# WooCommerce Custom Order Email

![WordPress](https://img.shields.io/badge/WordPress-%E2%89%A55.0-blue) ![WooCommerce](https://img.shields.io/badge/WooCommerce-%E2%89%A55.0-purple) ![PHP](https://img.shields.io/badge/PHP-%E2%89%A57.4-777BB4) ![License](https://img.shields.io/badge/License-GPL--2.0-green)

A WooCommerce extension that adds two manually-triggered order emails - **Resend Payment Details** and **Order Processing Error** - each configurable in German, English, and French, and sent from a dedicated meta box on the order screen.

---

## ✨ Features

- 📧 Two order emails, built as native `WC_Email` classes
- ⚙️ Configured under **WooCommerce → Settings → Emails**, alongside every other WooCommerce email
- 🌍 Multi-language subject, heading, and content (German, English, French)
- 📝 WYSIWYG (TinyMCE) content editor with placeholder-insertion chips
- 🔁 "Copy content between languages" toolbar and a client-side live preview
- ✉️ "Send Test Email" button using your most recent real order
- 🚫 Each email can only be sent once per order, with an explicit "allow resending" override
- 🧾 Sent emails are logged in order notes and shown in an orders-list column
- 📦 Bulk action to send the processing-error email to multiple orders at once
- 🧠 Works with both Classic order storage and HPOS (High-Performance Order Storage)

---

## 📌 Available Emails

| Email | Description |
|---|---|
| **Resend Payment Details** | Resends payment info to the customer |
| **Order Processing Error** | Notifies the customer of a processing issue |

Both are sent from a **Custom Order Emails** meta box on the order edit screen: pick a language, click Send. There's no automatic trigger - these are always sent deliberately by an admin.

---

## 🌐 Multi-Language Support

- 🇩🇪 German (`de`)
- 🇬🇧 English (`en`)
- 🇫🇷 French (`fr`)

Each email has its own subject, heading, and content per language, all on one settings page (no tab navigation to lose unsaved edits).

---

## 📦 Installation

1. Upload the plugin folder to `/wp-content/plugins/woocommerce-custom-order-email`
2. Activate the plugin via **WordPress Admin → Plugins**
3. Ensure WooCommerce is installed and active
4. Go to **WooCommerce → Settings → Emails** and open **Resend Payment Details** or **Order Processing Error** to configure templates

---

## ⚙️ Configuration

Each email's settings screen (under **WooCommerce → Settings → Emails**) lets you configure:

- Enable/disable and email type (HTML, plain text, or multipart)
- Subject, heading, and content for each of the three languages
- Content via a TinyMCE editor, with placeholder chips and a preview/test-send button

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
{order_items}           → renders a full HTML table (plain-text emails get a bulleted list instead)
{wc-order-item-name}    → comma-separated product names
```

---

## 📬 Email Behavior

- Built on WooCommerce's `WC_Email`, so sending goes through `wc_mail()`/`wp_mail()` with the store's standard header/footer branding
- HTML, plain-text, and multipart formats are all supported
- Each email type is sent **only once per order** by default; use "Allow resending" in the order meta box to lift that
- Sending is recorded in an order note and in order meta (date + language), shown in the meta box and the orders list

---

## 🔒 Safety & Validation

- Nonce-protected send/reset actions and AJAX requests
- Capability-checked (`manage_woocommerce`) on every admin action
- Placeholder values are escaped for HTML output
- Orders without a billing email are skipped with a clear notice instead of silently failing

---

## 🧠 Technical Overview

### Architecture

- `WC_Custom_Order_Email` (abstract, extends `WC_Email`) - shared multi-language settings fields, placeholder handling, and send-once tracking
- `WC_Custom_Order_Email_Payment` / `WC_Custom_Order_Email_Processing` - the two concrete emails
- `WC_Custom_Order_Email_Orders` - order-screen meta box, admin-post send/reset handlers, orders-list column, bulk action
- `WC_Custom_Order_Email_Admin` - settings-screen JS/AJAX enhancements (placeholder chips, preview, test send)

### Key Hooks Used

```php
woocommerce_email_classes
woocommerce_email_header / woocommerce_email_footer
add_meta_boxes
admin_post_wc_custom_order_email_send / _reset
manage_edit-shop_order_columns / woocommerce_shop_order_list_table_columns
bulk_actions-edit-shop_order / bulk_actions-woocommerce_page_wc-orders
wp_ajax_wc_custom_order_email_test_send
```

### Compatibility

| Requirement | Version |
|---|---|
| WordPress | ≥ 5.0 |
| PHP | ≥ 7.4 |
| WooCommerce | ≥ 5.0 (tested up to 9.4) |

---

## ⚠️ Notes

- Emails won't send if the order has no billing email address
- Each email type can only be sent once per order unless explicitly reset
- The bulk-send action uses a default language (English), since there's no per-order language picker in bulk mode

---

## 🚀 Future Improvements

- [ ] Add more languages dynamically
- [ ] Support for email attachments
- [ ] Conditional/automatic triggers (e.g. auto-send on a status change) alongside the manual actions

---

## 🤝 Contributing

Contributions are welcome! Please open an issue first to discuss what you'd like to change, then submit a pull request.

---

## 📄 License

This plugin is licensed under the [GPL-2.0 License](LICENSE).
