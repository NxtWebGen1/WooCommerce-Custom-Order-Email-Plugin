<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "Resend Payment Details" order email.
 */
class WC_Custom_Order_Email_Payment extends WC_Custom_Order_Email {

	protected $meta_suffix = 'payment';

	public function __construct() {
		$this->id          = 'wc_custom_order_email_payment';
		$this->title       = __( 'Resend Payment Details', 'wc-custom-order-email' );
		$this->description = __( 'Manually send the customer their payment details again. Available in German, English, and French, and can only be sent once per order.', 'wc-custom-order-email' );

		parent::__construct();
	}

	public function get_language_default_subject( $language ) {
		$subjects = array(
			'de' => __( 'Ihre Zahlungsdaten für Bestellung {order_number}', 'wc-custom-order-email' ),
			'en' => __( 'Your payment details for order {order_number}', 'wc-custom-order-email' ),
			'fr' => __( 'Vos informations de paiement pour la commande {order_number}', 'wc-custom-order-email' ),
		);

		return isset( $subjects[ $language ] ) ? $subjects[ $language ] : $subjects['en'];
	}

	public function get_language_default_heading( $language ) {
		$headings = array(
			'de' => __( 'Zahlungsdaten', 'wc-custom-order-email' ),
			'en' => __( 'Payment Details', 'wc-custom-order-email' ),
			'fr' => __( 'Informations de paiement', 'wc-custom-order-email' ),
		);

		return isset( $headings[ $language ] ) ? $headings[ $language ] : $headings['en'];
	}

	public function get_language_default_content( $language ) {
		$content = array(
			'de' => '<p>Hallo {customer_first_name},</p><p>anbei erhalten Sie erneut die Zahlungsdaten zu Ihrer Bestellung {order_number} vom {order_date}.</p><p>Bestellsumme: {order_total}</p>{order_items}<p>Rechnungsadresse:<br>{billing_address}</p>',
			'en' => '<p>Hello {customer_first_name},</p><p>Here are your payment details again for order {order_number} placed on {order_date}.</p><p>Order total: {order_total}</p>{order_items}<p>Billing address:<br>{billing_address}</p>',
			'fr' => '<p>Bonjour {customer_first_name},</p><p>Voici à nouveau vos informations de paiement pour la commande {order_number} du {order_date}.</p><p>Total de la commande : {order_total}</p>{order_items}<p>Adresse de facturation :<br>{billing_address}</p>',
		);

		return isset( $content[ $language ] ) ? $content[ $language ] : $content['en'];
	}
}
