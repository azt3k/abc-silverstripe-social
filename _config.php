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

// allow script tags
// maybe we could try using requirements and stripping the script tags
HtmlEditorConfig::get('cms')
    ->setOption(
        'extended_valid_elements',
        'img[class|src|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name|usemap|data*],' .
        'iframe[src|name|width|height|align|frameborder|marginwidth|marginheight|scrolling],' .
        'object[width|height|data|type],' .
        'param[name|value],' .
        'map[class|name|id],' .
        'area[shape|coords|href|target|alt],ol[class|start],' .
        'script[type|src|lang|async|charset]'
    );
