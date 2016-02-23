<?php

use Facebook\Facebook;

/**
 * Facebook images are not always instantly available so this is a rety job that should look for any
 */
class RetrySyncFacebookImages extends BuildTask implements CronTask {

    protected static $conf_instance;
    protected $conf;

    public function __construct() {
        $this->conf = $this->getConf();
        parent::__construct();
    }

    public function getSchedule() {
        return "*/15 * * * *";
    }

    public function getConf() {
        if (!static::$conf_instance) static::$conf_instance = SiteConfig::current_site_config();
        return static::$conf_instance;
    }

    /**
     * Initialise the script
     * @return void
     */
    function init() {

        if (method_exists(parent,'init')) parent::init();

        if (!Director::is_cli() && !Permission::check("ADMIN") && $_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR']) {
            return Security::permissionFailure();
        }

        if (!$this->conf) $this->__construct();

    }

    /**
     * adpacter for cron task
     * @return [type] [description]
     */
    public function process() {
        $this->init();
        $this->run();
    }

    function run($request) {

        // eol
        $eol = php_sapi_name() == 'cli' ? "\n" : "<br>\n";

        // output
        echo $eol . $eol . 'Syncing' . $eol . $eol;
        flush();
        @@ob_flush();

        if (!$this->conf->FacebookPullUpdates) {
            echo 'Sync disabled' . $eol . $eol;
            return;
        }

        // find any updates that are less than a week old with no image
        $updates = FBUpdate::get()
            ->where('
                UNIX_TIMESTAMP(OriginalCreated) > ' . (time() - (60 * 60 * 24 * 14)) . ' AND
                (Page.PrimaryImageID IS NULL OR Page.PrimaryImageID = \'\' OR Page.PrimaryImageID = 0)
            ');

        // helpful output
        echo 'Processing ' . $updates->count() . ' updates...' . $eol . $eol;

        // loop the loop
        foreach ($updates as $update) {
            $update->updateFromUpdate((object) json_decode($update->OriginalUpdate));
        }

    }
}
