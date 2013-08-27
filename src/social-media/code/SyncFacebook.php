<?php

/**
 * @todo need reconcile removals in both directions
 */
class SyncFacebook extends BuildTask {
	
	protected static $conf_instance;
	protected static $facebook_instance;
	protected $conf;
	protected $facebook;
	protected $errors = array();	
	protected $messages = array();
	
	public function __construct() {

		$this->conf		= $this->getConf();
		$this->facebook = $this->getFacebook();

		parent::__construct();
	}

	public function getConf() {
		if (!self::$conf_instance) self::$conf_instance = SiteConfig::current_site_config();
		return self::$conf_instance;
	}

	public function getFacebook() {

		if (!$this->conf) $this->conf = $this->getConf();
		
		if (!self::$facebook_instance) {
			self::$facebook_instance = new Facebook(array(
				'appId'  => $this->conf->FacebookAppId,
				'secret' => $this->conf->FacebookAppSecret
			));
			self::$facebook_instance->setAccessToken($this->conf->FacebookPageAccessToken);		
		}
		
		return self::$facebook_instance;
	}	
	
	function init() {
		
		parent::init();
		//Controller::init();

		if (!Director::is_cli() && !Permission::check("ADMIN") && $_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR']) {
			return Security::permissionFailure();
		}
		
		if (!$this->conf || !$this->facebook) $this->__construct();
		
	}
	
	function run($request) {
		
		// output
		echo "<br />\n<br />\nSyncing...<br />\n<br />\n";
		flush();
		ob_flush();

		if (!$this->conf->FacebookPullUpdates) {
			echo "Sync disabled <br />\n<br />\n";
			return;
		}
					
		// grab the most recent tweet
		$params = array();
		$params['since'] = ($lastUpdate = DataObject::get_one('FBUpdate','','','UpdateID DESC')) ? $lastUpdate->UpdateID  : 1 ;
		
		// set the number of hits
		$params['limit'] = 200;
		
		// if there was no last tweet we need to go into initial population
		$initPop = $lastUpdate ? false : true ;
		
		// get tweets
		$resp = (object) $this->facebook->api("/".$this->conf->FacebookPageId."/feed");		
		
		// only proceed if we have results to work with
		if (count($resp->data)) {
				
			// process the response
			$this->processResponse($resp->data);

			// check if we need to do an initial population
			if ($initPop) {

				//output
				echo "<br />\n<br />\nDoing initial Population<br />\n<br />\n";
				flush();
				ob_flush();

				// keep going until we hit a problem
				while (count($resp)) {
					 
					// only proceed if we have paging data
					if (!empty($resp->paging)) {
						
						// parse url
						$parsed = (object) parse_url($resp->paging['next']);
						
						// only proceed if we have the query string params
						if (!empty($parsed->query)) {
							
							// parse the query
							parse_str($parsed->query, $q);
							$q = (object) $q;

							// get tweets
							$until = empty($q->until) ? '' : $q->until;
							$resp = (object) $this->facebook->api("/".$this->conf->FacebookPageId."/feed?limit=25&until=".$until);

							// only proceed if we have results to work with
							if (count($resp->data)) {

								// process the response
								$noNew = $this->processResponse($resp->data);

								// break if we haven't added anything
								if ($noNew) break;

							}
						} else {
							die(print_r($resp,1));
						}
						
					} else{
						// output
						echo "No more pages <br />\n<br />\n";
						flush();
						ob_flush();
						break;
					}

				 }

				// output
				echo "Finished\n";
				flush();
				ob_flush();
			}

		} else {

			// output
			echo "No hits <br /><br />\n";
			flush();
			ob_flush();

		}	
		
	}
	
	public function processResponse(array $resp) {
		
		$noNew = true;
		
		foreach ($resp as $data) {
			
			// type cast
			$data = (object) $data;
			
			if (!$savedUpdate = DataObject::get_one('FBUpdate',"UpdateID='".$data->id."'")) {
				if (!$pubUpdate = DataObject::get_one('PublicationFBUpdate',"FBUpdateID='".$data->id."'")) {

					// push output
					echo "Adding Update ".$data->id."<br />\n";
					flush();
					ob_flush();

					// create the tweet data object
					$update = new FBUpdate;
					$update->updateFromUpdate($data);
					$update->write();
					if (!$update->doPublish()) die('Failed to Publish '.$update->Title);

					// set no new flag
					$noNew = false;
				
				} else {

					// push output
					echo "Update ".$data->id." came from the website<br />\n";
					flush();
					ob_flush();

				}
			} else {

				// this should only happen during initial population because we should have only got in tweets that are newer than x

				// push output
				echo "Already added Update ".$data->id."<br />\n";
				flush();
				ob_flush();
				
			}
		}
		
		return $noNew;
		
	}
	
}