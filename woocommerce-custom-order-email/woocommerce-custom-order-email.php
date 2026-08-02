<?php
/**
 * Plugin Name: WooCommerce Custom Order Email
 * Plugin URI: https://github.com/NxtWebGen1/WooCommerce-Custom-Order-Email-Plugin
 * Description: Adds "Resend Payment Details" and "Order Processing Error" order emails to WooCommerce, with templates configurable in German, English, and French.
 * Version: 2.0.0
 * Author: Murslin Shehzad
 * Author URI: https://github.com/NxtWebGen1
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wc-custom-order-email
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 9.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Bail out if WooCommerce isn't active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
	return;
}

// Plugin constants
define( 'WC_CUSTOM_ORDER_EMAIL_VERSION', '2.0.0' );
define( 'WC_CUSTOM_ORDER_EMAIL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WC_CUSTOM_ORDER_EMAIL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WC_CUSTOM_ORDER_EMAIL_PLUGIN_FILE', __FILE__ );

/**
 * Set up the parts of the plugin that don't depend on WC_Email.
 *
 * WooCommerce's own email base class (WC_Email) isn't loaded yet at
 * "woocommerce_loaded" - it's only required later, when WC_Emails::init()
 * runs on the "init" hook. So our classes that extend WC_Email must be
 * require'd lazily, inside the woocommerce_email_classes filter callback
 * below, not here.
 */
function wc_custom_order_email_init() {
	add_filter( 'woocommerce_email_classes', 'wc_custom_order_email_register_classes' );

	if ( is_admin() ) {
		require_once WC_CUSTOM_ORDER_EMAIL_PLUGIN_DIR . 'includes/class-wc-custom-order-email-orders.php';
		require_once WC_CUSTOM_ORDER_EMAIL_PLUGIN_DIR . 'includes/class-wc-custom-order-email-admin.php';

		new WC_Custom_Order_Email_Orders();
		new WC_Custom_Order_Email_Admin();
	}
}
add_action( 'woocommerce_loaded', 'wc_custom_order_email_init' );

/**
 * Register our custom emails with WooCommerce so they appear under
 * WooCommerce > Settings > Emails alongside the built-in ones.
 *
 * This filter is applied by WC_Emails::init() on the "init" hook, by which
 * point WC_Email is guaranteed to be loaded - safe to require our classes
 * that extend it here.
 */
function wc_custom_order_email_register_classes( $email_classes ) {
	require_once WC_CUSTOM_ORDER_EMAIL_PLUGIN_DIR . 'includes/class-wc-custom-order-email.php';
	require_once WC_CUSTOM_ORDER_EMAIL_PLUGIN_DIR . 'includes/class-wc-custom-order-email-payment.php';
	require_once WC_CUSTOM_ORDER_EMAIL_PLUGIN_DIR . 'includes/class-wc-custom-order-email-processing.php';

	$email_classes['WC_Custom_Order_Email_Payment']    = new WC_Custom_Order_Email_Payment();
	$email_classes['WC_Custom_Order_Email_Processing'] = new WC_Custom_Order_Email_Processing();

	return $email_classes;
}

/**
 * The order-edit screen ID, classic or HPOS.
 *
 * wc_get_page_screen_id() only exists on WooCommerce versions with HPOS
 * support; this plugin's minimum (WC 5.0) predates it, so fall back to the
 * classic post-type screen ID when it's not available.
 */
function wc_custom_order_email_get_order_screen_id() {
	if ( function_exists( 'wc_get_page_screen_id' ) ) {
		return wc_get_page_screen_id( 'shop-order' );
	}

	return 'shop_order';
}
