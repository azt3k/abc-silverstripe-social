(function($) {
    tinymce.create('tinymce.plugins.social_embed', {

        init : function(ed, url) {

            var self = this;

            // inject the widget scripts into the editor document
            ed.onBeforeSetContent.add(function(ed, o) {
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
                var $content = $('<div>' + o.content + '</div>'), $twitterFrames;

                // transform the embeds back to short codes
                $content.find('.social-embed').each(function() {
                    var $el = $(this);
                    var shortCode = $el.attr('data-shortcode').replace(/'/g, '"');
                    $el.after(shortCode);
                    $el.remove();
                });

                // do some cleanup
                $content.find('#rufous-sandbox').closest('p').remove();
                $content.find('#rufous-sandbox').remove();
                $content.find('#fb-root').remove();
                $twitterFrames = $content.find("iframe[title='Twitter settings iframe']");
                $twitterFrames.closest('p').remove();
                $twitterFrames.remove();

                // get the content string
                var content = $content.html();

                // make sure we don't have a bung p tag
                if (content.replace(/^\s+|\s+$/g, '') == '<p>&nbsp;</p>') content = '';

                // set the content;
                o.content = content;
            });

            // replace the short code with markup on load
            // works alright for twitter, but fb not so much - insta - untested
            ed.onSetContent.add(function(ed, o) {

                // parse the content
                var re = /\[social_embed,url="([^"]+)"\]/gi,
                    m = ed.getContent().match(re);

                if (m) {

                    // handle m
                    var mCount = m.length,
                        rCount = 0,
                        rMap = {},
                        i;

                    // find all the matched
                    for (i=0; i < m.length; i++) {

                        // extract the match data
                        var mCur = m[i],
                            m2 = /url="([^"]+)"/.exec(mCur),
                            url = m2[1];

                        // get the fully parsed piece of html
                        $.get('/abc-social-admin/htmlfragment?pUrl=' + url, function(mCur, url, data, textStatus, jqXHR) {

                            // increment the request counter
                            rCount++;

                            // generate the token and the replacement html
                            var ii,
                                token = '[social_embed,url="' + url + '"]',
                                data =  '<div class="social-embed" data-shortcode="' + token.replace(/"/g, '\'') + '">' +
                                            data +
                                        '</div>';

                            // store the replacement data
                            rMap[mCur] = data;

                            // do the replacement once we get back all of the requests
                            if (rCount == mCount) {
                                var cont = ed.getContent();
                                for (ii=0; ii < m.length; ii++) {
                                    var key = m[ii];
                                    cont = cont.replace(key, rMap[key]);
                                }
                                ed.setContent(cont);
                            }

                        }.bind(null, mCur, url));
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
