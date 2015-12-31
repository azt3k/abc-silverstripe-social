<?php

/**
 * @author AzT3k
 */
class PublicationInstagramUpdate extends DataObject
{

    private static $db = array(
        'InstagramUpdateID' => 'Varchar(255)'
    );

    private static $has_one = array(
        'Page' => 'Page'
    );
}
