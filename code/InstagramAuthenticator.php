<?php

use MetzWeb\Instagram\Instagram;

class InstagramAuthenticator extends Controller {

	protected static $conf_instance;
	protected static $instagram_instance;
	protected $conf;
	protected $instagram;
	protected $errors = array();
	protected $messages = array();

	public function __construct() {

		$this->conf		= static::get_conf();
		$this->instagram = static::get_instagram();

		parent::__construct();
	}

	public static function get_conf() {
		if (!static::$conf_instance) static::$conf_instance = SiteConfig::current_site_config();
		return static::$conf_instance;
	}

	public static function get_instagram() {

		$conf = static::get_conf();

		if (!static::$instagram_instance) {
			static::$instagram_instance = new Instagram(array(
				'apiKey'      => $conf->TwitterApiKey,
				'apiSecret'   => $conf->TwitterApiSecret,
				'apiCallback' => SocialHelper::php_self(),
			));
		}

		return static::$instagram_instance;
	}

	public static function validate_current_conf() {

		$conf		= static::get_conf();
		$instagram	= static::get_instagram();
		$code = 200;

		// $instagram->config['user_token']	= $conf->InstagramOAuthToken;
		// $instagram->config['user_secret']	= $conf->InstagramOAuthSecret;
		//
		// $code = $instagram->request(
		// 	'GET', $instagram->url('1.1/account/verify_credentials')
		// );

		if ($code == 200) {
			return true;
		} else {
			throw new Exception('There was an error: ' . $instagram->response['response']);
			return false;
		}
	}

	protected function addError() {
		$this->errors[] = 'There was an error: '.$this->instagram->response['response'];
	}

	protected function addMsg($msg) {
		$this->messages[] = $msg;
	}

	protected function wipe() {
		$this->conf->InstagramOAuthToken = null;
		$this->conf->InstagramOAuthSecret = null;
		$this->conf->write();
		unset($_SESSION['oauth']);
		header('Location: ' . SocialHelper::php_self());
	}

	// Step 1: Request a temporary token
	protected function request_token() {
		$inst = static::$instagram_instance;
		header("Location: " . static::$instagram_instance->getLoginUrl());
		exit;
	}

	// Step 2: Direct the user to the authorize web page
	protected function authorize() {
		$authurl = $this->instagram->url("oauth/authorize", '') . "?oauth_token={$_SESSION['oauth']['oauth_token']}";
		header("Location: ".$authurl);
		exit;

		// in case the redirect doesn't fire
		$this->addMsg('<p>To complete the OAuth flow please visit URL: <a href="' . $authurl . '">' . $authurl . '</a></p>');
	}

	// Step 3: This is the code that runs when Instagram redirects the user to the callback. Exchange the temporary token for a permanent access token
	protected function access_token() {

		$this->instagram->config['user_token'] = $_SESSION['oauth']['oauth_token'];
		$this->instagram->config['user_secret'] = $_SESSION['oauth']['oauth_token_secret'];

		$code = $this->instagram->request(
			'POST',
			$this->instagram->url('oauth/access_token', ''),
			array(
				'oauth_verifier' => $_REQUEST['oauth_verifier']
			)
		);

		if ($code == 200) {
			$token = $this->instagram->extract_params($this->instagram->response['response']);
			$this->conf->InstagramOAuthToken = $token['oauth_token'];
			$this->conf->InstagramOAuthSecret = $token['oauth_token_secret'];
			$this->conf->write();
			unset($_SESSION['oauth']);
			header('Location: ' . SocialHelper::php_self());
		} else {
			$this->addError();
		}
	}

	// Step 4: Now the user has authenticated, do something with the permanent token and secret we received
	protected function verify_credentials() {

		$this->instagram->config['user_token']	= $this->conf->InstagramOAuthToken;
		$this->instagram->config['user_secret']	= $this->conf->InstagramOAuthSecret;

		$code = $this->instagram->request(
			'GET', $this->instagram->url('1.1/account/verify_credentials')
		);

		// print_r($this->instagram->response);

		if ($code == 200) {
			$resp = json_decode($this->instagram->response['response']);
			$this->addMsg(
				'<p>Authourised as ' . $resp->screen_name . '</p>' .
				'<p>The access level of this token is: ' . $this->instagram->response['headers']['x-access-level'] . '</p>'
			);
		} else {
			$this->addError();
		}
	}

	public function index() {

		// authorise
		$user = Member::currentUser();
		if (!Permission::checkMember($user, 'ADMIN')) return $this->httpError(401, 'You do not have access to the requested content');

		// trigger various modes
		if (isset($_REQUEST['start']))					$this->request_token();
		else if (isset($_REQUEST['oauth_verifier']))	$this->access_token();
		else if (isset($_REQUEST['verify']))			$this->verify_credentials();
		else if (isset($_REQUEST['wipe']))				$this->wipe();

		// verify credentials if available
		if ($this->conf->InstagramOAuthToken && $this->conf->InstagramOAuthSecret && !isset($_REQUEST['verify'])) $this->verify_credentials();

		// display output
		$errMsg = count($this->errors) ? "<p>".implode("<br />", $this->errors)."</p>" : '' ;
		$msgMsg = count($this->messages) ? "<p>".implode("<br />", $this->messages)."</p>" : '' ;

		return '<p>' . $msgMsg . $errMsg . (
			$this->conf->InstagramOAuthToken && $this->conf->InstagramOAuthSecret
				? 'Do you want to: <ul><li><a href="?verify=1">reverify the credentials?</a></li><li><a href="?wipe=1">wipe them and start again</a></li></ul>'
				: '<a href="?start=1">Click to authorize</a>.'
		) . '</p>';
	}

}
