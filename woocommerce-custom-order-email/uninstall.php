<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Current (2.0+) storage: one settings option per email, managed by WC_Settings_API.
delete_option( 'woocommerce_wc_custom_order_email_payment_settings' );
delete_option( 'woocommerce_wc_custom_order_email_processing_settings' );

// Legacy (1.0) storage, kept for cleanup on sites uninstalling without ever
// having re-saved settings since upgrading.
$languages   = array( 'de', 'en', 'fr' );
$email_types = array( 'payment', 'processing' );

foreach ( $email_types as $email_type ) {
	foreach ( $languages as $lang ) {
		delete_option( 'wc_custom_email_subject_' . $email_type . '_' . $lang );
		delete_option( 'wc_custom_email_content_' . $email_type . '_' . $lang );
	}
}
