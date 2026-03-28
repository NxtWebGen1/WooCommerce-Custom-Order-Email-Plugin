WooCommerce Custom Order Email

A flexible WooCommerce plugin that adds custom order email actions with multi-language support, allowing store administrators to manually send tailored emails to customers directly from the order admin panel.

✨ Features
📧 Add custom email actions to WooCommerce orders
🌍 Multi-language support (German, English, French)
📝 Fully customizable email subject and content
🔄 Dynamic placeholders for order data
🚫 Prevents duplicate email sending
🧾 Automatically logs emails in order notes
⚙️ Dedicated admin settings page
🧠 Supports both:
Classic WooCommerce order storage
HPOS (High-Performance Order Storage)
⚡ AJAX-powered language handling in admin UI
📌 Available Email Actions

This plugin adds two custom actions to WooCommerce orders:

Resend Payment Details
Order Processing Error Notification

These actions appear in the order actions dropdown and can be triggered manually by the admin.

🌐 Multi-Language Support

The plugin supports the following languages:

German (de)
English (en)
French (fr)

Each email type can be configured independently per language.

⚙️ Configuration

Navigate to:

WooCommerce → Custom E-Mails

You can configure:
Email subject
Email content (with WYSIWYG editor)
Separate templates per:
Email type
Language
🧩 Available Placeholders

Use dynamic placeholders in your email templates:

General
{order_number}
{order_date}
{order_total}
Customer Info
{customer_name}
{customer_first_name}
{customer_email}
Address Info
{billing_address}
{shipping_address}
Order Items
{order_items} → renders a full HTML table
{wc-order-item-name} → comma-separated product names
📬 Email Behavior
Emails are sent using WordPress wp_mail()
HTML format is supported
Each email type is sent only once per order
Sent emails are:
Logged in order notes
Marked via order meta
Prevented from being resent
🔒 Safety & Validation
Prevents duplicate email sending
Sanitizes all user inputs
Uses nonces for form and AJAX security
Displays admin notices for:
Success
Errors
Missing templates
🧠 Technical Overview
Architecture
Singleton pattern for main plugin class
Hook-based integration with WooCommerce
Modular structure using WordPress APIs
Key Hooks Used
woocommerce_order_actions
woocommerce_order_action_*
admin_menu
admin_init
admin_enqueue_scripts
wp_ajax_*
Compatibility
WordPress ≥ 5.0
PHP ≥ 7.4
WooCommerce ≥ 5.0
Tested up to WooCommerce 8.0
📁 Admin UI Features
Tab-based interface:
Email type tabs
Language tabs
Rich text editor (TinyMCE)
Inline styling for better UX
🔄 Order Integration
Adds custom actions dynamically based on:
Whether email was already sent
Automatically detects:
HPOS or classic order system
Redirects correctly after action execution
📦 Installation
Upload the plugin files to /wp-content/plugins/wc-custom-order-email
Activate the plugin via WordPress admin
Ensure WooCommerce is installed and active
Go to WooCommerce → Custom E-Mails to configure templates
⚠️ Notes
Emails will not send if subject/content is missing
Each email type can only be sent once per order
Language selection defaults to German if not provided
🚀 Future Improvements (optional ideas)
Add more languages dynamically
Support for attachments
Email preview functionality
Integration with WooCommerce email templates
Conditional logic for sending emails
