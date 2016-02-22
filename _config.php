<?php

SiteConfig::add_extension('SocialMediaConfig');
Page::add_extension('SocialMediaPageExtension');
FBUpdate::add_extension('SocialUpdatePageExtension');
Tweet::add_extension('SocialUpdatePageExtension');
InstagramUpdate::add_extension('SocialUpdatePageExtension');
ShortcodeParser::get('default')
	->register('socal_embed', array('SocialMediaPageExtension', 'SocialEmbedParser'));
