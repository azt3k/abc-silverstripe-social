<?php

// Define path constant
$path = str_replace('\\', '/', __DIR__);
$path_fragments = explode('/', $path);
$dir_name = $path_fragments[count($path_fragments) - 1];
define('ABC_SOCIAL_DIR', $dir_name);

// attach the social extensions to the config and page classes
SiteConfig::add_extension('SocialMediaConfig');
Page::add_extension('SocialMediaPageExtension');

// attach common behaviours to the social updates
FBUpdate::add_extension('SocialUpdatePageExtension');
Tweet::add_extension('SocialUpdatePageExtension');
InstagramUpdate::add_extension('SocialUpdatePageExtension');

// add the embed functionality
ShortcodeParser::get('default')->register('social_embed', array('SocialMediaPageExtension', 'SocialEmbedParser'));
HtmlEditorConfig::get('cms')->enablePlugins(array(
	'social_embed' => '../../../' . ABC_SOCIAL_DIR . '/js/editor-plugin.js'
));
HtmlEditorConfig::get('cms')->addButtonsToLine(2, 'social_embed');
