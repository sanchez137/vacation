<?php
namespace Drupal\googlelogin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Drupal\googlelogin\Plugin\Network\Google_Client;
use Drupal\googlelogin\Plugin\Network\contrib\Google_Oauth2Service;


class GoogleOAuthLoginController extends ControllerBase {
  public function googleOAuthUserLogin() {
    $this->google_oauth_user_login();
    return $this->redirect('<front>');
  }
    
  public function google_oauth_user_login() {
    $config       = \Drupal::config('google_oauth_login.settings');
    //Google client ID.
    $clientId = $config->get('client_id');
    //Google client secret.
    $clientSecret = $config->get('client_secret');
    //Callback URL.
    $redirectURL = $config->get('redirect_url');;
    //Call Google API.
    $gClient = new Google_Client();
    $gClient->setApplicationName('Sample Application');
    $gClient->setClientId($clientId);
    $gClient->setClientSecret($clientSecret);
    $gClient->setRedirectUri($redirectURL);
    
    $google_oauthV2 = new Google_Oauth2Service($gClient);
        
    if (isset($_GET['code'])) {
      \Drupal::logger('googlelogin')->notice('Authenticate call1');
      $auth = $gClient->authenticate($_GET['code']);
      $_SESSION['google_oauth_token'] = $gClient->getAccessToken();
    }
        
    if (isset($_SESSION['google_oauth_token'])) {
      $gClient->setAccessToken($_SESSION['google_oauth_token']);
    }
    if ($gClient->getAccessToken()) {
      //Get user profile data from google.
      $userInfo = $google_oauthV2->userinfo->get();
      //Initialize User class.
      $this->userExist($userInfo);
      //Insert or update user data to the database.        
    }
  }
    
  public function userExist($userInfo) {
    $email    = $userInfo['email'];
    $user_id  = $userInfo['id'];
    $userData = user_load_by_mail($email);
    if (empty($userData)) {
      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
      $user = \Drupal\user\Entity\User::create();

      //Mandatory settings.
      $user->setPassword(FALSE);
      $user->enforceIsNew();
      $user->setEmail($email);
      $user->setUsername($email); 

      //Optional settings.    
      $user->set("init", 'email');
      $user->set("langcode", $language);
      $user->set("preferred_langcode", $language);
      $user->set("preferred_admin_langcode", $language);
      $user->activate();
      //Save user.
      $user->save();
      user_login_finalize($user);
    } 
    else {
      user_login_finalize($userData);
    }
  }

} 