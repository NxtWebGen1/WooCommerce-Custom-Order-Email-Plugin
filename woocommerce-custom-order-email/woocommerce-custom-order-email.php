<?php
/**
 * Plugin Name: WooCommerce Custom Order Email
 * Plugin URI: https://dev1.aluhutdating.de
 * Description: Fügt eine benutzerdefinierte E-Mail-Aktion zu WooCommerce Bestellungen hinzu, die in mehreren Sprachen verfügbar ist.
 * Version: 1.0.0
 * Author: Aluhut Dating
 * Author URI: https://dev1.aluhutdating.de
 * Text Domain: wc-custom-order-email
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Prüfe ob WooCommerce aktiv ist
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}

// Plugin-Konstanten
define( 'WC_CUSTOM_ORDER_EMAIL_VERSION', '1.0.0' );
define( 'WC_CUSTOM_ORDER_EMAIL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WC_CUSTOM_ORDER_EMAIL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Haupt-Plugin-Klasse
 */
class WC_Custom_Order_Email {
	
	/**
	 * Instanz der Klasse
	 */
	private static $instance = null;
	
	/**
	 * Singleton-Instanz
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Konstruktor
	 */
	private function __construct() {
		$this->init_hooks();
	}
	
	/**
	 * Initialisiere Hooks
	 */
	private function init_hooks() {
		// Admin-Hooks
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		
		// WooCommerce Order Actions
		add_filter( 'woocommerce_order_actions', array( $this, 'add_custom_order_action' ) );
		add_action( 'woocommerce_order_action_send_custom_email', array( $this, 'process_custom_order_action' ) );
		add_action( 'woocommerce_order_action_send_order_processing_email', array( $this, 'process_order_processing_action' ) );
		
		// Enqueue Scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		
		// Admin Notices anzeigen
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
		
		// AJAX für Sprachauswahl
		add_action( 'wp_ajax_wc_custom_email_get_language', array( $this, 'ajax_get_language_options' ) );
	}
	
	/**
	 * Füge Admin-Menü hinzu
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Benutzerdefinierte Bestellungs-E-Mail', 'wc-custom-order-email' ),
			'Custom E-Mails',
			'manage_woocommerce',
			'wc-custom-order-email',
			array( $this, 'render_settings_page' )
		);
	}
	
	/**
	 * Registriere Einstellungen
	 */
	public function register_settings() {
		// Registriere Einstellungen für jede Sprache und jeden E-Mail-Typ
		$languages = array( 'de', 'en', 'fr' );
		$email_types = array( 'payment', 'processing' );
		
		foreach ( $email_types as $email_type ) {
			foreach ( $languages as $lang ) {
				register_setting( 'wc_custom_order_email_settings', 'wc_custom_email_subject_' . $email_type . '_' . $lang );
				register_setting( 'wc_custom_order_email_settings', 'wc_custom_email_content_' . $email_type . '_' . $lang );
			}
		}
	}
	
