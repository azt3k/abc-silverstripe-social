<?php

/**
 * @author AzT3k
 */
class PurgeTwitter extends BuildTask {

    public function init() {

        parent::init();

        if (!Director::is_cli() && !Permission::check("ADMIN") && $_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR']) {
            return Security::permissionFailure();
        }

    }

    public function process() {
        $this->init();
        $this->run();
    }

    public function run($request = null) {
        // eol
        $eol = php_sapi_name() == 'cli' ? "\n" : "<br>\n";

        // output
        echo $eol . $eol . 'Purging...' . $eol . $eol;
        flush();
        @ob_flush();

        foreach(Tweet::get() as $page) {
            echo "Deleting " . $page->Title . $eol;
            $page->delete();
        }

        foreach(Versioned::get_by_stage('Tweet', 'Stage') as $page) {
            echo "Deleting From Stage: " . $page->Title . $eol;
            $page->deleteFromStage('Stage');
        }

        foreach(Versioned::get_by_stage('Tweet', 'Live') as $page) {
            echo "Deleting From Live: " . $page->Title . $eol;
            $page->deleteFromStage('Live');
        }

    }
}
