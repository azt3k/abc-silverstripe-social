<?php

class SocialMediaConfig extends DataExtension {

    private static $db = array(

        'FacebookAppId'                     => 'Varchar(255)',
        'FacebookAppSecret'                 => 'Varchar(255)',
        'FacebookUserId'                    => 'Varchar(255)',
        'FacebookUserAccessToken'           => 'Varchar(255)',
        'FacebookUserAccessTokenExpires'    => 'SS_DateTime',
        'FacebookPageId'                    => 'Varchar(255)',
        'FacebookPageAccessToken'           => 'Varchar(255)',
        'FacebookPageAccessTokenExpires'    => 'SS_DateTime',
        'FacebookPageLink'                  => 'Varchar(255)',
        'FacebookPageFeedType'              => 'Enum(\'feed,posts,tagged,promotable_posts\',\'feed\')',
        'FacebookPushUpdates'               => 'Boolean',
        'FacebookPullUpdates'               => 'Boolean',

        'TwitterConsumerKey'                => 'Varchar(255)',
        'TwitterConsumerSecret'             => 'Varchar(255)',
        'TwitterOAuthToken'                 => 'Varchar(255)',
        'TwitterOAuthTokenExpires'          => 'SS_DateTime',
        'TwitterOAuthSecret'                => 'Varchar(255)',
        'TwitterUsername'                   => 'Varchar(255)',
        'TwitterPushUpdates'                => 'Boolean',
        'TwitterPullUpdates'                => 'Boolean',

        'InstagramApiKey'                   => 'Varchar(255)',
        'InstagramApiSecret'                => 'Varchar(255)',
        'InstagramOAuthToken'               => 'Varchar(255)',
        'InstagramOAuthTokenExpires'        => 'SS_DateTime',
        'InstagramUsername'                 => 'Varchar(255)',
        'InstagramUserId'                   => 'Varchar(255)',
        'InstagramPushUpdates'              => 'Boolean',
        'InstagramPullUpdates'              => 'Boolean',
    );

    private static $has_one = array(
        'DefaultImage'                      => 'Image',
        'DefaultFBUpdateImage'              => 'Image',
        'DefaultTweetImage'                 => 'Image',
        'DefaultInstagramUpdateImage'       => 'Image',
    );

