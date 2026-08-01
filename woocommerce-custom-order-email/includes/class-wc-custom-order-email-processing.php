<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "Order Processing Error" order email.
 */
class WC_Custom_Order_Email_Processing extends WC_Custom_Order_Email {

	protected $meta_suffix = 'processing';

	public function __construct() {
		$this->id          = 'wc_custom_order_email_processing';
		$this->title       = __( 'Order Processing Error', 'wc-custom-order-email' );
		$this->description = __( 'Manually notify the customer of a problem processing their order. Available in German, English, and French, and can only be sent once per order.', 'wc-custom-order-email' );

		parent::__construct();
	}

	public function get_default_subject( $language ) {
		$subjects = array(
			'de' => __( 'Problem bei der Bearbeitung Ihrer Bestellung {order_number}', 'wc-custom-order-email' ),
			'en' => __( 'There was a problem processing your order {order_number}', 'wc-custom-order-email' ),
			'fr' => __( 'Un problème est survenu avec votre commande {order_number}', 'wc-custom-order-email' ),
		);

		return isset( $subjects[ $language ] ) ? $subjects[ $language ] : $subjects['en'];
	}

	public function get_default_heading( $language ) {
		$headings = array(
			'de' => __( 'Bestellung in Bearbeitung - Fehler', 'wc-custom-order-email' ),
			'en' => __( 'Order Processing Error', 'wc-custom-order-email' ),
			'fr' => __( 'Erreur de traitement de la commande', 'wc-custom-order-email' ),
		);

		return isset( $headings[ $language ] ) ? $headings[ $language ] : $headings['en'];
	}

	public function get_default_content( $language ) {
		$content = array(
			'de' => '<p>Hallo {customer_first_name},</p><p>bei der Bearbeitung Ihrer Bestellung {order_number} vom {order_date} ist ein Problem aufgetreten. Unser Team wurde informiert und wird sich in Kürze bei Ihnen melden.</p><p>Bestellsumme: {order_total}</p>{order_items}',
			'en' => '<p>Hello {customer_first_name},</p><p>We ran into a problem processing your order {order_number} placed on {order_date}. Our team has been notified and will be in touch shortly.</p><p>Order total: {order_total}</p>{order_items}',
			'fr' => '<p>Bonjour {customer_first_name},</p><p>Un problème est survenu lors du traitement de votre commande {order_number} du {order_date}. Notre équipe a été informée et vous contactera prochainement.</p><p>Total de la commande : {order_total}</p>{order_items}',
		);

		return isset( $content[ $language ] ) ? $content[ $language ] : $content['en'];
	}
}
