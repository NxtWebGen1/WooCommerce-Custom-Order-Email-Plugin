<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order-screen integration: a meta box with explicit send/reset actions
 * (instead of guessing the current order from the order-actions dropdown),
 * an "already sent" orders-list column, and a bulk-send action.
 *
 * Works for both classic (post-based) and HPOS order storage.
 */
class WC_Custom_Order_Email_Orders {

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );

		add_action( 'admin_post_wc_custom_order_email_send', array( $this, 'handle_send' ) );
		add_action( 'admin_post_wc_custom_order_email_reset', array( $this, 'handle_reset' ) );

		// Orders list column - classic.
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_order_list_column' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_order_list_column_classic' ), 10, 2 );

		// Orders list column - HPOS.
		add_filter( 'woocommerce_shop_order_list_table_columns', array( $this, 'add_order_list_column' ) );
		add_action( 'woocommerce_shop_order_list_table_custom_column', array( $this, 'render_order_list_column_hpos' ), 10, 2 );

		// Bulk action - classic.
		add_filter( 'bulk_actions-edit-shop_order', array( $this, 'add_bulk_action' ) );
		add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'handle_bulk_action' ), 10, 3 );

		// Bulk action - HPOS.
		add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( $this, 'add_bulk_action' ) );
		add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', array( $this, 'handle_bulk_action' ), 10, 3 );
	}

	/**
	 * All registered custom order emails, keyed by their ->id.
	 *
	 * @return WC_Custom_Order_Email[]
	 */
	private function get_emails() {
		$mailer = WC()->mailer();
		$emails = array();

		if ( ! $mailer || empty( $mailer->emails ) ) {
			return $emails;
		}

		foreach ( $mailer->emails as $email ) {
			if ( $email instanceof WC_Custom_Order_Email ) {
				$emails[ $email->id ] = $email;
			}
		}

		return $emails;
	}

	private function get_email( $id ) {
		$emails = $this->get_emails();

		return isset( $emails[ $id ] ) ? $emails[ $id ] : null;
	}

	/**
	 * HPOS-aware edit URL for an order.
	 */
	private function get_order_edit_url( $order ) {
		if ( method_exists( $order, 'get_edit_order_url' ) ) {
			return $order->get_edit_order_url();
		}

		return admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' );
	}

	/* -----------------------------------------------------------------
	 * Meta box
	 * --------------------------------------------------------------- */

	public function add_meta_box() {
		add_meta_box(
			'wc-custom-order-email',
			__( 'Custom Order Emails', 'wc-custom-order-email' ),
			array( $this, 'render_meta_box' ),
			wc_custom_order_email_get_order_screen_id(),
			'side',
			'default'
		);
	}

	public function render_meta_box( $post_or_order_object ) {
		$order = ( $post_or_order_object instanceof WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;

		if ( ! $order ) {
			return;
		}

		$emails = $this->get_emails();

		if ( empty( $emails ) ) {
			return;
		}

		echo '<div class="wc-custom-order-email-metabox">';

		foreach ( $emails as $email ) {
			$this->render_meta_box_row( $order, $email );
		}

		echo '</div>';
	}

	private function render_meta_box_row( $order, $email ) {
		$already_sent = $email->is_already_sent( $order );

		echo '<div class="wc-custom-order-email-row">';
		echo '<p><strong>' . esc_html( $email->get_title() ) . '</strong></p>';

		if ( $already_sent ) {
			$info = $email->get_sent_info( $order );
			if ( ! empty( $info['date'] ) ) {
				printf(
					/* translators: 1: date, 2: language code */
					'<p class="description">' . esc_html__( 'Sent on %1$s (%2$s).', 'wc-custom-order-email' ) . '</p>',
					esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $info['date'] ) ) ),
					esc_html( strtoupper( isset( $info['language'] ) ? $info['language'] : '' ) )
				);
			} else {
				echo '<p class="description">' . esc_html__( 'Already sent.', 'wc-custom-order-email' ) . '</p>';
			}

			$reset_url = wp_nonce_url(
				add_query_arg(
					array(
						'action'   => 'wc_custom_order_email_reset',
						'email_id' => $email->id,
						'order_id' => $order->get_id(),
					),
					admin_url( 'admin-post.php' )
				),
				'wc_custom_order_email_reset'
			);

			echo '<a href="' . esc_url( $reset_url ) . '" class="wc-custom-order-email-reset">' . esc_html__( 'Allow resending', 'wc-custom-order-email' ) . '</a>';
		} else {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="wc-custom-order-email-send-form">';
			wp_nonce_field( 'wc_custom_order_email_send' );
			echo '<input type="hidden" name="action" value="wc_custom_order_email_send" />';
			echo '<input type="hidden" name="email_id" value="' . esc_attr( $email->id ) . '" />';
			echo '<input type="hidden" name="order_id" value="' . esc_attr( $order->get_id() ) . '" />';

			echo '<select name="language" class="wc-enhanced-select" style="width: 100%;">';
			foreach ( $email->get_languages() as $code => $label ) {
				echo '<option value="' . esc_attr( $code ) . '">' . esc_html( $label ) . '</option>';
			}
			echo '</select>';

			echo '<p><button type="submit" class="button wc-custom-order-email-send-btn">' . esc_html__( 'Send', 'wc-custom-order-email' ) . '</button></p>';
			echo '</form>';
		}

		echo '</div><hr />';
	}

	/* -----------------------------------------------------------------
	 * Send / reset handlers
	 * --------------------------------------------------------------- */

	public function handle_send() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'wc-custom-order-email' ) );
		}

		check_admin_referer( 'wc_custom_order_email_send' );

		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$email_id = isset( $_POST['email_id'] ) ? sanitize_key( wp_unslash( $_POST['email_id'] ) ) : '';
		$language = isset( $_POST['language'] ) ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : 'en';

		$order = $order_id ? wc_get_order( $order_id ) : false;

		if ( ! $order ) {
			$this->set_notice( 'error', __( 'This email could not be sent - the order was not found.', 'wc-custom-order-email' ) );
			$this->redirect_to_order_list();
			return;
		}

		$email = $this->get_email( $email_id );

		if ( ! $email ) {
			$this->set_notice( 'error', __( 'This email could not be sent - unknown email type.', 'wc-custom-order-email' ) );
			$this->redirect( $this->get_order_edit_url( $order ) );
			return;
		}

		$result = $email->trigger( $order_id, $language );
		$this->set_notice_from_result( $result, $email, $order );

		$this->redirect( $this->get_order_edit_url( $order ) );
	}

	public function handle_reset() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'wc-custom-order-email' ) );
		}

		check_admin_referer( 'wc_custom_order_email_reset' );

		$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
		$email_id = isset( $_GET['email_id'] ) ? sanitize_key( wp_unslash( $_GET['email_id'] ) ) : '';

		$order = $order_id ? wc_get_order( $order_id ) : false;
		$email = $this->get_email( $email_id );

		if ( $order && $email ) {
			$email->reset_sent_flag( $order );
			$this->set_notice( 'success', sprintf(
				/* translators: %s: email title */
				__( '"%s" can now be sent again for this order.', 'wc-custom-order-email' ),
				$email->get_title()
			) );
			$this->redirect( $this->get_order_edit_url( $order ) );
			return;
		}

		$this->redirect_to_order_list();
	}

	private function set_notice_from_result( $result, $email, $order ) {
		$label = $email->get_title();

		switch ( $result ) {
			case 'sent':
				$this->set_notice( 'success', sprintf(
					/* translators: 1: email title, 2: recipient email */
					__( '"%1$s" was sent successfully to %2$s.', 'wc-custom-order-email' ),
					$label,
					$order->get_billing_email()
				) );
				break;

			case 'already_sent':
				$this->set_notice( 'error', sprintf(
					/* translators: %s: email title */
					__( '"%s" has already been sent for this order.', 'wc-custom-order-email' ),
					$label
				) );
				break;

			case 'no_recipient':
				$this->set_notice( 'error', sprintf(
					/* translators: %s: email title */
					__( '"%s" could not be sent - this order has no billing email address.', 'wc-custom-order-email' ),
					$label
				) );
				break;

			case 'disabled':
				$this->set_notice( 'error', sprintf(
					/* translators: %s: email title */
					__( '"%s" is currently disabled in WooCommerce > Settings > Emails.', 'wc-custom-order-email' ),
					$label
				) );
				break;

			case 'mail_failed':
				$this->set_notice( 'error', __( 'The email could not be sent. Please check your site\'s mail configuration.', 'wc-custom-order-email' ) );
				break;

			default:
				$this->set_notice( 'error', __( 'The order could not be found.', 'wc-custom-order-email' ) );
				break;
		}
	}

	private function set_notice( $type, $message ) {
		set_transient( 'wc_custom_email_notice_' . get_current_user_id(), array(
			'type'    => $type,
			'message' => $message,
		), 30 );
	}

	public function display_admin_notices() {
		$notice = get_transient( 'wc_custom_email_notice_' . get_current_user_id() );

		if ( $notice && is_array( $notice ) ) {
			printf(
				'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
				esc_attr( $notice['type'] ),
				esc_html( $notice['message'] )
			);
			delete_transient( 'wc_custom_email_notice_' . get_current_user_id() );
		}

		if ( isset( $_GET['wc_custom_email_bulk_sent'] ) ) {
			$sent    = absint( $_GET['wc_custom_email_bulk_sent'] );
			$skipped = isset( $_GET['wc_custom_email_bulk_skipped'] ) ? absint( $_GET['wc_custom_email_bulk_skipped'] ) : 0;

			printf(
				'<div class="notice notice-info is-dismissible"><p>%s</p></div>',
				esc_html( sprintf(
					/* translators: 1: number sent, 2: number skipped */
					__( 'Order Processing Error email: sent to %1$d order(s), skipped %2$d (already sent, no billing email, or disabled).', 'wc-custom-order-email' ),
					$sent,
					$skipped
				) )
			);
		}
	}

	private function redirect( $url ) {
		wp_safe_redirect( $url );
		exit;
	}

	private function redirect_to_order_list() {
		$this->redirect( admin_url( 'edit.php?post_type=shop_order' ) );
	}

	/* -----------------------------------------------------------------
	 * Assets
	 * --------------------------------------------------------------- */

	public function enqueue_admin_scripts( $hook ) {
		$screen = get_current_screen();
		$is_order_screen = $screen && $screen->id === wc_custom_order_email_get_order_screen_id();

		if ( ! $is_order_screen && ( 'post.php' === $hook || 'post-new.php' === $hook ) ) {
			global $post;
			$is_order_screen = $post && 'shop_order' === $post->post_type;
		}

		if ( ! $is_order_screen ) {
			return;
		}

		wp_enqueue_style(
			'wc-custom-order-email-admin',
			WC_CUSTOM_ORDER_EMAIL_PLUGIN_URL . 'assets/admin.css',
			array(),
			WC_CUSTOM_ORDER_EMAIL_VERSION
		);

		wp_enqueue_script(
			'wc-custom-order-email-admin',
			WC_CUSTOM_ORDER_EMAIL_PLUGIN_URL . 'assets/admin.js',
			array( 'jquery' ),
			WC_CUSTOM_ORDER_EMAIL_VERSION,
			true
		);
	}

	/* -----------------------------------------------------------------
	 * Orders list column
	 * --------------------------------------------------------------- */

	public function add_order_list_column( $columns ) {
		$columns['wc_custom_order_email'] = __( 'Custom Emails', 'wc-custom-order-email' );

		return $columns;
	}

	public function render_order_list_column_classic( $column, $post_id ) {
		if ( 'wc_custom_order_email' === $column ) {
			$this->render_order_list_column_content( wc_get_order( $post_id ) );
		}
	}

	public function render_order_list_column_hpos( $column, $order ) {
		if ( 'wc_custom_order_email' === $column ) {
			$this->render_order_list_column_content( $order );
		}
	}

	private function render_order_list_column_content( $order ) {
		if ( ! $order ) {
			echo '&#8211;';
			return;
		}

		foreach ( $this->get_emails() as $email ) {
			$label = $email->get_title();

			if ( $email->is_already_sent( $order ) ) {
				$info  = $email->get_sent_info( $order );
				$title = ! empty( $info['date'] )
					? sprintf(
						/* translators: 1: email title, 2: date, 3: language code */
						__( '%1$s sent on %2$s (%3$s)', 'wc-custom-order-email' ),
						$label,
						$info['date'],
						strtoupper( isset( $info['language'] ) ? $info['language'] : '' )
					)
					: $label;

				echo '<span class="wc-custom-order-email-badge wc-custom-order-email-badge--sent" title="' . esc_attr( $title ) . '">&#10003; ' . esc_html( $label ) . '</span><br />';
			} else {
				echo '<span class="wc-custom-order-email-badge wc-custom-order-email-badge--pending" title="' . esc_attr( sprintf(
					/* translators: %s: email title */
					__( '%s not sent yet', 'wc-custom-order-email' ),
					$label
				) ) . '">&#8211; ' . esc_html( $label ) . '</span><br />';
			}
		}
	}

	/* -----------------------------------------------------------------
	 * Bulk action - Order Processing Error, sent in a default language
	 * --------------------------------------------------------------- */

	public function add_bulk_action( $actions ) {
		$actions['wc_custom_order_email_send_processing'] = __( 'Send: Order Processing Error email', 'wc-custom-order-email' );

		return $actions;
	}

	public function handle_bulk_action( $redirect_to, $action, $order_ids ) {
		if ( 'wc_custom_order_email_send_processing' !== $action ) {
			return $redirect_to;
		}

		$email = $this->get_email( 'wc_custom_order_email_processing' );
		$sent    = 0;
		$skipped = 0;

		if ( $email ) {
			$language = apply_filters( 'wc_custom_order_email_bulk_language', 'en' );

			foreach ( (array) $order_ids as $order_id ) {
				$result = $email->trigger( absint( $order_id ), $language );
				if ( 'sent' === $result ) {
					$sent++;
				} else {
					$skipped++;
				}
			}
		} else {
			$skipped = count( (array) $order_ids );
		}

		return add_query_arg(
			array(
				'wc_custom_email_bulk_sent'    => $sent,
				'wc_custom_email_bulk_skipped' => $skipped,
			),
			$redirect_to
		);
	}
}
