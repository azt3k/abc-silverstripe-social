<?php

use Facebook\Facebook;

class FBAuthenticator extends Controller
{
	private static $allowed_actions = array(
		'index',
		'purge'
	);

	protected $conf;

	public function __construct() {

		$this->conf = SiteConfig::current_site_config();

		parent::__construct();

	}

	public static function getOAuthDialogURL()
	{
		// get the required vars for the dance
		$conf = SiteConfig::current_site_config();
		$app_id = $conf->FacebookAppId;
		$app_secret = $conf->FacebookAppSecret;
		$my_url = 'http://'.$_SERVER['HTTP_HOST'].'/'.get_class();
		$page_id = $conf->FacebookPageId;
		$user_id = $conf->FacebookUserId;

		$_SESSION['state'] = md5(uniqid(rand(), TRUE)); //CSRF protection
		$dialog_url =   'http://www.facebook.com/dialog/oauth?'.
						'client_id='.$app_id.'&'.
						'redirect_uri='.urlencode($my_url).'&'.
						'scope='. 'public_profile,'.
								  'email,'.
								  'user_about_me,'.
								  'user_events,'.
								  'user_likes,'.
								  'user_location,'.
								  'user_photos,'.
								  'user_posts,'.
								  'user_status,'.
								  'user_tagged_places,'.
								  'manage_pages,'.
								  'publish_pages,'.
								  'publish_actions'.
								  '&'.
						'state='.$_SESSION['state'];

		return $dialog_url;

	}

	public static function validateCRSF()
	{
		return $_SESSION['state'] && ($_SESSION['state'] === $_REQUEST['state']);
	}

	public static function purge_auth_tokens(){
		$conf = SiteConfig::current_site_config();
		$conf->FacebookUserAccessToken = null;
		$conf->FacebookPageAccessToken = null;
		$conf->write();
	}

	public static function validate_current_conf($validate = array('page','user'))
	{
		$userValid = $pageValid = true;
		$conf = SiteConfig::current_site_config();

		// if (
		// 	!$conf->FacebookAppId ||
		// 	!$conf->FacebookAppSecret ||
		// 	!$conf->FacebookUserId ||
		// 	!$conf->FacebookPageId ||
		// 	!$conf->FacebookUserAccessToken ||
		// 	!$conf->FacebookPageAccessToken
		// ) {
		// 	throw new Exception('Incomplete facebook configuration');
		// 	return false;
		// }

		// set up a call to the api

		$facebook = new Facebook(array(
			'app_id'  		=> $conf->FacebookAppId,
			'app_secret' 	=> $conf->FacebookAppSecret
		));
		if ( $validate == 'user' || (is_array($validate) && in_array('user', $validate)) ) {
			$facebook->setDefaultAccessToken((string) $conf->FacebookUserAccessToken);
			$userValid = (object) $facebook->sendRequest('get', '/me')->getDecodedBody();
			$userValid = !empty($userValid->id) && $userValid->id == $conf->FacebookUserId ? true : false ;
		}
		if ( $validate == 'page' || (is_array($validate) && in_array('page', $validate)) ) {
			$facebook->setDefaultAccessToken((string) $conf->FacebookPageAccessToken);
			$pageValid = (object) $facebook->sendRequest('get', '/me')->getDecodedBody();
			$pageValid = !empty($pageValid->id) && $pageValid->id == $conf->FacebookPageId ? true : false ;
		}

		if ($pageValid && $userValid) {
			return true;
		} else {
			$msg = '';
			if (!$pageValid) $msg.= 'Your Facebook page configuration is broken; ';
			if (!$userValid) $msg.= 'Your Facebook user configuration is broken; ';
			throw new Exception($msg);
			return false;
		}

	}

	public function purge(){

		// authorise
		$user = Member::currentUser();
		if (!Permission::checkMember($user, 'ADMIN')) return $this->httpError(401, 'You do not have access to the requested content');

		// purge
		self::purge_auth_tokens();

		die('Auth tokens purged.<br><br><a href="/FBAuthenticator">Click here to authorise</a>');

	}

	public function index() {

		// authorise
		$user = Member::currentUser();
		if (!Permission::checkMember($user, 'ADMIN')) return $this->httpError(401, 'You do not have access to the requested content');

		// get the required vars for the dance
		$app_id = $this->conf->FacebookAppId;
		$app_secret = $this->conf->FacebookAppSecret;
		$my_url = 'http://'.$_SERVER['HTTP_HOST'].'/'.get_class();
		$page_id = $this->conf->FacebookPageId;
		$user_id = $this->conf->FacebookUserId;

		if (empty($user_id)) die('Facebook User ID not supplied');
		if (empty($page_id)) die('Facebook Page ID not supplied');

		$code = empty($_REQUEST['code']) ? null : $_REQUEST['code'] ;

		if(empty($code)) {
			$dialog_url = self::getOAuthDialogURL();
			header('Location: '.$dialog_url);
			exit;
		}

		if (self::validateCRSF()) {

			// The user access token url
			$token_url =	'https://graph.facebook.com/oauth/access_token?'.
							'client_id='.		$app_id.'&'.
							'redirect_uri='.	urlencode($my_url).'&'.
							'client_secret='.	$app_secret.'&'.
							'code='.			$code;

			// fetch and parse the response
			$response = file_get_contents($token_url);
			$params = null;
			parse_str($response, $params);

			// die if we didn't get the required info
			if (empty($params['access_token'])) die('couldn\'t get user access token - are you logged in to the correct facebook account?');

			// save the user access token
			$this->conf->FacebookUserAccessToken = $params['access_token'];
			$this->conf->write();

			// set up a call to the api
			$facebook = new Facebook(array(
				'app_id'  => $app_id,
				'app_secret' => $app_secret
			));

			$facebook->setDefaultAccessToken($params['access_token']);
			$page_info = $facebook->sendRequest('get', '/' . $page_id . "?fields=access_token" )->getDecodedBody();

			// die if we didn't get the required info
			if (empty($page_info['access_token'])) die('couldn\'t get page access token - are you logged in to the correct facebook account?');

			// save the page access token
			$this->conf->FacebookPageAccessToken = $page_info['access_token'];
			$this->conf->write();

			// grab the site config
			$freshConf = SiteConfig::current_site_config();

			// final output
			$freshConf->FacebookPageAccessToken && $freshConf->FacebookUserAccessToken
				? die('authenticated.<br>page token: ' . $freshConf->FacebookPageAccessToken . '<br>user token: ' . $freshConf->FacebookUserAccessToken)
				: die('there was a problem authenticating');

		}else{

			die('crsf error');

		}


	}
}
