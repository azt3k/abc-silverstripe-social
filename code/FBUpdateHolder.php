<?php

/**
 * @author AzT3k
 */
class FBUpdateHolder extends Page
{

    private static $can_be_root = true;
    private static $allowed_children = array(
        'FBUpdate'
    );
}

class FBUpdateHolder_Controller extends Page_Controller
{
}
