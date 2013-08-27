<?php
class PostToSocialMedia extends Controller
{
	protected static $conf;

	public function __construct() {
		
		self::$conf = SiteConfig::current_site_config();

		parent::__construct();
		
	}
	
	/**
	 * @todo actually validate the configuration - will need to create a class extened from controller for authenticating / validating the configuration refer to FBAuthenticator
	 * @return boolean
	 */
	public function confirmTwitterAccess() {
		
		if (!self::$conf->TwitterPushUpdates) return false;

		if (
			self::$conf->TwitterConsumerKey &&
			self::$conf->TwitterConsumerSecret &&
			self::$conf->TwitterOAuthToken &&
			self::$conf->TwitterOAuthSecret
		) {
			try {
				TwitterAuthenticator::validate_current_conf();
				return true;
			} catch (Exception $e) {
				self::$conf->TwitterOAuthToken = null;
				self::$conf->TwitterOAuthSecret = null;
				self::$conf->write();
				return false;
			}
		} else {
			return false;
		}
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function confirmFacebookAccess() {

		if (!self::$conf->FacebookPushUpdates) return false;		
		
		try {
			FBAuthenticator::validate_current_conf('page');
		} catch (Exception $e) {
			self::$conf->FacebookPageAccessToken = null;
			self::$conf->write();
			return false;
		}
		
		try {
			FBAuthenticator::validate_current_conf('user');
		} catch (Exception $e) {
			self::$conf->FacebookUserAccessToken = null;
			self::$conf->write();	
			return false;
		}
		
		return true;
	}	

	/**
	 * 
	 * @param array $data
	 * @param array $services
	 */
	public function sendToSocialMedia(array $data, array $services = array('facebook','twitter')) {
		
		// init output
		$ids = array(
			'facebook'	=> null,
			'twitter'	=> null
		);

		// Facebook
		if (in_array('facebook', $services) && $this->confirmFacebookAccess()) {		
			
			$facebook = new Facebook(array(
				'appId'  => self::$conf->FacebookAppId,
				'secret' => self::$conf->FacebookAppSecret,
			));

			$facebook->setAccessToken(self::$conf->FacebookPageAccessToken);
			try {
				$post_id = $facebook->api("/".self::$conf->FacebookPageId."/feed", "post", $data);
				$ids['facebook'] = $post_id['id'];
			} catch (FacebookApiException $e) {
				SS_Log::log('Error '.$e->getCode().' : '.$e->getFile().' Line '.$e->getLine().' : '.$e->getMessage()."\n".'BackTrace: '."\n".$e->getTraceAsString(),SS_Log::ERR);
			}
			
		}

		// Twitter
		if (in_array('twitter', $services) && $this->confirmTwitterAccess()) {
			
			$connection = new tmhOAuth(array(
				'consumer_key'		=> self::$conf->TwitterConsumerKey,
				'consumer_secret'	=> self::$conf->TwitterConsumerSecret,
				'user_token'		=> self::$conf->TwitterOAuthToken,
				'user_secret'		=> self::$conf->TwitterOAuthSecret
			));

			$tweet = $data['name'] . ": " . $data['link'];
			$code = $connection->request('POST', $connection->url('1.1/statuses/update'), array('status' => $tweet));
			
			if ($code == 200) {
				$data = json_decode($connection->response['response']);
				$ids['twitter'] = $data->id_str;
			}

			
		}
		
		return $ids;

	}
}