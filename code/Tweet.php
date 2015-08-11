<?php

/**
 * Description of Tweet
 *
 * @author AzT3k
 */
class Tweet extends Page {

    private static $db = array(
        'TweetID'           => 'Varchar(255)',
        'OriginalCreated'   => 'SS_DateTime',
        'OriginalTweet'     => 'Text'
    );

    private static $has_one = array(
        'PrimaryImage'      => 'Image',
    );

    private static $defaults = array(
        'holder_class'      => 'TweetHolder',
    );

    /**
     * @config
     */
    private static $conf = array();

	/**
     *  @param  array|object $conf An associative array containing the configuration - see static::$conf for an example
     *  @return void
     */
    public static function set_conf($conf) {
        $conf = (array) $conf;
        static::$conf = array_merge(static::$conf, $conf);
    }

    /**
     *  @return stdClass
     */
    public static function get_conf() {
        return (object) array_merge(static::$defaults, static::$conf);
    }

    /**
     * @return void
     */
    protected static function set_conf_from_yaml() {
        $conf = (array) Config::inst()->get(__CLASS__, 'conf');
        if (!empty($conf))
            static::$conf = array_merge(static::$conf, $conf);
    }

    /**
     *  @return void
     */
    protected function configure() {
        static::set_conf_from_yaml();
    }

    public function __construct($record = null, $isSingleton = false, $model = null) {
        parent::__construct($record, $isSingleton, $model);
        $this->configure();
    }

    public function onBeforeWrite() {
        parent::onBeforeWrite();
        $this->findParent();
    }

	public function findParent() {
        if (!$this->ParentID) {
            $conf = static::get_conf();
            if (!$parent = DataObject::get_one($conf->holder_class)) {
                $parent = new $conf->holder_class;
                $parent->write();
                $parent->doPublish();
            }
            $this->ParentID = $parent->ID;
        }
    }

    public function updateFromTweet(stdClass $tweet, $save = true) {

        if (!empty($tweet->entities->media[0])) {

            // extract media
            $media = $tweet->entities->media[0];

            // only process photos
            if ($media->type == 'photo') {

                // get url
                $img = $media->media_url;

                // sanity check
                if (!is_dir(ASSETS_PATH . '/social-updates/')) mkdir(ASSETS_PATH . '/social-updates/');

                // pull down image
                $pi = pathinfo($img);
                $absPath = ASSETS_PATH . '/social-updates/' . $pi['basename'];
                $relPath = ASSETS_DIR . '/social-updates/' . $pi['basename'];
                if (!file_exists($absPath)) {
                    $imgData = file_get_contents($img);
                    file_put_contents($absPath, $imgData);
                }

                // create image record
                $image = new Image;
                $image->setFilename($relPath);
                $image->write();

                // associate
                $this->PrimaryImageID = $image->ID;

            }
        }

        $this->Title            = 'Tweet - '.$tweet->id_str;
        $this->URLSegment       = 'Tweet-'.$tweet->id_str;
        $this->TweetID          = $tweet->id_str;
        $this->OriginalCreated  = date('Y-m-d H:i:s',strtotime($tweet->created_at));
        $this->Content          = $tweet->text;
        $this->OriginalTweet    = json_encode($tweet);
		$this->findParent();

        return $save ? $this->write() : true ;

    }

    public function getCMSFields() {

        $fields = parent::getCMSFields();

        $lastEditedDateField = new DateTimeField('OriginalCreated');
        $lastEditedDateField->setConfig('showcalendar', true);
        $fields->addFieldToTab('Root.Main', $lastEditedDateField, 'Content');

        $fields->addFieldToTab('Root.Original', new LiteralField('OriginalTweet', str_replace("\n", '<br>', print_r($this->OriginalTweet,1))));

        return $fields;

    }

    public function OriginalLink() {
        return 'https://twitter.com/' .
            SiteConfig::current_site_config()->TwitterUsername .
            '/status/' .
            $this->TweetID;
    }

    /**
     * Adds all the tweet fields on to this object rather than just the ones we have seperated out
     *
     * @return \Tweet
     */
    public function expandTweetData(stdClass $tweet = null){
        $data = $tweet ? json_decode(json_encode($tweet),true) : json_decode($this->OriginalTweet,true);
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
