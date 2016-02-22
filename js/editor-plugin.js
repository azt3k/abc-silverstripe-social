(function() {
    tinymce.create('tinymce.plugins.social_embed', {

        init : function(ed, url) {

            var self = this;

            // Register commands
            ed.addCommand('mceInsertSocialEmbed', function() {
                ed.windowManager.open({
                    title: 'Social Embed',
                    url: url + '/js/editor-plugin.html',
                    body: [
                        {type: 'textbox', name: 'url', label: 'URL'}
                    ],
                    onsubmit: function(e) {
                        // Insert content when the window form is submitted
                        editor.insertContent('[social_embed,url=' + e.data.url + ']');
                    }
                });
            });

            // add the button
            ed.addButton ('social_embed', {
                'title' : 'Social Embed',
                'image' : url + '/../img/icon.png',
                'cmd': 'mceInsertSocialEmbed',
            });
        },

        getInfo : function() {
            return {
                longname  : 'Social Embed',
                author    : 'Me',
                authorurl : 'http://github.com/azt3k',
                infourl   : 'http://github.com/azt3k/abc-silverstripe-social',
                version   : "0.1"
            };
        }
    });

    tinymce.PluginManager.add('social_embed', tinymce.plugins.social_embed);
})();
