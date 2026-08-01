/**
 * WooCommerce Custom Order Email - Order screen meta box.
 * (Language <select> elements get select2 automatically via WooCommerce's
 * own .wc-enhanced-select handling - no extra init needed here.)
 */
(function ($) {
	'use strict';

	$(document).on('submit', '.wc-custom-order-email-send-form', function (e) {
		if (!window.confirm('Send this email to the customer now?')) {
			e.preventDefault();
		}
	});

	$(document).on('click', '.wc-custom-order-email-reset', function (e) {
		if (!window.confirm('Allow this email to be sent again for this order?')) {
			e.preventDefault();
		}
	});

})(jQuery);
