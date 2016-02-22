(function($) {
	$(function() {
		$('form').on('submit', function(e) {

			e.preventDefault();

			// generate the token
			var token = '[social_embed,url="' + $('#url').val() + '"]';

			// insert the content
			tinyMCEPopup.execCommand('mceInsertContent', false, token);

			// Refocus in window
			if (tinyMCEPopup.isWindow) window.focus();

			// close the window etc
			tinyMCEPopup.editor.focus();
			tinyMCEPopup.close();
		});
	});
})(jQuery);
