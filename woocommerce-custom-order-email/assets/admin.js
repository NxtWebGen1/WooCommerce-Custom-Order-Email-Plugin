/**
 * WooCommerce Custom Order Email - Admin JavaScript
 */
(function($) {
	'use strict';
	
	function initLanguageSelector() {
		var $orderActionSelect = $('select[name="wc_order_action"]');
		
		// Prüfe ob das Select-Element existiert
		if ($orderActionSelect.length === 0) {
			return;
		}
		
		var $actionsContainer = $orderActionSelect.closest('li.wide');
		var $submitBox = $actionsContainer.closest('ul.submitbox, ul.order_actions');
		
		// Fallback: Suche nach dem nächstgelegenen Container
		if ($actionsContainer.length === 0) {
			$actionsContainer = $orderActionSelect.parent();
		}
		
		var languageSelectorHtml = '<li class="wide" id="wc_custom_email_language_wrapper" style="display: none; padding: 10px 0;">' +
			'<label for="wc_custom_email_language" style="display: block; margin-bottom: 5px;">' +
			'<strong>Sprache auswählen:</strong>' +
			'</label>' +
			'<select name="wc_custom_email_language" id="wc_custom_email_language" style="width: 100%;">' +
			'<option value="de">Deutsch</option>' +
			'<option value="en">Englisch</option>' +
			'<option value="fr">Französisch</option>' +
			'</select>' +
			'</li>';
		
		// Füge Sprachauswahl hinzu, falls noch nicht vorhanden
		if ($('#wc_custom_email_language_wrapper').length === 0) {
			if ($actionsContainer.length > 0) {
				$actionsContainer.after(languageSelectorHtml);
			} else if ($submitBox.length > 0) {
				$submitBox.find('li.wide').first().after(languageSelectorHtml);
			} else {
				$orderActionSelect.after(languageSelectorHtml);
			}
		}
		
		var $languageWrapper = $('#wc_custom_email_language_wrapper');
		
		// Funktion zum Anzeigen/Verstecken der Sprachauswahl
		function toggleLanguageSelector() {
			var selectedAction = $orderActionSelect.val();
			if (selectedAction === 'send_custom_email' || selectedAction === 'send_order_processing_email') {
				$languageWrapper.slideDown(200);
			} else {
				$languageWrapper.slideUp(200);
			}
		}
		
		// Initial prüfen
		toggleLanguageSelector();
		
		// Bei Änderung der Order Action
		$orderActionSelect.off('change.wc_custom_email').on('change.wc_custom_email', function() {
			toggleLanguageSelector();
		});
		
		// Stelle sicher, dass die Sprachauswahl beim Absenden des Formulars sichtbar ist
		$(document).off('submit.wc_custom_email', 'form#post, form.order_actions, form').on('submit.wc_custom_email', 'form#post, form.order_actions, form', function() {
			var selectedAction = $orderActionSelect.val();
			if (selectedAction === 'send_custom_email' || selectedAction === 'send_order_processing_email') {
				$languageWrapper.show();
			}
		});
	}
	
	// Initialisiere beim DOM-Ready
	$(document).ready(function() {
		initLanguageSelector();
	});
	
	// Initialisiere auch nach AJAX-Laden (für WooCommerce HPOS)
	$(document).on('wc_backbone_modal_loaded', function() {
		setTimeout(initLanguageSelector, 100);
	});
	
	// Initialisiere auch nach anderen WooCommerce Events
	$(document).on('woocommerce_order_loaded', function() {
		setTimeout(initLanguageSelector, 100);
	});
	
})(jQuery);

