<?php

class TwitterAuthenticator extends Controller {

	protected static $conf_instance;
	protected static $tmh_oauth_instance;
	protected $conf;
	protected $tmhOAuth;
	protected $errors = array();
	protected $messages = array();

	public function __construct() {

		$this->conf		= self::get_conf();
		$this->tmhOAuth = self::get_tmh_oauth();

		parent::__construct();
	}

	public static function get_conf() {
		if (!self::$conf_instance) self::$conf_instance = SiteConfig::current_site_config();
		return self::$conf_instance;
	}

	public static function get_tmh_oauth() {

		$conf = self::get_conf();

		if (!self::$tmh_oauth_instance) {
			self::$tmh_oauth_instance = new tmhOAuth(array(
				'consumer_key'		=> $conf->TwitterConsumerKey,
				'consumer_secret'	=> $conf->TwitterConsumerSecret,
			));
		}

		return self::$tmh_oauth_instance;
	}

	public static function validate_current_conf() {

		$conf		= self::get_conf();
		$tmhOAuth	= self::get_tmh_oauth();

		$tmhOAuth->config['user_token']		= $conf->TwitterOAuthToken;
		$tmhOAuth->config['user_secret']	= $conf->TwitterOAuthSecret;

		$code = $tmhOAuth->request(
			'GET', $tmhOAuth->url('1.1/account/verify_credentials')
		);

		if ($code == 200) {
			return true;
		} else {
			throw new Exception('There was an error: ' . $tmhOAuth->response['response']);
			return false;
		}
	}

	protected function addError() {
		$this->errors[] = 'There was an error: '.$this->tmhOAuth->response['response'];
	}

	protected function addMsg($msg) {
		$this->messages[] = $msg;
	}

	protected function wipe() {
		$this->conf->TwitterOAuthToken = null;
		$this->conf->TwitterOAuthSecret = null;
		$this->conf->write();
		unset($_SESSION['oauth']);
		header('Location: ' . Twitter::php_self());
	}

	// Step 1: Request a temporary token
	protected function request_token() {
		$code = $this->tmhOAuth->request(
			'POST',
			$this->tmhOAuth->url('oauth/request_token', ''),
			array(
				'oauth_callback' => Twitter::php_self()
			)
		);

		if ($code == 200) {
			$_SESSION['oauth'] = $this->tmhOAuth->extract_params($this->tmhOAuth->response['response']);
			$this->authorize();
		} else {
			$this->addError();
		}
	}

	// Step 2: Direct the user to the authorize web page
	protected function authorize() {
		$authurl = $this->tmhOAuth->url("oauth/authorize", '') . "?oauth_token={$_SESSION['oauth']['oauth_token']}";
		header("Location: ".$authurl);
		exit;

		// in case the redirect doesn't fire
		$this->addMsg('<p>To complete the OAuth flow please visit URL: <a href="' . $authurl . '">' . $authurl . '</a></p>');
	}

	// Step 3: This is the code that runs when Twitter redirects the user to the callback. Exchange the temporary token for a permanent access token
	protected function access_token() {

		$this->tmhOAuth->config['user_token'] = $_SESSION['oauth']['oauth_token'];
		$this->tmhOAuth->config['user_secret'] = $_SESSION['oauth']['oauth_token_secret'];

		$code = $this->tmhOAuth->request(
			'POST',
			$this->tmhOAuth->url('oauth/access_token', ''),
			array(
				'oauth_verifier' => $_REQUEST['oauth_verifier']
			)
		);

		if ($code == 200) {
			$token = $this->tmhOAuth->extract_params($this->tmhOAuth->response['response']);
			$this->conf->TwitterOAuthToken = $token['oauth_token'];
			$this->conf->TwitterOAuthSecret = $token['oauth_token_secret'];
			$this->conf->write();
			unset($_SESSION['oauth']);
			header('Location: ' . Twitter::php_self());
		} else {
			$this->addError();
		}
	}

	// Step 4: Now the user has authenticated, do something with the permanent token and secret we received
	protected function verify_credentials() {

		$this->tmhOAuth->config['user_token']	= $this->conf->TwitterOAuthToken;
		$this->tmhOAuth->config['user_secret']	= $this->conf->TwitterOAuthSecret;

		$code = $this->tmhOAuth->request(
			'GET', $this->tmhOAuth->url('1.1/account/verify_credentials')
		);

		// print_r($this->tmhOAuth->response);

		if ($code == 200) {
			$resp = json_decode($this->tmhOAuth->response['response']);
			$this->addMsg(
				'<p>Authourised as ' . $resp->screen_name . '</p>' .
				'<p>The access level of this token is: ' . $this->tmhOAuth->response['headers']['x-access-level'] . '</p>'
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
		elseif (isset($_REQUEST['oauth_verifier']))		$this->access_token();
		elseif (isset($_REQUEST['verify']))				$this->verify_credentials();
		elseif (isset($_REQUEST['wipe']))				$this->wipe();

		// verify credentials if available
		if ($this->conf->TwitterOAuthToken && $this->conf->TwitterOAuthSecret && !isset($_REQUEST['verify'])) $this->verify_credentials();

		// display output
		$errMsg = count($this->errors) ? "<p>".implode("<br />",$this->errors)."</p>" : '' ;
		$msgMsg = count($this->messages) ? "<p>".implode("<br />",$this->messages)."</p>" : '' ;

		return '<p>'.$msgMsg.$errMsg.(
			$this->conf->TwitterOAuthToken && $this->conf->TwitterOAuthSecret
				? 'Do you want to: <ul><li><a href="?verify=1">reverify the credentials?</a></li><li><a href="?wipe=1">wipe them and start again</a></li></ul>'
				: '<a href="?start=1">Click to authorize</a>.'
		).'</p>';
	}

}
