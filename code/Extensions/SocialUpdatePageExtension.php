<?php

class SocialUpdatePageExtension extends DataExtension {

    public function UpdateType() {
        switch ($this->owner->ClassName) {
            case 'Tweet':           return 'Twitter';
            case 'FBUpdate':        return 'Facebook';
            case 'InstagramUpdate': return 'Instagram';
        }
    }

    public function UpdateImage() {

        $conf = SiteConfig::current_site_config();

        switch ($this->owner->ClassName) {
            case 'Tweet':
                return $this->owner->PrimaryImageID
                    ? $this->owner->PrimaryImage()
                    : $conf->DefaultTweetImage();
            case 'FBUpdate':
                return $this->owner->PrimaryImageID
                    ? $this->owner->PrimaryImage()
                    : $conf->DefaultFBUpdateImage();
            case 'InstagramUpdate':
                return $this->owner->PrimaryImageID
                    ? $this->owner->PrimaryImage()
                    : $conf->DefaultInstagramUpdateImage();
        }
    }
}