	/**
	 * Rendere Einstellungsseite
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( __( 'Sie haben keine Berechtigung, auf diese Seite zuzugreifen.', 'wc-custom-order-email' ) );
		}
		
		// Speichere Einstellungen
		if ( isset( $_POST['submit'] ) && check_admin_referer( 'wc_custom_order_email_settings' ) ) {
			$languages = array( 'de', 'en', 'fr' );
			$email_types = array( 'payment', 'processing' );
			
			foreach ( $email_types as $email_type ) {
				foreach ( $languages as $lang ) {
					$subject_key = 'wc_custom_email_subject_' . $email_type . '_' . $lang;
					$content_key = 'wc_custom_email_content_' . $email_type . '_' . $lang;
					
					if ( isset( $_POST[ $subject_key ] ) ) {
						update_option( $subject_key, sanitize_text_field( $_POST[ $subject_key ] ) );
					}
					if ( isset( $_POST[ $content_key ] ) ) {
						update_option( $content_key, wp_kses_post( $_POST[ $content_key ] ) );
					}
				}
			}
			
			echo '<div class="notice notice-success"><p>' . __( 'Einstellungen gespeichert!', 'wc-custom-order-email' ) . '</p></div>';
		}
		
		$languages = array(
			'de' => __( 'Deutsch', 'wc-custom-order-email' ),
			'en' => __( 'Englisch', 'wc-custom-order-email' ),
			'fr' => __( 'Französisch', 'wc-custom-order-email' ),
		);
		
		$email_types = array(
			'payment' => 'Zahlungsdaten neu senden',
			'processing' => 'Bestellung in Bearbeitung Fehler',
		);
		
		// Hole aktive Tabs
		$active_email_type = isset( $_GET['email_type'] ) ? sanitize_text_field( $_GET['email_type'] ) : 'payment';
		if ( ! in_array( $active_email_type, array_keys( $email_types ) ) ) {
			$active_email_type = 'payment';
		}
		
		$active_lang = isset( $_GET['lang'] ) ? sanitize_text_field( $_GET['lang'] ) : 'de';
		if ( ! in_array( $active_lang, array_keys( $languages ) ) ) {
			$active_lang = 'de';
		}
		
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<form method="post" action="">
				<?php wp_nonce_field( 'wc_custom_order_email_settings' ); ?>
				
				<!-- E-Mail-Typ Tabs -->
				<div class="nav-tab-wrapper" style="margin-bottom: 10px;">
					<?php
					foreach ( $email_types as $email_type_key => $email_type_name ) {
						$active_class = ( $active_email_type === $email_type_key ) ? ' nav-tab-active' : '';
						$url = add_query_arg( array( 'email_type' => $email_type_key, 'lang' => $active_lang ), admin_url( 'admin.php?page=wc-custom-order-email' ) );
						echo '<a href="' . esc_url( $url ) . '" class="nav-tab' . esc_attr( $active_class ) . '">' . esc_html( $email_type_name ) . '</a>';
					}
					?>
				</div>
				
				<!-- Sprach-Tabs -->
				<div class="nav-tab-wrapper" style="margin-bottom: 20px;">
					<?php
					foreach ( $languages as $lang_code => $lang_name ) {
						$active_class = ( $active_lang === $lang_code ) ? ' nav-tab-active' : '';
						$url = add_query_arg( array( 'email_type' => $active_email_type, 'lang' => $lang_code ), admin_url( 'admin.php?page=wc-custom-order-email' ) );
						echo '<a href="' . esc_url( $url ) . '" class="nav-tab' . esc_attr( $active_class ) . '">' . esc_html( $lang_name ) . '</a>';
					}
					?>
				</div>
				
				<?php
				$subject = get_option( 'wc_custom_email_subject_' . $active_email_type . '_' . $active_lang, '' );
				$content = get_option( 'wc_custom_email_content_' . $active_email_type . '_' . $active_lang, '' );
				?>
				
				<input type="hidden" name="current_email_type" value="<?php echo esc_attr( $active_email_type ); ?>" />
				<input type="hidden" name="current_lang" value="<?php echo esc_attr( $active_lang ); ?>" />
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="wc_custom_email_subject_<?php echo esc_attr( $active_email_type ); ?>_<?php echo esc_attr( $active_lang ); ?>">
								<?php _e( 'E-Mail-Betreff', 'wc-custom-order-email' ); ?>
							</label>
						</th>
						<td>
							<input type="text" 
								   id="wc_custom_email_subject_<?php echo esc_attr( $active_email_type ); ?>_<?php echo esc_attr( $active_lang ); ?>" 
								   name="wc_custom_email_subject_<?php echo esc_attr( $active_email_type ); ?>_<?php echo esc_attr( $active_lang ); ?>" 
								   value="<?php echo esc_attr( $subject ); ?>" 
								   class="regular-text" />
							<p class="description">
								<?php _e( 'Verfügbare Platzhalter: {order_number}, {customer_name}, {customer_first_name}, {order_date}, {order_total}, {wc-order-item-name}', 'wc-custom-order-email' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wc_custom_email_content_<?php echo esc_attr( $active_email_type ); ?>_<?php echo esc_attr( $active_lang ); ?>">
								<?php _e( 'E-Mail-Inhalt', 'wc-custom-order-email' ); ?>
							</label>
						</th>
						<td>
							<?php
							$editor_id = 'wc_custom_email_content_' . $active_email_type . '_' . $active_lang;
							wp_editor(
								$content,
								$editor_id,
								array(
									'textarea_name' => 'wc_custom_email_content_' . $active_email_type . '_' . $active_lang,
									'textarea_rows' => 15,
									'media_buttons' => false,
									'tinymce' => true,
								)
							);
							?>
							<p class="description">
								<?php _e( 'Verfügbare Platzhalter: {order_number}, {customer_name}, {customer_first_name}, {customer_email}, {order_date}, {order_total}, {billing_address}, {shipping_address}, {order_items}, {wc-order-item-name}', 'wc-custom-order-email' ); ?>
							</p>
						</td>
					</tr>
				</table>
				
				<?php submit_button(); ?>
			</form>
		</div>
		
		<style>
			.nav-tab-wrapper {
				margin-bottom: 20px;
			}
		</style>
		<?php
	}
	
	/**
	 * Füge benutzerdefinierte Order Action hinzu
	 */
	public function add_custom_order_action( $actions ) {
		// Hole die aktuelle Bestellung
		global $theorder;
		
		$order = $theorder;
		
		// Falls $theorder nicht verfügbar ist, versuche die Order-ID aus dem Request zu holen
		if ( ! $order ) {
			$order_id = 0;
			
			// Für HPOS
			if ( isset( $_GET['id'] ) ) {
				$order_id = absint( $_GET['id'] );
			}
			// Für klassische Orders
			elseif ( isset( $_GET['post'] ) ) {
				$order_id = absint( $_GET['post'] );
			}
			// Fallback: Aus POST-Request (wenn Formular gesendet wurde)
			elseif ( isset( $_POST['post_ID'] ) ) {
				$order_id = absint( $_POST['post_ID'] );
			}
			// Fallback: Aus POST-Request (HPOS)
			elseif ( isset( $_POST['order_id'] ) ) {
				$order_id = absint( $_POST['order_id'] );
			}
			
			if ( $order_id ) {
				$order = wc_get_order( $order_id );
			}
		}
		
		// Prüfe welche E-Mails bereits gesendet wurden
		$payment_sent = false;
		$processing_sent = false;
		
		if ( $order ) {
			$payment_sent = $this->is_email_sent( $order, 'payment' );
			$processing_sent = $this->is_email_sent( $order, 'processing' );
		}
		
		// Füge nur E-Mails hinzu, die noch nicht gesendet wurden
		if ( ! $payment_sent ) {
			$actions['send_custom_email'] = 'Zahlungsdaten neu senden';
		}
		
		if ( ! $processing_sent ) {
			$actions['send_order_processing_email'] = 'Bestellung in Bearbeitung Fehler';
		}
		
		return $actions;
	}
	
