<?php

/**
 * Description of Tweet
 *
 * @author AzT3k
 */
class FBUpdate extends Page {

    private static $db = array(
        'UpdateID'          => 'Varchar(255)',
        'OriginalCreated'   => 'SS_DateTime',
        'OriginalUpdate'    => 'Text'
    );

    private static $has_one = array(
        'PrimaryImage'      => 'Image',
    );

    private static $defaults = array(
        'holder_class'      => 'FBUpdateHolder',
    );

    /**
     * @config
     */
    private static $conf = array();

    /**
     *  @param  array|object $conf An associative array containing the configuration - see self::$conf for an example
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

    public function updateFromUpdate(stdClass $update, $save = true) {

        $content = empty($update->message)
            ? empty($update->description)
                ? empty($update->story)
                    ? null
                    : $update->story
                : $update->description
            : $update->message;

        if (!$content) {
            echo 'Encountered error with: ' . print_r($update,1);
            return false;
        }
        else {

            $this->Title                = 'Facebook Update - '.$update->id;
            $this->URLSegment           = 'FBUpdate-'.$update->id;
            $this->UpdateID             = $update->id;
            $this->OriginalCreated      = date('Y-m-d H:i:s',strtotime($update->created_time));
            $this->Content              = $content;
            $this->OriginalUpdate       = json_encode($update);

    		$this->findParent();

            return $save ? $this->write() : true ;
        }

    }

    public function getCMSFields() {

        $fields = parent::getCMSFields();

        $lastEditedDateField = new DateTimeField('OriginalCreated');
        $lastEditedDateField->setConfig('showcalendar', true);
        $fields->addFieldToTab('Root.Main', $lastEditedDateField, 'Content');

        $fields->addFieldToTab('Root.Original', new TextareaField('OriginalUpdate'));

        return $fields;

    }

    public function OriginalLink() {
        $id = SiteConfig::current_site_config()->FacebookPageId;
        return 'https://www.facebook.com/' .
            $id .
            '/posts/' .
            str_replace($id . '_', '', $this->UpdateID);
    }

    public function PageTitle() {

        // populate this with the original tweet data
        $data = json_decode($this->OriginalUpdate);

        return $data->from->name.' '.date('jS M', strtotime($this->OriginalCreated));
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
