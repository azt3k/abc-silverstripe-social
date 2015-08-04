<?php
class PublicationFBUpdate extends DataObject {

	private static $db = array(
		'FBUpdateID'	=> 'Varchar(255)'
	);

	private static $has_one = array(
		'Page'		=> 'Page'
	);
}
