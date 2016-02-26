(function($) {
    tinymce.create('tinymce.plugins.social_embed', {

        init : function(ed, url) {

            var self = this;

            ed.onBeforeSetContent.add(function(ed, o) {
                // inject some scripts
                var head = ed.contentDocument.getElementsByTagName('head')[0],
                    s = [
                        '//platform.twitter.com/widgets.js',
                        '//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.3',
                        '//platform.instagram.com/en_US/embeds.js'
                    ];
                for (var i = 0; i < s.length; i++) {
                    var el = ed.contentDocument.createElement('script');
                    head.appendChild(el);
                    el.src = s[i];
                }
            });

            // Register commands
            ed.addCommand('mceInsertSocialEmbed', function() {
                ed.windowManager.open({
                    title: 'Social Embed',
                    url: '/abc-social-admin',
                    height: 768,
                    width: 520,
                });
            });

            // add the button
            ed.addButton ('social_embed', {
                'title' : 'Social Embed',
                'image' : url + '/../img/icon.png',
                'cmd': 'mceInsertSocialEmbed',
            });

            // replace the markup with the short code on save
            // this seems to happen a lot - as in more often than just on save
            ed.onSaveContent.add(function(ed, o) {
                var $content = $('<div>' + o.content + '</div>');

                // transform the embeds back to short codes
                $content.find('.social-embed').each(function() {
                    var $el = $(this);
                    var shortCode = $el.attr('data-shortcode').replace(/'/g, '"');
                    $el.after(shortCode);
                    $el.remove();
                });

                // do some cleanup
                $content.find('#rufous-sandbox').remove();
                $content.find('iframe#fb').remove();
                $content.find('#fb_xdm_frame_http').remove();
                $content.find('#fb_xdm_frame_https').remove();
                $content.find('#fb-root').remove();

                // alert($('<div />').append($content).html());
                o.content = $content.html();
            });

            // replace the short code with markup on load
            // works alright for twitter, but fb not so much - insta - untested
            ed.onSetContent.add(function(ed, o) {

                // parse the content
                var re = /\[social_embed,url="([^"]+)"\]/gi,
                    m = ed.getContent().match(re),
                    i;

                if (m) {

                    // find all the matched
                    for (i=0; i < m.length; i++) {

                        // extract the match data
                        var mCur = m[i],
                            m2 = /url="([^"]+)"/.exec(mCur);
                            url = m2[1];

                        console.log(url);

                        // get the fully parsed piece of html
                        $.get('/abc-social-admin/htmlfragment?pUrl=' + url, function(data) {

                            // console.log(data);

                            // generate the token and the replacement html
                            var token = '[social_embed,url="' + url + '"]',
                                data =  '<div class="social-embed" data-shortcode="' + token.replace(/"/g, '\'') + '">' +
                                            data +
                                        '</div>';

                            // replace
                            // this seems to create multiple requests as we are setting content in onSetContent callback
                            ed.setContent(ed.getContent().replace(mCur, data));

                            // // call the parsers
                            // $(ed.contentWindow).on('load', function() {
                            //     ed.contentWindow.twttr.widgets.load(ed.getContent());
                            // });
                        });
                    }
                }
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
})(jQuery);
