<?php
/**
 * Plain-text alternative for the custom order emails.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo wp_strip_all_tags( $email_heading ) . "\n\n";
echo $content . "\n\n"; // Already plain text at this point (see WC_Custom_Order_Email::get_content_plain()).
echo esc_html__( '----------------------------------------', 'wc-custom-order-email' ) . "\n";
