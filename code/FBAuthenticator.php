<?php

use Facebook\Facebook;

/**
 * @author AzT3k
 */
class FBAuthenticator extends Controller
{

    private static $allowed_actions = array(
        'index',
        'purge'
    );

    protected static $conf_instance;
    protected static $facebook_instance;
    protected $conf;
    protected $facebook;

    public static function get_conf()
    {
        if (!static::$conf_instance) {
            static::$conf_instance = SiteConfig::current_site_config();
        }
        return static::$conf_instance;
    }

    public static function get_facebook()
    {
        if (!static::$facebook_instance) {
            $conf = static::get_conf();

            static::$facebook_instance = new Facebook(array(
                'app_id'        => $conf->FacebookAppId,
                'app_secret'    => $conf->FacebookAppSecret
            ));
        }

        return static::$facebook_instance;
    }

    public function __construct()
    {
        $this->conf = static::get_conf();
        $this->facebook = static::get_facebook();

        parent::__construct();
    }

    public static function getOAuthDialogURL()
    {

        // get the required vars for the dance
        $conf       = static::get_conf();
        $app_id     = $conf->FacebookAppId;
        $app_secret = $conf->FacebookAppSecret;
        $my_url     = SocialHelper::php_self();

        $_SESSION['state'] = md5(uniqid(rand(), true)); //CSRF protection
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

    public static function validate_crsf()
    {
        return $_SESSION['state'] && ($_SESSION['state'] === $_REQUEST['state']);
    }

    public static function purge_auth_tokens()
    {
        $conf = static::get_conf();
        $conf->FacebookUserAccessToken = null;
        $conf->FacebookPageAccessToken = null;
        $conf->write();
    }

    public static function validate_current_conf($validate = array('page', 'user'))
    {
        $userValid = $pageValid = true;
        $conf      = static::get_conf();
        $facebook  = static::get_facebook();

        if ($validate == 'user' || (is_array($validate) && in_array('user', $validate))) {
            $facebook->setDefaultAccessToken((string) $conf->FacebookUserAccessToken);
            $userValid = (object) $facebook->sendRequest('get', '/me')->getDecodedBody();
            $userValid = !empty($userValid->id) && $userValid->id == $conf->FacebookUserId ? true : false ;
        }

        if ($validate == 'page' || (is_array($validate) && in_array('page', $validate))) {
            $facebook->setDefaultAccessToken((string) $conf->FacebookPageAccessToken);
            $pageValid = (object) $facebook->sendRequest('get', '/me')->getDecodedBody();
            $pageValid = !empty($pageValid->id) && $pageValid->id == $conf->FacebookPageId ? true : false ;
        }

        if ($pageValid && $userValid) {
            return true;
        } else {
            $msg = '';
            if (!$pageValid) {
                $msg.= 'Unable to fetch page data; ';
            }
            if (!$userValid) {
                $msg.= 'Unable to fetch user data; ';
            }
            throw new Exception($msg);
            return false;
        }
    }

    public function purge()
    {

        // authorise
        $user = Member::currentUser();
        if (!Permission::checkMember($user, 'ADMIN')) {
            return $this->httpError(401, 'You do not have access to the requested content');
        }

        // purge
        static::purge_auth_tokens();

        die('Auth tokens purged.<br><br><a href="/FBAuthenticator">Click here to authorise</a>');
    }

    public function index()
    {

        // authorise
        $user = Member::currentUser();
        if (!Permission::checkMember($user, 'ADMIN')) {
            return $this->httpError(401, 'You do not have access to the requested content');
        }

        // grab the API Lib
        $facebook = static::get_facebook();

        // the auth process authorises both a user and a page
        // it might pay to make this a seperate step in future if we decide to add user feeds as well as page feeds
        if (empty($this->conf->FacebookPageId)) {
            die('Facebook Page ID not supplied');
        }

        // if there's a code in the request grab it
        $code = empty($_REQUEST['code']) ? null : $_REQUEST['code'] ;

        // if the code is empty shoot off to fb and grab one - it will redirect back here with a code
        if (empty($code)) {
            $dialog_url = static::getOAuthDialogURL();
            header('Location: '.$dialog_url);
            exit;
        }

        // so we've got a code now - lets do some more authorisation
        if (static::validate_crsf()) {

            // The user access token url
            $token_url = 'https://graph.facebook.com/oauth/access_token' .
                         '?client_id='.     $this->conf->FacebookAppId .
                         '&redirect_uri='.  urlencode(SocialHelper::php_self()) .
                         '&client_secret='. $this->conf->FacebookAppSecret .
                         '&code='.          $code;

            // fetch and parse the response
            $response = file_get_contents($token_url);
            $params = null;
            parse_str($response, $params);

            // die if we didn't get the required info
            if (empty($params['access_token'])) {
                die('couldn\'t get user access token - are you logged in to the correct facebook account?');
            }

            // save the user access token
            $this->conf->FacebookUserAccessToken = $params['access_token'];
            $this->conf->write();

            // get the user ID associated with the access token and stash it for later
            $facebook->setDefaultAccessToken((string) $this->conf->FacebookUserAccessToken);
            $user = (object) static::get_facebook()->sendRequest('get', '/me')->getDecodedBody();

            // die if we couldn't get an id
            if (empty($user->id)) {
                die('couldn\'t access user info');
            }

            // save the user ID
            $this->conf->FacebookUserId = $user->id;
            $this->conf->write();

            // get an access token for the page
            $page_info = $facebook->sendRequest('get', '/' . $this->conf->FacebookPageId . "?fields=access_token")->getDecodedBody();

            // die if we didn't get the required info
            if (empty($page_info['access_token'])) {
                die('couldn\'t get page access token - are you logged in to the correct facebook account and an admin of page ' . $this->conf->FacebookPageId . '?');
            }

            // save the page access token
            $this->conf->FacebookPageAccessToken = $page_info['access_token'];
            $this->conf->write();

            // grab the site config
            $freshConf = SiteConfig::current_site_config();

            // final output
            echo '<p>User ' . $this->conf->FacebookUserId . ($freshConf->FacebookUserAccessToken ? ' was authenticated' : ' was not authenticated') . '</p>';
            echo '<p>Page ' . $this->conf->FacebookPageId . ($freshConf->FacebookPageAccessToken ? ' was authenticated' : ' was not authenticated') . '</p>';
            exit;
        } else {
            die('crsf error');
        }
    }
}
