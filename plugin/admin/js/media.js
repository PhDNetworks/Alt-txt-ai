/**
 * Alt Text AI — Single image generation in media modal.
 */
(function ($) {
	'use strict';

	$(document).on('click', '.atai-generate-btn', function (e) {
		e.preventDefault();

		var $btn    = $(this);
		var $status = $btn.siblings('.atai-status');
		var id      = $btn.data('attachment-id');

		$btn.prop('disabled', true).text('Generating…');
		$status.text('').removeClass('atai-error atai-success');

		$.ajax({
			url:  atai.ajax_url,
			type: 'POST',
			data: {
				action:        'atai_generate_single',
				nonce:         atai.nonce,
				attachment_id: id
			},
			success: function (res) {
				if (res.success) {
					$status.addClass('atai-success').text('✓ ' + res.data.alt_text);

					// Update the alt text field in the attachment details modal
					var $altField = $btn.closest('.attachment-details, .compat-item')
						.closest('.attachment-details, .media-sidebar, .attachment-info')
						.find('[data-setting="alt"] input, #attachment-details-two-column-alt-text, input[name="attachments[' + id + '][_wp_attachment_image_alt]"]');

					if ($altField.length) {
						$altField.val(res.data.alt_text).trigger('change');
					}

					// Also try the Backbone model approach for the media modal
					if (wp && wp.media && wp.media.frame) {
						var selection = wp.media.frame.state().get('selection');
						if (selection) {
							var model = selection.get(id);
							if (model) {
								model.set('alt', res.data.alt_text);
							}
						}
					}
				} else {
					$status.addClass('atai-error').text('✗ ' + (res.data.message || 'Unknown error'));
				}
			},
			error: function () {
				$status.addClass('atai-error').text('✗ Network error. Please try again.');
			},
			complete: function () {
				$btn.prop('disabled', false).text('✨ Generate Alt Text with AI');
			}
		});
	});
})(jQuery);
