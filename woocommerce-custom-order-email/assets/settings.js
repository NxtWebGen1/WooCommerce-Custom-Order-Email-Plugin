/**
 * WooCommerce Custom Order Email - Email settings screen enhancements.
 * Placeholder chips, copy-between-languages, live preview, test send.
 */
(function ($) {
	'use strict';

	if (typeof wcCustomOrderEmailAdmin === 'undefined') {
		return;
	}

	var cfg = wcCustomOrderEmailAdmin;

	function getFieldValue(fieldId) {
		var editor = window.tinymce && window.tinymce.get(fieldId);
		if (editor && !editor.isHidden()) {
			return editor.getContent();
		}
		var el = document.getElementById(fieldId);
		return el ? el.value : '';
	}

	function setFieldValue(fieldId, value) {
		var editor = window.tinymce && window.tinymce.get(fieldId);
		if (editor) {
			editor.setContent(value);
		}
		var el = document.getElementById(fieldId);
		if (el) {
			el.value = value;
		}
	}

	function insertAtCursor(el, text) {
		if (typeof el.selectionStart === 'number') {
			var start = el.selectionStart, end = el.selectionEnd;
			el.value = el.value.substring(0, start) + text + el.value.substring(end);
			el.selectionStart = el.selectionEnd = start + text.length;
		} else {
			el.value += text;
		}
		el.focus();
	}

	function insertIntoField(fieldId, isWysiwyg, text) {
		if (isWysiwyg) {
			var editor = window.tinymce && window.tinymce.get(fieldId);
			if (editor && !editor.isHidden()) {
				editor.execCommand('mceInsertContent', false, text);
				return;
			}
		}
		var el = document.getElementById(fieldId);
		if (el) {
			insertAtCursor(el, text);
		}
	}

	function replaceSample(text) {
		$.each(cfg.sampleData, function (placeholder, value) {
			text = text.split(placeholder).join(value);
		});
		return text;
	}

	/* Placeholder chips */
	function initPlaceholderChips() {
		$('.wc-custom-order-email-placeholder-chips').each(function () {
			var $wrap = $(this);
			var targetId = $wrap.data('target');
			var type = $wrap.data('target-type');
			var placeholders = 'wysiwyg' === type ? cfg.htmlPlaceholders : cfg.textPlaceholders;

			$.each(placeholders, function (i, token) {
				$('<button type="button" class="button button-small wc-custom-order-email-chip"></button>')
					.text(token)
					.on('click', function (e) {
						e.preventDefault();
						insertIntoField(targetId, 'wysiwyg' === type, token);
					})
					.appendTo($wrap);
			});
		});
	}

	/* Copy content between languages */
	function initCopyToolbar() {
		var $table = $('table.form-table').first();
		if ($table.length === 0) {
			return;
		}

		var $options = '';
		$.each(cfg.languages, function (code, label) {
			$options += '<option value="' + code + '">' + label + '</option>';
		});

		var $toolbar = $(
			'<div class="wc-custom-order-email-copy-toolbar">' +
			'<strong>' + cfg.i18n.copyLanguage + '</strong> ' +
			'<label>' + cfg.i18n.from + ' <select class="wcoe-copy-from">' + $options + '</select></label> ' +
			'<label>' + cfg.i18n.to + ' <select class="wcoe-copy-to">' + $options + '</select></label> ' +
			'<button type="button" class="button wcoe-copy-btn">' + cfg.i18n.copyButton + '</button>' +
			'</div>'
		);

		$table.before($toolbar);

		$toolbar.find('.wcoe-copy-btn').on('click', function (e) {
			e.preventDefault();

			var from = $toolbar.find('.wcoe-copy-from').val();
			var to = $toolbar.find('.wcoe-copy-to').val();

			if (from === to) {
				return;
			}

			if (!window.confirm(cfg.i18n.copyConfirm)) {
				return;
			}

			$.each(['subject_', 'heading_'], function (i, prefix) {
				var fromId = cfg.fieldPrefix + prefix + from;
				var toId = cfg.fieldPrefix + prefix + to;
				setFieldValue(toId, getFieldValue(fromId));
			});

			var fromContentId = cfg.fieldPrefix + 'content_' + from;
			var toContentId = cfg.fieldPrefix + 'content_' + to;
			setFieldValue(toContentId, getFieldValue(fromContentId));
		});
	}

	/* Live preview modal (client-side, sample data only) */
	function showPreviewModal(subject, content) {
		var $overlay = $('<div class="wc-custom-order-email-modal-overlay"></div>');
		var $modal = $('<div class="wc-custom-order-email-modal"></div>');
		var $close = $('<button type="button" class="button wc-custom-order-email-modal-close"></button>').text(cfg.i18n.close);
		var $iframe = $('<iframe class="wc-custom-order-email-modal-iframe"></iframe>');

		$modal.append('<h2>' + cfg.i18n.previewTitle + '</h2>');
		$modal.append('<p><strong>' + $('<div>').text(subject).html() + '</strong></p>');
		$modal.append($iframe);
		$modal.append($close);
		$overlay.append($modal);
		$('body').append($overlay);

		var doc = $iframe[0].contentWindow.document;
		doc.open();
		doc.write('<!doctype html><html><head><meta charset="utf-8"></head><body style="font-family:sans-serif;">' + content + '</body></html>');
		doc.close();

		function closeModal() {
			$overlay.remove();
		}

		$close.on('click', closeModal);
		$overlay.on('click', function (e) {
			if (e.target === $overlay[0]) {
				closeModal();
			}
		});
	}

	function initPreviewButtons() {
		$(document).on('click', '.wc-custom-order-email-preview-btn', function (e) {
			e.preventDefault();

			var $btn = $(this);
			var subject = getFieldValue($btn.data('subject-target'));
			var content = getFieldValue($btn.data('content-target'));

			showPreviewModal(replaceSample(subject), replaceSample(content));
		});
	}

	/* Test send via AJAX, using the most recent real order */
	function initTestSendButtons() {
		$(document).on('click', '.wc-custom-order-email-test-send-btn', function (e) {
			e.preventDefault();

			var $btn = $(this);
			var $result = $btn.siblings('.wc-custom-order-email-test-send-result');
			var subject = getFieldValue($btn.data('subject-target'));
			var content = getFieldValue($btn.data('content-target'));

			$btn.prop('disabled', true);
			$result.removeClass('wc-custom-order-email-result--error wc-custom-order-email-result--success').text(cfg.i18n.sending);

			$.post(cfg.ajaxUrl, {
				action: 'wc_custom_order_email_test_send',
				nonce: cfg.nonce,
				email_id: $btn.data('email-id'),
				language: $btn.data('lang'),
				subject: subject,
				content: content
			}).done(function (response) {
				if (response && response.success) {
					$result.addClass('wc-custom-order-email-result--success').text(response.data.message);
				} else {
					$result.addClass('wc-custom-order-email-result--error').text((response && response.data && response.data.message) || 'Error');
				}
			}).fail(function () {
				$result.addClass('wc-custom-order-email-result--error').text('Error');
			}).always(function () {
				$btn.prop('disabled', false);
			});
		});
	}

	$(function () {
		initPlaceholderChips();
		initCopyToolbar();
		initPreviewButtons();
		initTestSendButtons();
	});

})(jQuery);
