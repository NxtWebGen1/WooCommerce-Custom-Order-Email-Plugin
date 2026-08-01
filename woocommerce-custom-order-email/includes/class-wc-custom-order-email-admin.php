<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enhancements for the native WooCommerce email settings screen:
 * placeholder-insertion chips, a "copy to another language" toolbar,
 * a client-side live preview, and a "send test email" action.
 */
class WC_Custom_Order_Email_Admin {

	const EMAIL_IDS = array( 'wc_custom_order_email_payment', 'wc_custom_order_email_processing' );

	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_settings_assets' ) );
		add_action( 'wp_ajax_wc_custom_order_email_test_send', array( $this, 'ajax_test_send' ) );
	}

	private function get_current_section() {
		if ( ! isset( $_GET['page'], $_GET['tab'] ) || 'wc-settings' !== $_GET['page'] || 'email' !== $_GET['tab'] ) {
			return '';
		}

		$section = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : '';

		return in_array( $section, self::EMAIL_IDS, true ) ? $section : '';
	}

	public function enqueue_settings_assets() {
		$section = $this->get_current_section();

		if ( ! $section ) {
			return;
		}

		wp_enqueue_style(
			'wc-custom-order-email-admin',
			WC_CUSTOM_ORDER_EMAIL_PLUGIN_URL . 'assets/admin.css',
			array(),
			WC_CUSTOM_ORDER_EMAIL_VERSION
		);

		wp_enqueue_script(
			'wc-custom-order-email-settings',
			WC_CUSTOM_ORDER_EMAIL_PLUGIN_URL . 'assets/settings.js',
			array( 'jquery' ),
			WC_CUSTOM_ORDER_EMAIL_VERSION,
			true
		);

		wp_localize_script( 'wc-custom-order-email-settings', 'wcCustomOrderEmailAdmin', array(
			'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
			'nonce'            => wp_create_nonce( 'wc_custom_order_email_admin' ),
			'emailId'          => $section,
			'languages'        => array(
				'de' => __( 'German', 'wc-custom-order-email' ),
				'en' => __( 'English', 'wc-custom-order-email' ),
				'fr' => __( 'French', 'wc-custom-order-email' ),
			),
			'fieldPrefix'      => 'woocommerce_' . $section . '_',
			'textPlaceholders' => array( '{order_number}', '{customer_name}', '{customer_first_name}', '{order_date}', '{order_total}', '{wc-order-item-name}' ),
			'htmlPlaceholders' => array( '{order_number}', '{customer_name}', '{customer_first_name}', '{customer_email}', '{order_date}', '{order_total}', '{billing_address}', '{shipping_address}', '{order_items}', '{wc-order-item-name}' ),
			'sampleData'       => array(
				'{order_number}'        => '1234',
				'{customer_name}'       => 'Jane Doe',
				'{customer_first_name}' => 'Jane',
				'{customer_email}'      => 'jane@example.com',
				'{order_date}'          => date_i18n( get_option( 'date_format' ) ),
				'{order_total}'         => '$49.00',
				'{billing_address}'     => 'Jane Doe<br>123 Example Street<br>Springfield, 12345',
				'{shipping_address}'    => 'Jane Doe<br>123 Example Street<br>Springfield, 12345',
				'{order_items}'         => '<table style="width:100%;border-collapse:collapse;"><tr><td style="padding:6px;border:1px solid #ddd;">Sample Product</td><td style="padding:6px;border:1px solid #ddd;text-align:right;">1</td><td style="padding:6px;border:1px solid #ddd;text-align:right;">$49.00</td></tr></table>',
				'{wc-order-item-name}'  => 'Sample Product',
			),
			'i18n'             => array(
				'previewTitle' => __( 'Email Preview (sample data)', 'wc-custom-order-email' ),
				'sending'      => __( 'Sending...', 'wc-custom-order-email' ),
				'close'        => __( 'Close', 'wc-custom-order-email' ),
				'copyLanguage' => __( 'Copy content between languages', 'wc-custom-order-email' ),
				'from'         => __( 'From', 'wc-custom-order-email' ),
				'to'           => __( 'To', 'wc-custom-order-email' ),
				'copyButton'   => __( 'Copy', 'wc-custom-order-email' ),
				'copyConfirm'  => __( 'This will overwrite the subject, heading, and content of the target language. Continue?', 'wc-custom-order-email' ),
			),
		) );
	}

	/**
	 * AJAX: send a test email using unsaved subject/content values, rendered
	 * against the most recent real order so placeholders resolve correctly.
	 */
	public function ajax_test_send() {
		check_ajax_referer( 'wc_custom_order_email_admin', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do this.', 'wc-custom-order-email' ) ) );
		}

		$email_id = isset( $_POST['email_id'] ) ? sanitize_key( wp_unslash( $_POST['email_id'] ) ) : '';
		$language = isset( $_POST['language'] ) ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : 'en';
		$subject  = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
		$content  = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';

		if ( ! in_array( $email_id, self::EMAIL_IDS, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Unknown email type.', 'wc-custom-order-email' ) ) );
		}

		$mailer = WC()->mailer();
		$email  = null;

		foreach ( $mailer->emails as $candidate ) {
			if ( $candidate instanceof WC_Custom_Order_Email && $candidate->id === $email_id ) {
				$email = $candidate;
				break;
			}
		}

		if ( ! $email ) {
			wp_send_json_error( array( 'message' => __( 'Unknown email type.', 'wc-custom-order-email' ) ) );
		}

		$orders = wc_get_orders( array(
			'limit'   => 1,
			'orderby' => 'date',
			'order'   => 'DESC',
		) );

		if ( empty( $orders ) ) {
			wp_send_json_error( array( 'message' => __( 'No orders exist yet, so a test email can\'t be rendered with real order data. Create at least one order first, or use the Preview button for a sample-data preview.', 'wc-custom-order-email' ) ) );
		}

		$order    = $orders[0];
		$rendered = $email->render_preview( $order, $language, $subject, $content );

		$current_user = wp_get_current_user();
		$to           = $current_user->user_email;

		if ( ! $to ) {
			wp_send_json_error( array( 'message' => __( 'Your admin user account has no email address to send the test to.', 'wc-custom-order-email' ) ) );
		}

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$body    = '<p><em>' . sprintf(
			/* translators: %s: order number */
			esc_html__( 'This is a test email rendered using order #%s. The shared header/footer branding used by the real email is not shown here.', 'wc-custom-order-email' ),
			esc_html( $order->get_order_number() )
		) . '</em></p>' . wpautop( wptexturize( $rendered['content'] ) );

		$sent = wp_mail( $to, '[' . __( 'TEST', 'wc-custom-order-email' ) . '] ' . $rendered['subject'], $body, $headers );

		if ( $sent ) {
			wp_send_json_success( array( 'message' => sprintf(
				/* translators: %s: recipient email address */
				__( 'Test email sent to %s.', 'wc-custom-order-email' ),
				$to
			) ) );
		}

		wp_send_json_error( array( 'message' => __( 'Failed to send the test email. Please check your site\'s mail configuration.', 'wc-custom-order-email' ) ) );
	}
}
