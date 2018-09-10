<?php

namespace Drupal\googlelogin\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\block\Annotation\Translation;

use Drupal\googlelogin\Plugin\Network\Google_Client;
use Drupal\googlelogin\Plugin\Network\contrib\Google_Oauth2Service;

/**
 * Provides a Google OAuth Login Block
 *
 * @Block(
 *   id = "google_oauth__login_block",
 *   admin_label = @Translation("Google OAuth Login"),
 *   category = @Translation("Blocks")
 * )
 */
class GoogleOAuthLoginBlock extends BlockBase {
 	
 	/**
   * {@inheritdoc}
   */
  public function build() {
  	
    $config = \Drupal::config('google_oauth_login.settings');
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

		if(isset($_SESSION['google_oauth_token'])) {
			$gClient->setAccessToken($_SESSION['google_oauth_token']);
			if ($gClient->getAccessToken()) {
	      //Get user profile data from google.
	      $userInfo = $google_oauthV2->userinfo->get();
	      $output = $userInfo['name'];
	    }
		}		
    else {
    	$iconUrl = '';
    	$authUrl = $gClient->createAuthUrl();

			$display = \Drupal::config('googlelogin.icon.settings')->get('display');
	    $display_url = \Drupal::config('googlelogin.icon.settings')->get('display_url');

	    $path = drupal_get_path('module', 'googlelogin');

	    if (isset($display_url) && $display_url!='') {
	      $iconUrl = '<img src = '.$display_url.' />';
	    }
	    else {
	      if ($display == 0) {
	        $iconUrl = '<img src = "/'. $path .'/images/google-login.png" border="0">';
	      }
	      if ($display == 1) {         
	        $iconUrl = '<img src = "/'. $path .'/images/google-signin.png" border="0">';
	      }
	      if ($display == 2) {         
	        $iconUrl = '<img src = "/'. $path .'/images/google-sign-in.png" border="0">';
	      }
	    }

			$output = '<a href="'. $authUrl .'">'. $iconUrl .'</a>';  
    }	    
    
    return array(
      '#markup' => $output,
      '#cache' => array(
        'max-age' => 0
      )
    );
  }
    
}
