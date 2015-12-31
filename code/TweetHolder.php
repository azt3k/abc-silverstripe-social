<?php

/**
 * Description of Tweet
 *
 * @author AzT3k
 */
class TweetHolder extends Page
{

    private static $can_be_root = true;
    private static $allowed_children = array(
        'Tweet'
    );
}

class TweetHolder_Controller extends Page_Controller
{
}