	/**
	 * Verarbeite benutzerdefinierte Order Action (Zahlungsdaten)
	 */
	public function process_custom_order_action( $order ) {
		$this->send_custom_email( $order, 'payment', 'Zahlungsdaten' );
	}
	
	/**
	 * Verarbeite Order Processing Action (Bestellung in Bearbeitung Fehler)
	 */
	public function process_order_processing_action( $order ) {
		$this->send_custom_email( $order, 'processing', 'Bestellung in Bearbeitung Fehler' );
	}
	
	/**
	 * Prüfe ob eine E-Mail bereits gesendet wurde
	 */
	private function is_email_sent( $order, $email_type ) {
		if ( ! $order ) {
			return false;
		}
		
		$meta_key = '_wc_custom_email_sent_' . $email_type;
		$sent = $order->get_meta( $meta_key );
		
		return ! empty( $sent ) && $sent === 'yes';
	}
	
	/**
	 * Markiere E-Mail als gesendet
	 */
	private function mark_email_as_sent( $order, $email_type ) {
		if ( ! $order ) {
			return;
		}
		
		$meta_key = '_wc_custom_email_sent_' . $email_type;
		$order->update_meta_data( $meta_key, 'yes' );
		$order->save();
	}
	
	/**
	 * Sende benutzerdefinierte E-Mail
	 */
	private function send_custom_email( $order, $email_type, $email_type_label ) {
		if ( ! $order ) {
			return;
		}
		
		// Prüfe ob E-Mail bereits gesendet wurde
		if ( $this->is_email_sent( $order, $email_type ) ) {
			// Speichere Fehler-Notice in Transient für Admin-Notice
			set_transient( 'wc_custom_email_notice_' . get_current_user_id(), array(
				'type' => 'error',
				'message' => sprintf( __( 'Die E-Mail "%s" wurde bereits an diesen Kunden gesendet und kann nicht erneut versendet werden.', 'wc-custom-order-email' ), $email_type_label )
			), 30 );
			
			// Weiterleitung zur Bestellungs-Detailseite
			$this->redirect_to_order( $order );
			return;
		}
		
		// Hole die ausgewählte Sprache (Standard: Deutsch)
		$selected_lang = isset( $_POST['wc_custom_email_language'] ) ? sanitize_text_field( $_POST['wc_custom_email_language'] ) : 'de';
		
		// Validiere Sprache
		$allowed_languages = array( 'de', 'en', 'fr' );
		if ( ! in_array( $selected_lang, $allowed_languages ) ) {
			$selected_lang = 'de';
		}
		
		// Hole E-Mail-Template
		$subject = get_option( 'wc_custom_email_subject_' . $email_type . '_' . $selected_lang, '' );
		$content = get_option( 'wc_custom_email_content_' . $email_type . '_' . $selected_lang, '' );
		
		if ( empty( $subject ) || empty( $content ) ) {
			// Speichere Fehler-Notice in Transient für Admin-Notice
			set_transient( 'wc_custom_email_notice_' . get_current_user_id(), array(
				'type' => 'error',
				'message' => sprintf( __( 'E-Mail-Template für "%s" in der ausgewählten Sprache wurde nicht gefunden. Bitte konfigurieren Sie die E-Mail-Templates in den Einstellungen.', 'wc-custom-order-email' ), $email_type_label )
			), 30 );
			
			// Weiterleitung zur Bestellungs-Detailseite
			$this->redirect_to_order( $order );
			return;
		}
		
		// Ersetze Platzhalter
		$subject = $this->replace_placeholders( $subject, $order );
		$content = $this->replace_placeholders( $content, $order );
		
		// E-Mail-Versand
		$to = $order->get_billing_email();
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		
		$sent = wp_mail( $to, $subject, $content, $headers );
		
		if ( $sent ) {
			// Markiere E-Mail als gesendet
			$this->mark_email_as_sent( $order, $email_type );
			
			// Füge Notiz zur Bestellung hinzu
			$order->add_order_note( sprintf( __( '%s E-Mail wurde an %s gesendet (Sprache: %s)', 'wc-custom-order-email' ), $email_type_label, $to, strtoupper( $selected_lang ) ) );
			
			// Speichere Erfolgs-Notice in Transient für Admin-Notice
			set_transient( 'wc_custom_email_notice_' . get_current_user_id(), array(
				'type' => 'success',
				'message' => sprintf( __( '%s E-Mail wurde erfolgreich an %s gesendet.', 'wc-custom-order-email' ), $email_type_label, $to )
			), 30 );
		} else {
			// Speichere Fehler-Notice in Transient für Admin-Notice
			set_transient( 'wc_custom_email_notice_' . get_current_user_id(), array(
				'type' => 'error',
				'message' => __( 'Fehler beim Versenden der E-Mail.', 'wc-custom-order-email' )
			), 30 );
		}
		
		// Weiterleitung zur Bestellungs-Detailseite
		$this->redirect_to_order( $order );
	}
	
