/**
 * Alt Text AI — Bulk processing sequential handler.
 */
(function ($) {
	'use strict';

	// Only run if we have a batch to process
	if (typeof window.ataiBulkBatchId === 'undefined') {
		return;
	}

	var batchId  = window.ataiBulkBatchId;
	var total    = window.ataiBulkCount;
	var index    = 0;
	var success  = 0;
	var errors   = 0;
	var lastUsage = null;

	function updateProgress() {
		var pct = total > 0 ? Math.round(((index) / total) * 100) : 0;
		$('#atai-bulk-status').text('Processing ' + (index + 1) + '/' + total + '…');
		$('#atai-bulk-bar').css('width', pct + '%');
	}

	function appendLog(msg) {
		var $log = $('#atai-bulk-log');
		$log.prepend('<div>' + msg + '</div>');
		// Keep only last 10 entries visible
		$log.children().slice(10).remove();
	}

	function complete() {
		$('#atai-bulk-progress').hide();
		var summary = success + ' images updated, ' + errors + ' errors.';
		if (lastUsage && lastUsage.used !== undefined) {
			summary += ' Usage: ' + lastUsage.used + '/' + lastUsage.limit + ' this month.';
		}
		$('#atai-bulk-summary').text(summary);
		$('#atai-bulk-complete').show();
	}

	function processNext() {
		if (index >= total) {
			complete();
			return;
		}

		updateProgress();

		$.ajax({
			url:  ataiBulk.ajax_url,
			type: 'POST',
			data: {
				action:   'atai_bulk_next',
				nonce:    ataiBulk.nonce,
				batch_id: batchId,
				index:    index
			},
			success: function (res) {
				if (!res.success) {
					appendLog('✗ Error: ' + (res.data ? res.data.message : 'Unknown'));
					errors++;
					complete();
					return;
				}

				var d = res.data;

				if (d.done) {
					complete();
					return;
				}

				if (d.usage) {
					lastUsage = d.usage;
				}

				var title = d.title || ('#' + d.attachment_id);

				if (d.error) {
					appendLog('✗ ' + title + ': ' + d.error);
					errors++;

					// Stop on quota exceeded
					if (d.error.indexOf('quota') !== -1) {
						appendLog('⚠ Quota exceeded — stopping.');
						complete();
						return;
					}
				} else {
					appendLog('✓ ' + title + ': ' + d.alt_text);
					success++;
				}

				index++;
				processNext();
			},
			error: function () {
				appendLog('✗ Network error at image ' + (index + 1));
				errors++;
				index++;
				processNext();
			}
		});
	}

	// Start processing on page load
	$(document).ready(function () {
		processNext();
	});

})(jQuery);
