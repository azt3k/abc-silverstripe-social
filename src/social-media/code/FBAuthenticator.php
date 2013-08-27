<?php
class FBAuthenticator extends Controller
{
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
						'scope='. 'email,'.
								  'user_about_me,'.
								  'user_activities,'.
								  'user_birthday,'.
								  'user_education_history,'.
								  'user_groups,'.
								  'user_hometown,'.
								  'user_interests,'.
								  'user_likes,'.
								  'user_location,'.
								  'user_questions,'.
								  'user_relationships,'.
								  'user_relationship_details,'.
								  'user_religion_politics,'.
								  'user_subscriptions,'.
								  'user_website,'.
								  'user_work_history,'.
								  'user_checkins,'.
								  'user_events,'.
								  'user_games_activity,'.
								  'user_notes,'.
								  'user_photos,'.
								  'user_status,'.
								  'user_videos,'.
								  'friends_about_me,'.
								  'friends_activities,'.
								  'friends_birthday,'.
								  'friends_education_history,'.
								  'friends_groups,'.
								  'friends_hometown,'.
								  'friends_interests,'.
								  'friends_likes,'.
								  'friends_location,'.
								  'friends_questions,'.
								  'friends_relationships,'.
								  'friends_relationship_details,'.
								  'friends_religion_politics,'.
								  'friends_subscriptions,'.
								  'friends_website,'.
								  'friends_work_history,'.
								  'friends_checkins,'.
								  'friends_events,'.
								  'friends_games_activity,'.
								  'friends_notes,'.
								  'friends_photos,'.
								  'friends_status,'.
								  'friends_videos,'.
								  'publish_actions,'.
								  'user_online_presence,'.
								  'friends_online_presence,'.
								  'manage_pages,'.
								  'publish_stream,'.
								  'read_mailbox,'.
								  'read_page_mailboxes,'.
								  'read_stream,'.
								  'export_stream,'.
								  'offline_access,'.
								  'status_update,'.
								  'photo_upload,'.
								  'video_upload,'.
								  'create_note,'.
								  'share_item,'.
								  'xmpp_login,'.
								  'sms,'.
								  'create_event,'.
								  'rsvp_event,'.
								  'read_friendlists,'.
								  'manage_friendlists,'.
								  'read_requests,'.
								  'manage_notifications,'.
								  'read_insights,'.
								  'ads_management,'.
								  'publish_checkins'.                        
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
		
		if (
			!$conf->FacebookAppId ||
			!$conf->FacebookAppSecret ||
			!$conf->FacebookUserId ||
			!$conf->FacebookPageId ||
			!$conf->FacebookUserAccessToken ||
			!$conf->FacebookPageAccessToken
		) {
			throw new Exception('Incomplete facebook configuration');
			return false;
		}
		
		// set up a call to the api

		$facebook = new Facebook(array(
			'appId'  => $conf->FacebookAppId,
			'secret' => $conf->FacebookAppSecret
		));
		if ( $validate == 'user' || (is_array($validate) && in_array('user', $validate)) ) {
			$facebook->setAccessToken($conf->FacebookUserAccessToken);
			$userValid = (object) $facebook->api("/me");
			$userValid = !empty($userValid->id) && $userValid->id == $conf->FacebookUserId ? true : false ;		
		}
		if ( $validate == 'page' || (is_array($validate) && in_array('page', $validate)) ) {
			$facebook->setAccessToken($conf->FacebookPageAccessToken);
			$pageValid = (object) $facebook->api("/me");
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
				'appId'  => $app_id,
				'secret' => $app_secret
			));
			
			$facebook->setAccessToken($params['access_token']);
			$page_info = $facebook->api("/".$page_id."?fields=access_token" );
			
			// die if we didn't get the required info
			if (empty($page_info['access_token'])) die('couldn\'t get page access token - are you logged in to the correct facebook account?');
			
			// save the page access token
			$this->conf->FacebookPageAccessToken = $page_info['access_token'];
			$this->conf->write();
			
			// grab the site config
			$freshConf = SiteConfig::current_site_config();
			
			// final output
			$freshConf->FacebookPageAccessToken && $freshConf->FacebookUserAccessToken
				? die('authenticated')
				: die('there was a problem authenticating');

		}else{
			
			die('crsf error');
			
		}
	
		
	}
}