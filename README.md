# WooCommerce Custom Order Email

![WordPress](https://img.shields.io/badge/WordPress-%E2%89%A55.0-blue) ![WooCommerce](https://img.shields.io/badge/WooCommerce-%E2%89%A55.0-purple) ![PHP](https://img.shields.io/badge/PHP-%E2%89%A57.4-777BB4) ![License](https://img.shields.io/badge/License-GPL--2.0-green)

A flexible WooCommerce plugin that adds custom order email actions with multi-language support, allowing store administrators to manually send tailored emails to customers directly from the order admin panel.

---

## ✨ Features

- 📧 Add custom email actions to WooCommerce orders
- 🌍 Multi-language support (German, English, French)
- 📝 Fully customizable email subject and content
- 🔄 Dynamic placeholders for order data
- 🚫 Prevents duplicate email sending
- 🧾 Automatically logs emails in order notes
- ⚙️ Dedicated admin settings page
- 🧠 Supports both Classic WooCommerce order storage and HPOS (High-Performance Order Storage)
- ⚡ AJAX-powered language handling in admin UI

---

## 📌 Available Email Actions

| Action | Description |
|---|---|
| **Resend Payment Details** | Resends payment info to the customer |
| **Order Processing Error Notification** | Notifies the customer of a processing issue |

These actions appear in the order actions dropdown and can be triggered manually by the admin.

---

## 🌐 Multi-Language Support

- 🇩🇪 German (`de`)
- 🇬🇧 English (`en`)
- 🇫🇷 French (`fr`)

Each email type can be configured independently per language.

---

## 📦 Installation

1. Upload the plugin folder to `/wp-content/plugins/woocommerce-custom-order-email`
2. Activate the plugin via **WordPress Admin → Plugins**
3. Ensure WooCommerce is installed and active
4. Go to **WooCommerce → Custom E-Mails** to configure your templates

---

## ⚙️ Configuration

Navigate to **WooCommerce → Custom E-Mails** in your WordPress admin. You can configure:

- Email subject
- Email content (with WYSIWYG / TinyMCE editor)
- Separate templates per email type and language

---

## 🧩 Available Placeholders

### General
```
{order_number}
{order_date}
{order_total}
```

### Customer Info
```
{customer_name}
{customer_first_name}
{customer_email}
```

### Address Info
```
{billing_address}
{shipping_address}
```

### Order Items
```
{order_items}           → renders a full HTML table
{wc-order-item-name}    → comma-separated product names
```

---

## 📬 Email Behavior

- Emails are sent using WordPress `wp_mail()`
- HTML format is supported
- Each email type is sent **only once per order**
- Sent emails are logged in order notes and marked via order meta to prevent resending

---

## 🔒 Safety & Validation

- Prevents duplicate email sending
- Sanitizes all user inputs
- Uses nonces for form and AJAX security
- Displays admin notices for success, errors, and missing templates

---

## 🧠 Technical Overview

### Architecture
- Singleton pattern for main plugin class
- Hook-based integration with WooCommerce
- Modular structure using WordPress APIs

### Key Hooks Used

```php
woocommerce_order_actions
woocommerce_order_action_*
admin_menu
admin_init
admin_enqueue_scripts
wp_ajax_*
```

### Compatibility

| Requirement | Version |
|---|---|
| WordPress | ≥ 5.0 |
| PHP | ≥ 7.4 |
| WooCommerce | ≥ 5.0 (tested up to 8.0) |

---

## 📁 Admin UI Features

- Tab-based interface (email type tabs + language tabs)
- Rich text editor (TinyMCE)
- AJAX-powered language switching
- Inline styling for better UX

---

## ⚠️ Notes

- Emails will not send if subject or content is missing
- Each email type can only be sent once per order
- Language selection defaults to German if not provided

---

## 🚀 Future Improvements

- [ ] Add more languages dynamically
- [ ] Support for email attachments
- [ ] Email preview functionality
- [ ] Integration with WooCommerce email templates
- [ ] Conditional logic for sending emails

---

## 🤝 Contributing

Contributions are welcome! Please open an issue first to discuss what you'd like to change, then submit a pull request.

---

## 📄 License

This plugin is licensed under the [GPL-2.0 License](LICENSE).
