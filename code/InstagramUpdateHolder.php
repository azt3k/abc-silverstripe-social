<?php

/**
 * @author AzT3k
 */
class InstagramHolder extends Page
{

    private static $can_be_root = true;
    private static $allowed_children = array(
        'Instagram'
    );
}

class InstagramHolder_Controller extends Page_Controller
{
}