	/**
	 * Weiterleitung zur Bestellungs-Detailseite
	 */
	private function redirect_to_order( $order ) {
		if ( ! $order ) {
			return;
		}
		
		// Unterstütze sowohl HPOS als auch klassische Orders
		$order_id = $order->get_id();
		$redirect_url = '';
		
		// Prüfe ob HPOS aktiviert ist (WooCommerce 8.0+)
		$is_hpos = false;
		if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
			$is_hpos = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
		}
		
		if ( $is_hpos ) {
			// HPOS aktiviert - verwende neue URL-Struktur
			$redirect_url = admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $order_id );
		} else {
			// Klassische Orders - verwende Post-Edit-URL
			$redirect_url = admin_url( 'post.php?post=' . $order_id . '&action=edit' );
		}
		
		// Weiterleitung durchführen
		wp_safe_redirect( $redirect_url );
		exit;
	}
	
	/**
	 * Ersetze Platzhalter im Text
	 */
	private function replace_placeholders( $text, $order ) {
		// Hole Bestellpositionen-Namen
		$item_names = array();
		foreach ( $order->get_items() as $item_id => $item ) {
			$item_names[] = $item->get_name();
		}
		$item_names_string = implode( ', ', $item_names );
		
		$placeholders = array(
			'{order_number}'         => $order->get_order_number(),
			'{customer_name}'       => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
			'{customer_first_name}' => $order->get_billing_first_name(),
			'{customer_email}'      => $order->get_billing_email(),
			'{order_date}'          => wc_format_datetime( $order->get_date_created() ),
			'{order_total}'         => $order->get_formatted_order_total(),
			'{billing_address}'     => $order->get_formatted_billing_address(),
			'{shipping_address}'    => $order->get_formatted_shipping_address(),
			'{order_items}'         => $this->get_order_items_html( $order ),
			'{wc-order-item-name}' => $item_names_string,
		);
		
		foreach ( $placeholders as $placeholder => $replacement ) {
			$text = str_replace( $placeholder, $replacement, $text );
		}
		
		return $text;
	}
	
	/**
	 * Hole Bestellpositionen als HTML
	 */
	private function get_order_items_html( $order ) {
		$items_html = '<table style="width: 100%; border-collapse: collapse;">';
		$items_html .= '<thead><tr style="background-color: #f5f5f5;">';
		$items_html .= '<th style="padding: 10px; border: 1px solid #ddd; text-align: left;">' . __( 'Produkt', 'wc-custom-order-email' ) . '</th>';
		$items_html .= '<th style="padding: 10px; border: 1px solid #ddd; text-align: right;">' . __( 'Menge', 'wc-custom-order-email' ) . '</th>';
		$items_html .= '<th style="padding: 10px; border: 1px solid #ddd; text-align: right;">' . __( 'Preis', 'wc-custom-order-email' ) . '</th>';
		$items_html .= '</tr></thead><tbody>';
		
		foreach ( $order->get_items() as $item_id => $item ) {
			$product = $item->get_product();
			$items_html .= '<tr>';
			$items_html .= '<td style="padding: 10px; border: 1px solid #ddd;">' . $item->get_name() . '</td>';
			$items_html .= '<td style="padding: 10px; border: 1px solid #ddd; text-align: right;">' . $item->get_quantity() . '</td>';
			$items_html .= '<td style="padding: 10px; border: 1px solid #ddd; text-align: right;">' . wc_price( $item->get_total() ) . '</td>';
			$items_html .= '</tr>';
		}
		
		$items_html .= '</tbody></table>';
		
		return $items_html;
	}
	
	/**
	 * Enqueue Admin Scripts
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Prüfe ob wir auf einer WooCommerce Order-Seite sind
		$screen = get_current_screen();
		
		// Für WooCommerce HPOS (High-Performance Order Storage)
		if ( function_exists( 'wc_get_page_screen_id' ) ) {
			$order_screen_id = wc_get_page_screen_id( 'shop-order' );
			if ( $screen && $screen->id === $order_screen_id ) {
				$this->load_admin_assets();
				return;
			}
		}
		
		// Für klassische Order-Edit-Seite
		if ( 'post.php' === $hook || 'post-new.php' === $hook ) {
			global $post;
			if ( $post && 'shop_order' === $post->post_type ) {
				$this->load_admin_assets();
			}
		}
	}
	
	/**
	 * Lade Admin-Assets
	 */
	private function load_admin_assets() {
		wp_enqueue_script(
			'wc-custom-order-email-admin',
			WC_CUSTOM_ORDER_EMAIL_PLUGIN_URL . 'assets/admin.js',
			array( 'jquery' ),
			WC_CUSTOM_ORDER_EMAIL_VERSION,
			true
		);
		
		wp_add_inline_style( 'woocommerce_admin_styles', '
			#wc_custom_email_language_wrapper {
				padding: 10px 0;
			}
			#wc_custom_email_language_wrapper label {
				display: block;
				margin-bottom: 5px;
			}
		' );
	}
	
	/**
	 * Zeige Admin Notices an
	 */
	public function display_admin_notices() {
		$notice = get_transient( 'wc_custom_email_notice_' . get_current_user_id() );
		
		if ( $notice && is_array( $notice ) ) {
			$class = 'notice notice-' . esc_attr( $notice['type'] ) . ' is-dismissible';
			$message = esc_html( $notice['message'] );
			
			printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
			
			// Lösche den Transient nach dem Anzeigen
			delete_transient( 'wc_custom_email_notice_' . get_current_user_id() );
		}
	}
	
	/**
	 * AJAX: Hole Sprachoptionen für Dropdown
	 */
	public function ajax_get_language_options() {
		check_ajax_referer( 'wc_custom_email_nonce', 'nonce' );
		
		$languages = array(
			'de' => __( 'Deutsch', 'wc-custom-order-email' ),
			'en' => __( 'Englisch', 'wc-custom-order-email' ),
			'fr' => __( 'Französisch', 'wc-custom-order-email' ),
		);
		
		wp_send_json_success( $languages );
	}
}

// Initialisiere Plugin
function wc_custom_order_email_init() {
	return WC_Custom_Order_Email::get_instance();
}

// Starte Plugin nach WooCommerce
add_action( 'woocommerce_loaded', 'wc_custom_order_email_init' );

