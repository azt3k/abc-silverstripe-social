<?php

/**
 * @author AzT3k
 */
class PurgeInstagram extends BuildTask
{

    public function init()
    {
        parent::init();

        if (!Director::is_cli() && !Permission::check("ADMIN") && $_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR']) {
            return Security::permissionFailure();
        }
    }

    public function process()
    {
        $this->init();
        $this->run();
    }

    public function run($request)
    {

        // output
        echo "<br />\n<br />\nPurging...<br />\n<br />\n";
        flush();
        ob_flush();

        foreach (InstagramUpdate::get() as $page) {
            echo "Deleting " . $page->Title . "\n";
            $page->delete();
        }

        foreach (Versioned::get_by_stage('InstagramUpdate', 'Stage') as $page) {
            echo "Deleting From Stage: " . $page->Title . "\n";
            $page->deleteFromStage('Stage');
        }

        foreach (Versioned::get_by_stage('InstagramUpdate', 'Live') as $page) {
            echo "Deleting From Live: " . $page->Title . "\n";
            $page->deleteFromStage('Live');
        }
    }
}
