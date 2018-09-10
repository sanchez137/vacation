<?php

namespace Drupal\googlelogin\Plugin\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Settings form for Social API Google.
 */
class GoogleOAuthCredentialsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_oauth_login_admin_settings';
  }
  
  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames()  {
    return array(
      'google_oauth_login.settings'
    );
  }
  
  /**
   * Build Admin Settings Form.
   */
  public function buildForm(array $form, FormStateInterface $form_state)  {
    $config = $this->config('google_oauth_login.settings');
        
    $form['google_oauth_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Google OAuth Settings'),
      '#open' => TRUE
    ];
    $form['google_oauth_settings']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google OAuth ClientID'),
      '#required' => TRUE,
      '#default_value' => $config->get('client_id')
    ];
    $form['google_oauth_settings']['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google OAuth Client Secret'),
      '#required' => TRUE,
      '#default_value' => $config->get('client_secret')
    ];
    $form['google_oauth_settings']['redirect_url']  = [
      '#type' => 'textfield',
      '#title' => $this->t('Google OAuth Redirect URL'),
      '#required' => TRUE,
      '#default_value' => $config->get('redirect_url'),
      '#description'=> $this->t('Redirect URL should be in the following format ex: https://example.com/google_oauth_login'),
    ];
    return parent::buildForm($form, $form_state);
  }
  
  /**
   * Build Admin Submit.
   */
  public function submitForm(array &$form, FormStateInterface $form_state)  {
    $values = $form_state->getValues();
    $this->config('google_oauth_login.settings')
      ->set('client_id', $values['client_id'])
      ->set('client_secret', $values['client_secret'])
      ->set('redirect_url', $values['redirect_url'])
      ->save();
    drupal_set_message($this->t('Configuration Updated'));       
  }

}