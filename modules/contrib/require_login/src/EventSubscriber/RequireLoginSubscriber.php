<?php

namespace Drupal\require_login\EventSubscriber;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Xss;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Subscribe to kernal request event to check authentication.
 */
class RequireLoginSubscriber implements EventSubscriberInterface {

  /**
   * The module handler interface.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config factory interface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config;

  /**
   * The container interface.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  private $service;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $request;

  /**
   * The language manager interface.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private $language;

  /**
   * The account proxy interface.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $user;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $service_container
   *   The service container.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The account proxy interface.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager interface.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, ContainerInterface $service_container, RequestStack $request_stack, AccountProxyInterface $current_user, LanguageManagerInterface $language_manager) {
    $this->moduleHandler = $module_handler;
    $this->config = $config_factory;
    $this->service = $service_container;
    $this->request = $request_stack;
    $this->user = $current_user;
    $this->language = $language_manager;
  }

  /**
   * Checks authentication status of current user.
   *
   * @param object $config
   *   Object containing module configurations.
   * @param string $route_name
   *   The current pages routing name.
   *
   * @return bool
   *   Returns TRUE if user is authenticated and FALSE otherwise.
   */
  public function checkUserAuth($config, $route_name) {
    $lang = $this->language->getCurrentLanguage()->getId();
    $path = $this->service->get('path.current')->getPath();
    $path = trim($this->service->get('path.alias_manager')->getAliasByPath($path, $lang));

    // Compare current path with configured exclude paths. Returns TRUE when
    // at least one excluded path matches the current path.
    $exclude_paths = explode(PHP_EOL, $config->get('excluded_paths'));
    if ($auth_path = Xss::filterAdmin($config->get('auth_path'))) {
      $exclude_paths[] = $auth_path;
    }
    if ($this->service->get('path.matcher')->matchPath($path, implode(PHP_EOL, $exclude_paths))) {
      return TRUE;
    }

    // Various checks to determine exceptions for current page. Returns TRUE
    // when at least one check has evaluated as TRUE.
    $checks = [
      // Check user authentication.
      ($this->user->getAccount()->id() > 0),
      // Check cron.php file.
      ($route_name == 'system.cron'),
      // Check update.php file.
      ($route_name == 'system.db_update'),
      // Check timezone.
      ($route_name == 'system.timezone'),
      // Check user pages.
      ($route_name == 'user.login' || $route_name == 'user.register' || $route_name == 'user.pass' || substr($route_name, 0, 10) === 'user.reset'),
      // Check drush script.
      (function_exists('drupal_is_cli') && drupal_is_cli()),
    ];

    // Allow other modules to alter authentication checks.
    $this->moduleHandler->alter('require_login_authcheck', $checks);

    foreach ($checks as $check) {
      if ($check) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Redirect non-authenticated users to login page.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event response.
   */
  public function checkUserRedirect(GetResponseEvent $event) {
    $config = $this->config->get('require_login.config');
    $current_route = $this->request->getCurrentRequest(RouteObjectInterface::ROUTE_NAME);
    $route_name = $current_route->get('_route');
    $path = $this->service->get('path.current')->getPath();
    $configs = $this->config->get('system.site')->get('page');
    $request = $event->getRequest();

    // Retrieve the current page HTTP access code. Necessary so that customized
    // 403/404 pages are not redirected to login immediately.
    if ($request->attributes->get('exception') instanceof FlattenException) {
      $status_code = $request->attributes->get('exception')->getStatusCode();
    }
    else {
      $status_code = NULL;
    }

    // Exclude specific system paths from redirect to prevent infinite loops
    // when 'view published content' is disabled (not checked).
    $excluded = [
      '/system/403', $configs['403'],
      '/system/404', $configs['404'],
    ];
    if (in_array($path, $excluded) || $status_code == '404' || $status_code == '403') {
      return;
    }

    // Check user authentication status. Non-authenticated users will
    // automatically redirect to /user/login (OR configured login path).
    if (!$this->checkUserAuth($config, $route_name)) {

      // Display access denied message.
      $message = Xss::filterAdmin($config->get('deny_message'));
      if (!empty($message)) {
        drupal_set_message($message, 'warning');
      }

      // Prepare authentication redirect path.
      if ($auth_path = Xss::filterAdmin($config->get('auth_path'))) {
        $redirectPath = "internal:{$auth_path}";
      }
      else {
        $redirectPath = 'internal:/user/login';
      }

      $redirect = Url::fromUri($redirectPath, ['query' => ['destination' => $path]])->toString();
      $event->setResponse(new RedirectResponse($redirect));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkUserRedirect'];
    return $events;
  }

}
