<?php

/**
 * @todo need reconcile removals in both directions
 * @todo remove PublicationFBUpdateID && PublicationTweetID as they aren't really needed any more - if testing for post just call $this->owner->PublicationTweets()->count()
 */
class SocialMediaPageExtension extends DataExtension {
	
	protected $justPosted = false;

	private static $db = array(
		"LastPostedToSocialMedia"	=> "SS_Datetime",
		"PublicationFBUpdateID"		=> 'Varchar(255)',
		"PublicationTweetID"		=> 'Varchar(255)',
		"ForceUpdateMode"			=> "Enum('Default,Block,Force')"
	);
	private static $has_many = array(
		"PublicationTweets"			=> "PublicationTweet",
		"PublicationFBUpdates"		=> "PublicationFBUpdate"
	);
	private static $defaults = array(
		"ForceUpdateMode"			=> 'Default'
	);
	
	// Dummy getters - can be overridden on a per project basis to channel specific content to updates
	// ------------------------------------------------------------------------------------------------
	
	public function AssociatedImage() {
		return false;
	}
	
	public function SharedContent() {
		return $this->owner->Content;
	}
	
	public function SharedTitle() {
		return $this->owner->Title;
	}
	
	public function SharedLink() {
		return $this->owner->AbsoluteLink();
	}		
	
	// Other Methods
	// ------------------------------------------------------------------------------------------------
	
	public function parseContent($content, $words = null, $allowedTags = '<br>') {
		$br2nl = false;
		if (stripos('<br>',$allowedTags) === false) {
			$allowedTags.= '<br>';
			$br2nl = true;
		}
		$str = preg_replace(
			'/<br><br>$/',
			'',
			strip_tags(
				str_replace(
					array('<p>','</p>'), 
					array('','<br><br>'), 
					preg_replace(
						'/[\s\t\n ]+/',
						' ',
						$content
					)
				),
				$allowedTags
			)
		);
		if ($br2nl) $str = str_replace (array('<br>','<br/>','<br />'),"\n", $str);
		return $words ? AbcStr::get($str)->limitWords($words)->str : $str;
	}

	public function getFieldsToPush() {
		$content = $this->parseContent($this->owner->SharedContent(), 25, null);
		$image = $this->owner->AssociatedImage() ? $this->owner->AssociatedImage()->getAbsoluteURL() : null ;
		return array(
			'message' 		=> $content,
			'name' 			=> $this->owner->SharedTitle(),
			'link' 			=> $this->owner->SharedLink(),
			'description'	=> $content,
			'picture'		=> $image
		);
	}

	public function updateCMSFields(FieldList $fields) {
		$conf = SiteConfig::current_site_config();
		if ($conf->TwitterPushUpdates || $conf->FacebookPushUpdates) {
			$fields->addFieldToTab('Root.SocialMedia', new ReadonlyField('LastPostedToSocialMedia', 'Last Posted To Social Media'));
			$fields->addFieldToTab('Root.SocialMedia', new DropdownField('ForceUpdateMode', 'Update Mode', singleton(get_class($this->owner))->dbObject('ForceUpdateMode')->enumValues()));
		}
	}

	public function onAfterPublish() {

		if ($this->owner->ClassName != 'Tweet' && $this->owner->ClassName != 'FBUpdate') {
			
			// define the date window for repost
			$dateWindow = 60 * 60 * 24 * 30; // 30 days
			$lastPost = strtotime($this->owner->LastPostedToSocialMedia);
			$time = time();
			$embargoExpired = false;
			if ($time > $lastPost + $dateWindow)	$embargoExpired = true;
			if (empty($this->justPosted))			$this->justPosted = false;			
			
			if ( ($embargoExpired || !$this->owner->PublicationFBUpdateID || !$this->owner->PublicationTweetID || $this->owner->ForceUpdateMode == 'Force' ) && $this->owner->ForceUpdateMode != 'Block' ) {
				
				// what are we posting to
				$postTo = array();
				if ((!$this->owner->PublicationFBUpdateID || $embargoExpired || $this->owner->ForceUpdateMode == 'Force') && !$this->justPosted)	$postTo[] = 'facebook';
				if ((!$this->owner->PublicationTweetID || $embargoExpired || $this->owner->ForceUpdateMode == 'Force') && !$this->justPosted)		$postTo[] = 'twitter';
				
				// set the last post date
				$this->owner->LastPostedToSocialMedia = date('Y-m-d H:i:s');
				
				// make the posts
				$social = new PostToSocialMedia();
				$ids = $social->sendToSocialMedia($this->getFieldsToPush(), $postTo);

				// update the owner
				if (!empty($ids['facebook']))	$this->owner->PublicationFBUpdateID = $ids['facebook'];
				if (!empty($ids['twitter']))	$this->owner->PublicationTweetID = $ids['twitter'];
				
				// save if we have new data
				$save = false;
				if (in_array('twitter',$postTo) && !empty($ids['twitter'])) {
					$save = true;
					$pubTweet = new PublicationTweet;
					$pubTweet->TweetID = $ids['twitter'];
					$pubTweet->PageID = $this->owner->ID;
					$pubTweet->write();
				}
				if (in_array('facebook',$postTo) && !empty($ids['facebook'])) {
					$save = true;
					$pubUpdate = new PublicationFBUpdate;
					$pubUpdate->FBUpdateID = $ids['facebook'];
					$pubUpdate->PageID = $this->owner->ID;
					$pubUpdate->write();					
				}
				if ($save) {
					$this->justPosted = true;
					$this->owner->write();
					$this->owner->doPublish();
				}
				
			}
		}

		return;

		//parent::onAfterPublish();

	}
	
}
?>