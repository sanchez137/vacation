<?php

namespace Drupal\require_login\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Require Login settings for this site.
 */
class RequireLoginSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'require_login_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['require_login.config'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('require_login.config');
    $form['require_login_deny_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Access Denied Message'),
      '#description' => $this->t('Shown to anonymous users prior to user authentication (login).'),
      '#default_value' => $config->get('deny_message'),
    ];
    $items = [
      $this->t('Use &lt;front	&gt; to exclude the front page.'),
      $this->t('Use relative path to exclude content and other internal Drupal pages. <em>Example: /about/contact</em>'),
      $this->t('Use absolute path to exclude Drupal bootstrap enabled PHP scripts. <em>Example: /path/to/drupal/script/filename.php</em>'),
    ];
    $items = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
    $description = $this->t('Disable user authentication (login) requirement on specific paths. Enter one exclusion per line using the following formats:');
    $form['require_login_excluded_paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Exclude Paths'),
      '#description' => $description . render($items),
      '#default_value' => $config->get('excluded_paths'),
    ];
    $form['require_login_auth_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User Authentication (Login) Path'),
      '#description' => $this->t('Override the user authentication (login) path. May include URL queries and fragments.'),
      '#default_value' => $config->get('auth_path') ? $config->get('auth_path') : 'user/login',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $absolutes = [];

    // Add leading slash to paths (except for <front>). Trims extra whitespace
    // and prepares exclusions for saving.
    $exclude_paths = explode(PHP_EOL, $form_state->getValue('require_login_excluded_paths'));
    foreach ($exclude_paths as $key => $exclude_path) {
      $exclude_path = trim($exclude_path);
      if (empty($exclude_path) || $exclude_path == '<front>') {
        continue;
      }
      $url = parse_url($exclude_path);

      // Detect invalid absolute domain in path.
      if (isset($url['scheme']) || isset($url['host']) || preg_match('/^www./i', $url['path'])) {
        $absolutes[] = trim($exclude_path);
      }

      // Confirm leading forward slash presence.
      elseif (substr($exclude_path, 0, 1) != '/') {
        $exclude_paths[$key] = '/' . $exclude_path;
      }

      // Trim unnecessary whitespace from ends.
      else {
        $exclude_paths[$key] = $exclude_path;
      }
    }
    $form_state->setValue('require_login_excluded_paths', implode(PHP_EOL, $exclude_paths));

    // Throw error if absolute paths were detected.
    if ($absolutes) {
      $form_state->setErrorByName('require_login_excluded_paths', $this->t("Excluded paths shouldn't include protocol or domain name. Invalid paths:<br />!paths", [
        '!paths' => implode('<br />', $absolutes),
      ]));
    }

    // Add leading slash to user login path. Trims extra whitespace and prepares
    // user login path for saving.
    $auth_path = trim($form_state->getValue('require_login_auth_path'));
    if (!empty($auth_path)) {
      $url = parse_url($auth_path);

      // Detect invalid absolute domain in path.
      if (isset($url['scheme']) || isset($url['host']) || preg_match('/^www./i', $url['path'])) {
        $form_state->setErrorByName('require_login_auth_path', $this->t('User login path must be relative.'));
      }

      // Confirm leading forward slash presence.
      elseif (substr($auth_path, 0, 1) != '/') {
        $form_state->setValue('require_login_auth_path', '/' . $auth_path);
      }

      // Trim unnecessary whitespace from ends.
      else {
        $form_state->setValue('require_login_auth_path', $auth_path);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('require_login.config')
      ->set('deny_message', $form_state->getValue('require_login_deny_message'))
      ->set('excluded_paths', $form_state->getValue('require_login_excluded_paths'))
      ->set('auth_path', $form_state->getValue('require_login_auth_path'))
      ->save();

    drupal_flush_all_caches();
    parent::submitForm($form, $form_state);
  }

}
