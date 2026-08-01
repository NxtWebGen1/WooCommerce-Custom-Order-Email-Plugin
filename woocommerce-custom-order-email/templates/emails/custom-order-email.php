<?php
/**
 * HTML wrapper for the custom order emails.
 * Uses WooCommerce's own header/footer hooks so the email matches the
 * store's other transactional emails (logo, colors, base template).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<?php echo wp_kses_post( wpautop( wptexturize( $content ) ) ); ?>

<?php
do_action( 'woocommerce_email_footer', $email );
