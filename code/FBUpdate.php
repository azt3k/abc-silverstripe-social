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
class FBUpdate extends Page {

	private static $db = array(
		'UpdateID'			=> 'Varchar(255)',
		'UpdateCreated'		=> 'SS_DateTime',
		'OriginalUpdate'	=> 'Text'
	);

	public function updateFromUpdate(stdClass $update, $save = true) {

		$content = empty($update->message)
			? empty($update->description) 
				? $update->story
				: $update->description
			: $update->message;

		if (!$content) die(print_r($update,1));

		$this->Title			= 'Facebook Update - '.$update->id;
		$this->URLSegment		= 'FBUpdate-'.$update->id;
		$this->UpdateID			= $update->id;
		$this->UpdateCreated	= date('Y-m-d H:i:s',strtotime($update->created_time));
		$this->Content			= $content;
		$this->OriginalUpdate	= json_encode($update);

		return $save ? $this->write() : true ;

	}

	public function PageTitle() {

		// populate this with the original tweet data
		$data = json_decode($this->OriginalUpdate);

		return $data->from->name.' '.date('jS M', strtotime($this->UpdateCreated));
	}

	/**
	 * Adds all the tweet fields on to this object rather than just the ones we have seperated out
	 *
	 * @return \Tweet
	 */
	public function expandUpdateData(stdClass $update = null){

		$data = $tweet ? json_decode(json_encode($update),true) : json_decode($this->OriginalUpdate,true) ;

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

class FBUpdate_Controller extends Page_Controller {

}