    public function updateCMSFields(FieldList $fields) {


        // ---------
        // Images
        // ---------

        // Image
        $imageField = new UploadField('DefaultImage', 'DefaultImage');
        $imageField->getValidator()->setAllowedExtensions(array('jpg','jpeg','gif','png'));
        $fields->addFieldToTab('Root.Images', $imageField);

        // Image
        $fbImageField = new UploadField('DefaultFBUpdateImage', 'Default Facebook Image');
        $fbImageField->getValidator()->setAllowedExtensions(array('jpg','jpeg','gif','png'));
        $fields->addFieldToTab('Root.Images', $fbImageField);

        // Image
        $tweetImageField = new UploadField('DefaultTweetImage', 'Default Twitter Image');
        $tweetImageField->getValidator()->setAllowedExtensions(array('jpg','jpeg','gif','png'));
        $fields->addFieldToTab('Root.Images', $tweetImageField);

        // Image
        $instagramImageField = new UploadField('DefaultInstagramImage', 'Default Instagram Image');
        $instagramImageField->getValidator()->setAllowedExtensions(array('jpg','jpeg','gif','png'));
        $fields->addFieldToTab('Root.Images', $instagramImageField);

        // ---------
        // Facebook
        // ---------

        $fields->addFieldsToTab('Root.SocialMedia',    new LiteralField('FacebookHeading',    '<h3>Facebook</h3>'));

        // Validate Page Access Token
        $userValid = $pageValid = false;
        try {
            $pageValid = FBAuthenticator::validate_current_conf('page');
        } catch (Exception $e) {
            $pageMsg = $e->getMessage();
            $fields->addFieldsToTab('Root.SocialMedia',    new LiteralField(
                'FacebookBrokenPageConf',
                '<span style="color:red">Your facebook page configuration is broken (' . $pageValid . ')</span>'
            ));
        }

        // Validate User Access Token
        try {
            $userValid = FBAuthenticator::validate_current_conf('user');
        } catch (Exception $e) {
            $userMsg = $e->getMessage();
            $fields->addFieldsToTab('Root.SocialMedia',    new LiteralField(
                'FacebookBrokenUserConf',
                '<p style="color:red">Your facebook user configuration is broken (' . $userMsg . ')</p>'
            ));

        }

        $fields->addFieldsToTab(
            'Root.SocialMedia',
            array(
                new LiteralField('FacebookAppLink', '<p>Manage your apps here: <a href="https://developers.facebook.com/apps/">https://developers.facebook.com/apps/</a></p>'),
                new LiteralField('FacebookIDLink', '<p>Find your Facebook IDs here: <a href="http://findmyfbid.com/">http://findmyfbid.com/</a></p>'),
                new TextField('FacebookAppId', 'Facebook App Id'),
                new TextField('FacebookAppSecret', 'Facebook App Secret'),
                new TextField('FacebookUserId', 'Facebook User Id'),
                new TextField('FacebookPageId', 'Facebook Page Id'),
                new DropdownField('FacebookPageFeedType', 'Facebook Page Feed Type', $this->owner->dbObject('FacebookPageFeedType')->enumValues()),
                new CheckboxField('FacebookPushUpdates', 'Push publication updates to authorised Facebook account'),
                new CheckboxField('FacebookPullUpdates', 'Pull publication updates from authorised Facebook account'),
                new LiteralField('FacebookUserData', '<h4>Facebook User</h4>'),
                new LiteralField('FacebookUserLink', '<p><a target="_blank" href="' . $this->FacebookUserLink(). '">' . $this->owner->FacebookUserId . '</a></p>'),
                new LiteralField('FacebookUserAccessToken', '<p>Facebook User Access Token</p><p>'.($this->owner->FacebookUserAccessToken ? $this->owner->FacebookUserAccessToken.' <a href="/FBAuthenticator/purge" target="_blank">Wipe</a>' : '<a href="/FBAuthenticator" target="_blank">Authenticate</a>').'</p>'),
                new LiteralField('FacebookPageData', '<h4>Facebook Page</h4>'),
                new LiteralField('FacebookPageLink', '<p><a target="_blank" href="' . $this->FacebookPageLink(). '">' . $this->owner->FacebookPageId . '</a></p>'),
                new LiteralField('FacebookPageAccessToken', '<p>Facebook Page Access Token</p><p>'.($this->owner->FacebookPageAccessToken ? $this->owner->FacebookPageAccessToken.' <a href="/FBAuthenticator/purge" target="_blank">Wipe</a>' : '<a href="/FBAuthenticator" target="_blank">Authenticate</a>').'</p>'),
            )
        );

        // ---------
        // Twitter
        // ---------

        $fields->addFieldsToTab('Root.SocialMedia',    new LiteralField('TwitterHeading', '<br><h3>Twitter</h3>'));

        // user
        try {
            $twitterValid = TwitterAuthenticator::validate_current_conf();
        } catch (Exception $e) {
            $twitterMsg = $e->getMessage();
            $fields->addFieldsToTab('Root.SocialMedia',    new LiteralField(
                'TwitterBrokenConf',
                '<p style="color:red">Your twitter configuration is broken</p>'
            ));

        }

        $fields->addFieldsToTab('Root.SocialMedia', new LiteralField('TwitterAppLink', '<p>Manage your apps here: <a href="https://apps.twitter.com/">https://apps.twitter.com/</a></p>'));
        $fields->addFieldsToTab('Root.SocialMedia', new TextField('TwitterConsumerKey', 'Twitter Consumer Key'));
        $fields->addFieldsToTab('Root.SocialMedia', new TextField('TwitterConsumerSecret', 'Twitter Consumer Secret'));

        // only add the username field if we don't have an auth token
        if (!$this->owner->TwitterOAuthToken)
            $fields->addFieldsToTab('Root.SocialMedia', new TextField('TwitterUsername', 'Twitter Username (optional)'));

        $fields->addFieldsToTab('Root.SocialMedia', new CheckboxField('TwitterPushUpdates', 'Push publication updates to authorised Twitter account'));
        $fields->addFieldsToTab('Root.SocialMedia', new CheckboxField('TwitterPullUpdates', 'Pull publication updates from authorised Twitter account'));

        $fields->addFieldsToTab('Root.SocialMedia', new LiteralField('TwitterUserData', '<h4>Twitter User</h4>'));
        $fields->addFieldsToTab('Root.SocialMedia', new LiteralField('TwitterUserLink', '<p><a href="' . $this->TwitterPageLink(). '">' . $this->owner->TwitterUsername . '</a></p>'));
        $fields->addFieldsToTab('Root.SocialMedia', new LiteralField('TwitterOAuthToken', '<p>Twitter OAuth Token</p><p>'.($this->owner->TwitterOAuthToken ? $this->owner->TwitterOAuthToken.' <a href="/TwitterAuthenticator?wipe=1" target="_blank">Wipe</a>' : '<a href="/TwitterAuthenticator?start=1" target="_blank">Authenticate</a>').'</p>'));
        $fields->addFieldsToTab('Root.SocialMedia', new LiteralField('TwitterOAuthSecret', '<p>Twitter OAuth Secret</p><p>'.($this->owner->TwitterOAuthSecret ? $this->owner->TwitterOAuthSecret.' <a href="/TwitterAuthenticator?wipe=1" target="_blank">Wipe</a>' : '<a href="/TwitterAuthenticator?start=1" target="_blank">Authenticate</a>').'</p>'));

        // ---------
        // Instagram
        // ---------

        $fields->addFieldsToTab('Root.SocialMedia',    new LiteralField('InstagramHeading', '<br><h3>Instagram</h3>'));

        // user
        try {
            $instagramValid = InstagramAuthenticator::validate_current_conf();
        } catch (Exception $e) {
            $instagramMsg = $e->getMessage();
            $fields->addFieldsToTab('Root.SocialMedia',    new LiteralField(
                'InstagramBrokenConf',
                '<p style="color:red">Your instagram configuration is broken</p>'
            ));

        }

        $fields->addFieldsToTab('Root.SocialMedia', new LiteralField('InstagramAppLink', '<p>Manage your apps here: <a href="http://instagr.am/developer/">http://instagr.am/developer/</a></p>'));
        $fields->addFieldsToTab('Root.SocialMedia', new TextField('InstagramApiKey', 'Instagram Client ID'));
        $fields->addFieldsToTab('Root.SocialMedia', new TextField('InstagramApiSecret', 'Instagram Client Secret'));

        // only add the username field if we don't have an auth token
        if (!$this->owner->InstagramOAuthToken) {
            $fields->addFieldsToTab('Root.SocialMedia', new TextField('InstagramUsername', 'Instagram Username'));
            $fields->addFieldsToTab('Root.SocialMedia', new TextField('InstagramUserId', 'Instagram User ID'));
        }

        $fields->addFieldsToTab('Root.SocialMedia', new CheckboxField('InstagramPushUpdates', 'Push publication updates to authorised Instagram account'));
        $fields->addFieldsToTab('Root.SocialMedia', new CheckboxField('InstagramPullUpdates', 'Pull publication updates from authorised Instagram account'));

        $fields->addFieldsToTab('Root.SocialMedia', new LiteralField('InstagramUserData', '<h4>Instagram User</h4>'));
        $fields->addFieldsToTab('Root.SocialMedia', new LiteralField('InstagramUserLink', '<p><a target="_blank" href="' . $this->InstagramPageLink(). '">' . $this->owner->InstagramUsername . '(' . $this->owner->InstagramUserId . ')</a></p>'));
        $fields->addFieldsToTab('Root.SocialMedia', new LiteralField('InstagramOAuthToken', '<p>OAuth Token</p><p>'.($this->owner->InstagramOAuthToken ? $this->owner->InstagramOAuthToken.' <a href="/InstagramAuthenticator?wipe=1" target="_blank">Wipe</a>' : '<a href="/InstagramAuthenticator?start=1" target="_blank">Authenticate</a>').'</p>'));

        return $fields;

    }

    public function InstagramPageLink() {
        return SocialHelper::link($this->owner->InstagramUsername, 'instagram');
    }

    public function TwitterPageLink() {
        return SocialHelper::link($this->owner->TwitterUsername, 'twitter');
    }

    public function FacebookUserLink() {
        return SocialHelper::link($this->owner->FacebookUserId, 'facebook');
    }

    public function FacebookPageLink() {
        return SocialHelper::link($this->owner->FacebookPageId, 'facebook', 'page');
    }
}
