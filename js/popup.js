(function($) {
    $(function() {

        // preview
        $('#url').on('keyup change paste blur', function(e) {

            // get the element
            var $el = $(this);

            // make a get request to the html endpoint
            $.get('/abc-social-admin/htmlfragment?nocache=1&pUrl=' + encodeURI($el.val()), function(data) {
                $('#preview').html(data);
            });
        });

        $('form').on('submit', function(e) {

            // dont submit
            e.preventDefault();

            // get the fully parsed piece of html
            $.get('/abc-social-admin/htmlfragment?pUrl=' + encodeURI($('#url').val()), function(data) {

                // generate the token and the replacement html
                var token = '[social_embed,url="' + $('#url').val() + '"]',
                    // See editor plugin - it parses out <div class="social-embed"></div> with the short code
                    // we wrap it in a secondary div otherwise jQuery freak when it tries to parse the raw shortcode
                    data =  '<div>' +
                                '<div class="social-embed" data-shortcode="' + token.replace(/"/g, '\'') + '">' +
                                    data +
                                '</div>' +
                            '</div>' +
                            '<p>&nbsp;</p>'; // makes it so that they user can escape out of the injected content

                // insert the content
                tinyMCEPopup.execCommand('mceInsertContent', false, data);

                // Refocus in window
                if (tinyMCEPopup.isWindow) window.focus();

                // close the window etc
                tinyMCEPopup.editor.focus();
                tinyMCEPopup.close();
            });
        });
    });
})(jQuery);
