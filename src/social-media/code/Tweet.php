<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Tweet
 *
 * @author AzT3k
 */
class Tweet extends Page {
	
	private static $db = array(
		'TweetID'		=> 'Varchar(255)',
		'TweetCreated'	=> 'SS_DateTime',
		'OriginalTweet'	=> 'Text'
	);
	
	public function updateFromTweet(stdClass $tweet, $save = true) {
		
		$this->Title			= 'Tweet - '.$tweet->id_str;
		$this->URLSegment		= 'Tweet-'.$tweet->id_str;		
		$this->TweetID			= $tweet->id_str;
		$this->TweetCreated		= date('Y-m-d H:i:s',strtotime($tweet->created_at));
		$this->Content			= $tweet->text;
		$this->OriginalTweet	= json_encode($tweet);
		
		return $save ? $this->write() : true ;
		
	}
	
	
	public function PageTitle() {
		
		// populate this with the original tweet data
		$data = json_decode($this->OriginalTweet);
		
		return $data->user->name.' '.date('jS M', strtotime($this->TweetCreated));
	}		
	
	/**
	 * Adds all the tweet fields on to this object rather than just the ones we have seperated out
	 * 
	 * @return \Tweet
	 */
	public function expandTweetData(stdClass $tweet = null){
		
		$data = $tweet ? json_decode(json_encode($tweet),true) : json_decode($this->OriginalTweet,true) ;
		
		$this->customise($data);
		
		return $this;
	}
	
	/**
	 * Override canPublish check to allow publish from CLI
	 * @param type $member
	 * @return boolean
	 */
	public function canPublish($member = null) {
		if (Director::is_cli()) return true;
		else return parent::canPublish($member); 
	}	
			
}

class Tweet_Controller extends Page_Controller {	

}

