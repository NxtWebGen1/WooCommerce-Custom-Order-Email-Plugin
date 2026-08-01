<?php
/**
 * Abstract base class for the plugin's multi-language order emails.
 *
 * Extends WC_Email so each email type gets a native entry under
 * WooCommerce -> Settings -> Emails (enable toggle, recipient handling,
 * shared header/footer template) instead of a bespoke settings screen.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class WC_Custom_Order_Email extends WC_Email {

	/**
	 * Supported content languages.
	 *
	 * @var array
	 */
	protected $languages = array(
		'de' => 'German',
		'en' => 'English',
		'fr' => 'French',
	);

	/**
	 * Suffix used for the "already sent" order meta key. Set by subclasses.
	 *
	 * @var string
	 */
	protected $meta_suffix = '';

	/**
	 * Language selected for the email currently being triggered/rendered.
	 *
	 * @var string
	 */
	protected $current_language = 'en';

	/**
	 * Subclasses must return the built-in default subject for a language.
	 */
	abstract public function get_default_subject( $language );

	/**
	 * Subclasses must return the built-in default heading for a language.
	 */
	abstract public function get_default_heading( $language );

	/**
	 * Subclasses must return the built-in default content for a language.
	 */
	abstract public function get_default_content( $language );

	public function __construct() {
		$this->customer_email = true;
		$this->template_html  = 'emails/custom-order-email.php';
		$this->template_plain = 'emails/plain/custom-order-email.php';
		$this->template_base  = WC_CUSTOM_ORDER_EMAIL_PLUGIN_DIR . 'templates/';

		parent::__construct();
	}

	/**
	 * Build the per-language settings fields.
	 */
	public function init_form_fields() {
		$fields = array(
			'enabled'    => array(
				'title'   => __( 'Enable/Disable', 'wc-custom-order-email' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email', 'wc-custom-order-email' ),
				'default' => 'yes',
			),
			'email_type' => array(
				'title'       => __( 'Email type', 'wc-custom-order-email' ),
				'type'        => 'select',
				'description' => __( 'Choose which format the email is sent in.', 'wc-custom-order-email' ),
				'default'     => 'html',
				'class'       => 'email_type wc-enhanced-select',
				'options'     => $this->get_email_type_options(),
				'desc_tip'    => true,
			),
		);

		foreach ( $this->languages as $lang_code => $lang_label ) {
			$fields[ 'subject_' . $lang_code ] = array(
				/* translators: %s: language name */
				'title'       => sprintf( __( 'Subject (%s)', 'wc-custom-order-email' ), $lang_label ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __( 'Placeholders: {order_number}, {customer_name}, {customer_first_name}, {order_date}, {order_total}, {wc-order-item-name}', 'wc-custom-order-email' ),
				'default'     => $this->get_default_subject( $lang_code ),
			);

			$fields[ 'heading_' . $lang_code ] = array(
				/* translators: %s: language name */
				'title'       => sprintf( __( 'Email heading (%s)', 'wc-custom-order-email' ), $lang_label ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __( 'The heading shown at the top of the email body (same placeholders as the subject).', 'wc-custom-order-email' ),
				'default'     => $this->get_default_heading( $lang_code ),
			);

			$fields[ 'content_' . $lang_code ] = array(
				/* translators: %s: language name */
				'title'       => sprintf( __( 'Content (%s)', 'wc-custom-order-email' ), $lang_label ),
				'type'        => 'custom_wysiwyg',
				'description' => __( 'Placeholders: {order_number}, {customer_name}, {customer_first_name}, {customer_email}, {order_date}, {order_total}, {billing_address}, {shipping_address}, {order_items}, {wc-order-item-name}', 'wc-custom-order-email' ),
				'default'     => $this->get_default_content( $lang_code ),
			);
		}

		$this->form_fields = $fields;
	}

	/**
	 * Render a TinyMCE editor field on the WooCommerce email settings screen.
	 */
	public function generate_custom_wysiwyg_html( $key, $data ) {
		$field_key        = $this->get_field_key( $key );
		$value             = $this->get_option( $key );
		$lang_code         = preg_replace( '/^content_/', '', $key );
		$subject_field_key = $this->get_field_key( 'subject_' . $lang_code );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
			</th>
			<td class="forminp">
				<div class="wc-custom-order-email-placeholder-chips" data-target="<?php echo esc_attr( $field_key ); ?>" data-target-type="wysiwyg"></div>
				<?php
				wp_editor(
					$value,
					$field_key,
					array(
						'textarea_name' => $field_key,
						'textarea_rows' => 12,
						'media_buttons' => true,
						'tinymce'       => true,
					)
				);

				if ( ! empty( $data['description'] ) ) {
					echo '<p class="description">' . wp_kses_post( $data['description'] ) . '</p>';
				}
				?>
				<p class="wc-custom-order-email-actions">
					<button type="button" class="button wc-custom-order-email-preview-btn"
						data-content-target="<?php echo esc_attr( $field_key ); ?>"
						data-subject-target="<?php echo esc_attr( $subject_field_key ); ?>">
						<?php esc_html_e( 'Preview', 'wc-custom-order-email' ); ?>
					</button>
					<button type="button" class="button wc-custom-order-email-test-send-btn"
						data-email-id="<?php echo esc_attr( $this->id ); ?>"
						data-lang="<?php echo esc_attr( $lang_code ); ?>"
						data-content-target="<?php echo esc_attr( $field_key ); ?>"
						data-subject-target="<?php echo esc_attr( $subject_field_key ); ?>">
						<?php esc_html_e( 'Send Test Email', 'wc-custom-order-email' ); ?>
					</button>
					<span class="wc-custom-order-email-test-send-result"></span>
				</p>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Sanitize the TinyMCE editor field on save.
	 */
	public function validate_custom_wysiwyg_field( $key, $value ) {
		return wp_kses_post( wp_unslash( $value ) );
	}

	/**
	 * Add the placeholder-chip toolbar to the plain subject/heading text fields too.
	 * WC_Settings_API doesn't give us a hook per-row, so we post-process the
	 * generated table row markup for our own fields only.
	 */
	public function generate_settings_html( $form_fields = array(), $echo = true ) {
		$form_fields = empty( $form_fields ) ? $this->get_form_fields() : $form_fields;
		$html        = parent::generate_settings_html( $form_fields, false );

		foreach ( $form_fields as $key => $field ) {
			if ( 'text' !== $field['type'] || ( 0 !== strpos( $key, 'subject_' ) && 0 !== strpos( $key, 'heading_' ) ) ) {
				continue;
			}

			$field_key = $this->get_field_key( $key );
			$needle    = 'id="' . esc_attr( $field_key ) . '"';
			$pos       = strpos( $html, $needle );

			if ( false === $pos ) {
				continue;
			}

			$insert_at = strpos( $html, '>', $pos ) + 1;
			$chips     = '<div class="wc-custom-order-email-placeholder-chips" data-target="' . esc_attr( $field_key ) . '" data-target-type="text"></div>';
			$html      = substr_replace( $html, $chips, $insert_at, 0 );
		}

		if ( $echo ) {
			echo $html; // phpslint-note: already-escaped settings markup built by WooCommerce/WordPress APIs above.
		}

		return $html;
	}

	/**
	 * Whether this email has already been sent for the given order.
	 */
	public function is_already_sent( $order ) {
		if ( ! $order ) {
			return false;
		}

		$sent = $order->get_meta( $this->get_sent_meta_key() );

		return ! empty( $sent ) && 'yes' === $sent;
	}

	/**
	 * Mark this email as sent for the given order, recording when and in
	 * which language so the admin UI can display it later.
	 */
	protected function mark_as_sent( $order ) {
		$order->update_meta_data( $this->get_sent_meta_key(), 'yes' );
		$order->update_meta_data( $this->get_sent_meta_key() . '_info', array(
			'date'     => current_time( 'mysql' ),
			'language' => $this->current_language,
		) );
		$order->save();
	}

	/**
	 * Details recorded when this email was last sent for the given order.
	 *
	 * @return array{date?:string,language?:string}
	 */
	public function get_sent_info( $order ) {
		$info = $order->get_meta( $this->get_sent_meta_key() . '_info' );

		return is_array( $info ) ? $info : array();
	}

	/**
	 * Clear the "already sent" flag so the email can be sent again.
	 */
	public function reset_sent_flag( $order ) {
		$order->delete_meta_data( $this->get_sent_meta_key() );
		$order->delete_meta_data( $this->get_sent_meta_key() . '_info' );
		$order->save();
	}

	/**
	 * Order meta key used to track whether this email type was already sent.
	 */
	public function get_sent_meta_key() {
		return '_wc_custom_email_sent_' . $this->meta_suffix;
	}

	/**
	 * Supported languages, keyed by code.
	 */
	public function get_languages() {
		return $this->languages;
	}

	/**
	 * Send this email for a given order, in a given language.
	 *
	 * Returns a result code so callers can show an accurate admin notice:
	 * 'sent', 'already_sent', 'no_recipient', 'disabled', or 'not_found'.
	 */
	public function trigger( $order_id, $language = 'en' ) {
		$this->current_language = isset( $this->languages[ $language ] ) ? $language : 'en';

		if ( ! $order_id ) {
			return 'not_found';
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return 'not_found';
		}

		$this->object    = $order;
		$this->recipient = $order->get_billing_email();

		if ( ! $this->recipient ) {
			return 'no_recipient';
		}

		if ( $this->is_already_sent( $order ) ) {
			return 'already_sent';
		}

		if ( ! $this->is_enabled() ) {
			return 'disabled';
		}

		$this->setup_locale();

		$sent = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

		$this->restore_locale();

		if ( ! $sent ) {
			return 'mail_failed';
		}

		$this->mark_as_sent( $order );

		$order->add_order_note( sprintf(
			/* translators: 1: email title, 2: recipient email, 3: language code */
			__( '%1$s email was sent to %2$s (language: %3$s)', 'wc-custom-order-email' ),
			$this->get_title(),
			$this->recipient,
			strtoupper( $this->current_language )
		) );

		return 'sent';
	}

	public function get_subject() {
		$subject = $this->get_option( 'subject_' . $this->current_language );
		$subject = $this->replace_placeholders( $subject, 'text' );

		return $this->format_string( $subject );
	}

	public function get_heading() {
		$heading = $this->get_option( 'heading_' . $this->current_language );
		$heading = $this->replace_placeholders( $heading, 'text' );

		return $this->format_string( $heading );
	}

	public function get_content_html() {
		$content = $this->get_option( 'content_' . $this->current_language );
		$content = $this->replace_placeholders( $content, 'html' );
		$content = $this->format_string( $content );

		return wc_get_template_html(
			$this->template_html,
			array(
				'email_heading' => $this->get_heading(),
				'content'       => $content,
				'sent_to_admin' => false,
				'plain_text'    => false,
				'email'         => $this,
			),
			'',
			$this->template_base
		);
	}

	public function get_content_plain() {
		$content = $this->get_option( 'content_' . $this->current_language );
		$content = $this->replace_placeholders( $content, 'text' );
		$content = $this->format_string( $content );
		$content = $this->html_blocks_to_plain_text( $content );

		return wc_get_template_html(
			$this->template_plain,
			array(
				'email_heading' => $this->get_heading(),
				'content'       => $content,
				'sent_to_admin' => false,
				'plain_text'    => true,
				'email'         => $this,
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Turn WYSIWYG-authored HTML into readable plain text: line/paragraph
	 * breaks first, then strip whatever tags remain.
	 */
	protected function html_blocks_to_plain_text( $content ) {
		$content = preg_replace( '#<br\s*/?>#i', "\n", $content );
		$content = preg_replace( '#</(p|div|h[1-6]|li|tr)>#i', "\n\n", $content );
		$content = wp_strip_all_tags( $content );
		$content = preg_replace( "/\n{3,}/", "\n\n", trim( $content ) );

		return $content;
	}

	/**
	 * Render a subject/content pair against a real order without touching
	 * saved settings or the "already sent" flag. Used for the settings-screen
	 * "Send test email" button so admins can try out unsaved edits.
	 */
	public function render_preview( $order, $language, $subject_raw, $content_raw ) {
		$this->object           = $order;
		$this->current_language = isset( $this->languages[ $language ] ) ? $language : 'en';

		return array(
			'subject' => $this->format_string( $this->replace_placeholders( $subject_raw, 'text' ) ),
			'content' => $this->format_string( $this->replace_placeholders( $content_raw, 'html' ) ),
		);
	}

	/**
	 * Replace {placeholder} tokens with order data.
	 *
	 * @param string $text    Template text.
	 * @param string $context 'html' escapes scalar values for HTML output;
	 *                        'text' strips tags, for the subject/heading and
	 *                        the plain-text alternative body.
	 */
	protected function replace_placeholders( $text, $context = 'html' ) {
		$order = $this->object;

		if ( ! $order instanceof WC_Order ) {
			return $text;
		}

		$is_html = ( 'html' === $context );

		$item_names = array();
		foreach ( $order->get_items() as $item ) {
			$item_names[] = $is_html ? esc_html( $item->get_name() ) : wp_strip_all_tags( $item->get_name() );
		}

		$scalar = function( $value ) use ( $is_html ) {
			return $is_html ? esc_html( $value ) : wp_strip_all_tags( $value );
		};

		$placeholders = array(
			'{order_number}'        => $scalar( $order->get_order_number() ),
			'{customer_name}'       => $scalar( trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ) ),
			'{customer_first_name}' => $scalar( $order->get_billing_first_name() ),
			'{customer_email}'      => $scalar( $order->get_billing_email() ),
			'{order_date}'          => $scalar( $order->get_date_created() ? wc_format_datetime( $order->get_date_created() ) : '' ),
			'{order_total}'         => $is_html ? $order->get_formatted_order_total() : wp_strip_all_tags( $order->get_formatted_order_total() ),
			'{billing_address}'     => $is_html ? $order->get_formatted_billing_address() : wp_strip_all_tags( $order->get_formatted_billing_address() ),
			'{shipping_address}'    => $is_html ? $order->get_formatted_shipping_address() : wp_strip_all_tags( $order->get_formatted_shipping_address() ),
			'{order_items}'         => $is_html ? $this->get_order_items_html( $order ) : $this->get_order_items_plain( $order ),
			'{wc-order-item-name}'  => implode( ', ', $item_names ),
		);

		return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $text );
	}

	/**
	 * {order_items} placeholder — HTML table.
	 */
	protected function get_order_items_html( $order ) {
		$html  = '<table style="width: 100%; border-collapse: collapse;">';
		$html .= '<thead><tr style="background-color: #f5f5f5;">';
		$html .= '<th style="padding: 10px; border: 1px solid #ddd; text-align: left;">' . esc_html__( 'Product', 'wc-custom-order-email' ) . '</th>';
		$html .= '<th style="padding: 10px; border: 1px solid #ddd; text-align: right;">' . esc_html__( 'Quantity', 'wc-custom-order-email' ) . '</th>';
		$html .= '<th style="padding: 10px; border: 1px solid #ddd; text-align: right;">' . esc_html__( 'Price', 'wc-custom-order-email' ) . '</th>';
		$html .= '</tr></thead><tbody>';

		foreach ( $order->get_items() as $item ) {
			$html .= '<tr>';
			$html .= '<td style="padding: 10px; border: 1px solid #ddd;">' . esc_html( $item->get_name() ) . '</td>';
			$html .= '<td style="padding: 10px; border: 1px solid #ddd; text-align: right;">' . esc_html( $item->get_quantity() ) . '</td>';
			$html .= '<td style="padding: 10px; border: 1px solid #ddd; text-align: right;">' . wc_price( $item->get_total() ) . '</td>';
			$html .= '</tr>';
		}

		$html .= '</tbody></table>';

		return $html;
	}

	/**
	 * {order_items} placeholder — plain-text alternative.
	 */
	protected function get_order_items_plain( $order ) {
		$lines = array();

		foreach ( $order->get_items() as $item ) {
			$lines[] = sprintf(
				'%1$s x %2$d - %3$s',
				wp_strip_all_tags( $item->get_name() ),
				$item->get_quantity(),
				wp_strip_all_tags( wc_price( $item->get_total() ) )
			);
		}

		return implode( "\n", $lines );
	}
}
