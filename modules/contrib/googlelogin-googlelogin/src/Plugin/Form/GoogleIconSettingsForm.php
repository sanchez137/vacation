<?php

namespace Drupal\googlelogin\Plugin\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for Social API Icon Google.
 */
class GoogleIconSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_oauth_login_icon_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'googlelogin.icon.settings',
    ];
  }

  /**
   * Build Admin Settings Form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('googlelogin.icon.settings');

    $path = drupal_get_path('module', 'googlelogin');

    $display1 = '<img src = "/' . $path . '/images/google-login.png" border="0" width="10%">';
    $display2 = '<img src = "/' . $path . '/images/google-signin.png" border="0" width="10%">';
    $display3 = '<img src = "/' . $path . '/images/google-sign-in.png" border="0" width="10%">';
    

    $form['icon']['display'] = [
      '#type' => 'radios',
      '#title' => $this->t('Display Settings'),
      '#default_value' => $config->get('display'),
      '#options' => [0 => $display1, 1 => $display2, 2 => $display3],
    ];

    $form['icon']['display_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Direct URL'),
      '#default_value' => $config->get('display_url'),
      '#description' => $this->t('Please use absolute URL'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit Common Admin Settings.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('googlelogin.icon.settings')
      ->set('display', $values['display'])
      ->set('display_url', $values['display_url'])
      ->save();

    drupal_set_message($this->t('Icon Settings are updated'));
  }

}
