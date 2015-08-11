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

        $this->conf = static::get_conf();
        $this->instagram = static::get_instagram();

        parent::__construct();
    }

    public static function get_conf() {
        if (!static::$conf_instance) static::$conf_instance = SiteConfig::current_site_config();
        return static::$conf_instance;
    }

    public static function get_instagram() {
        if (!static::$instagram_instance) {
            $conf = static::get_conf();
            static::$instagram_instance = new Instagram(array(
                'apiKey'      => $conf->InstagramApiKey,
                'apiSecret'   => $conf->InstagramApiSecret,
                'apiCallback' => SocialHelper::php_self(),
            ));
        }

        return static::$instagram_instance;
    }

    public static function validate_current_conf() {

        $ins = static::get_instagram();
        $ins->setAccessToken(static::get_conf()->InstagramOAuthToken);
        $res = $ins->getUser();

        if (!empty($res->meta) && $res->meta->code == 200) {
            return true;
        } else {
            throw new Exception('There was an error: ' . print_r($res, 1));
            return false;
        }
    }

    protected function addError($err) {
        $this->errors[] = 'There was an error: ' . $err;
    }

    protected function addMsg($msg) {
        $this->messages[] = $msg;
    }

    protected function wipe() {
        $cnf = static::get_conf();
        $cnf->InstagramOAuthToken = null;
        $cnf->write();
        header('Location: ' . SocialHelper::php_self());
    }

    // Step 1: Request a temporary token
    protected function request_token() {
        header("Location: " . static::get_instagram()->getLoginUrl());
        exit;
    }

    // Step 2: This is the code that runs when Instagram redirects the user to the callback. Exchange the temporary token for a permanent access token
    protected function access_token() {
        $data = static::get_instagram()->getOAuthToken($_REQUEST['code']);
        $this->conf->InstagramOAuthToken = $data->access_token;
        $this->conf->InstagramUsername = $data->user->username;
        $this->conf->InstagramUserId = $data->user->id;
        $this->conf->write();
    }

    // Step 3: Now the user has authenticated, do something with the permanent token and secret we received
    protected function verify_credentials() {

        $ins = static::get_instagram();
        $ins->setAccessToken(static::get_conf()->InstagramOAuthToken);
        $res = $ins->getUser();

        if (!empty($res->meta) && $res->meta->code == 200) {
            $this->addMsg(
                '<p>Authourised as ' . $res->data->username . '</p>'
            );
        } else {
            $this->addError(print_r($res, 1));
        }
    }

    public function index() {

        // authorise
        $user = Member::currentUser();
        if (!Permission::checkMember($user, 'ADMIN')) return $this->httpError(401, 'You do not have access to the requested content');

        // trigger various modes
        if (isset($_REQUEST['start']))          $this->request_token();
        else if (isset($_REQUEST['code']))      $this->access_token();
        else if (isset($_REQUEST['verify']))    $this->verify_credentials();
        else if (isset($_REQUEST['wipe']))      $this->wipe();

        // verify credentials if available
        if ($this->conf->InstagramOAuthToken && !isset($_REQUEST['verify'])) $this->verify_credentials();

        // display output
        $errMsg = count($this->errors) ? "<p>".implode("<br />", $this->errors)."</p>" : '' ;
        $msgMsg = count($this->messages) ? "<p>".implode("<br />", $this->messages)."</p>" : '' ;

        return '<p>' . $msgMsg . $errMsg . (
            $this->conf->InstagramOAuthToken
                ? 'Do you want to: <ul><li><a href="?verify=1">reverify the credentials?</a></li><li><a href="?wipe=1">wipe them and start again</a></li></ul>'
                : '<a href="?start=1">Click to authorize</a>.'
        ) . '</p>';
    }

}
