<?php

/**
 * @author AzT3k
 */
class PublicationTweet extends DataObject {

    private static $db = array(
        'TweetID' => 'Varchar(255)'
    );

    private static $has_one = array(
        'Page' => 'Page'
    );

}
