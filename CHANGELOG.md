# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [2.0.0] - 2026-08-02

### Changed
- Rebuilt both emails on top of WooCommerce's own `WC_Email` class, so they now appear under **WooCommerce → Settings → Emails** with native enable/disable, email-type (HTML/plain/multipart), and shared header/footer branding — replacing the old bespoke settings page.
- Replaced the fragile "order actions dropdown + guessed order ID" send mechanism with a dedicated meta box on the order screen, backed by explicit `admin-post.php` handlers.
- Settings are now stored as one option per email (via `WC_Settings_API`) instead of 12 separate options.
- All admin-facing strings translated to English; German/English/French remain as the three customer-facing template languages.

### Added
- Orders list column showing which custom emails have been sent per order.
- Bulk action to send the "Order Processing Error" email to multiple orders at once.
- "Allow resending" control to clear the once-per-order send lock without editing order meta directly.
- Placeholder-insertion chips, a "copy content between languages" toolbar, a client-side live preview, and a "Send Test Email" action on the email settings screens.
- Proper plain-text alternative body for both emails (multipart-capable).
- `uninstall.php` cleanup and a "Settings" link on the Plugins list page.

### Fixed
- Removed a forced redirect that ran mid-way through WooCommerce's own order-save request, which could interfere with other plugins hooked into the same save.
- Placeholder values are now escaped for HTML context to avoid breaking email markup.

---

## [1.0.0] - 2026-03-28

### Added
- Initial public release
- Custom email actions added to WooCommerce order admin panel
  - Resend Payment Details
  - Order Processing Error Notification
- Multi-language support for German (`de`), English (`en`), and French (`fr`)
- Fully customizable email subject and content via TinyMCE WYSIWYG editor
- Dynamic placeholders for order data (`{order_number}`, `{customer_name}`, `{order_items}`, etc.)
- Duplicate email prevention — each email type can only be sent once per order
- Automatic logging of sent emails in WooCommerce order notes
- Dedicated admin settings page under **WooCommerce → Custom E-Mails**
- Tab-based admin UI with email type and language tabs
- AJAX-powered language switching in the admin interface
- Support for both Classic WooCommerce order storage and HPOS (High-Performance Order Storage)
- Nonce-based security for all form and AJAX interactions
- Admin notices for success, errors, and missing templates
- Emails sent via WordPress `wp_mail()` with full HTML support

---

## [Unreleased]

### Planned
- Add more languages dynamically
- Support for email attachments
- Conditional/automatic triggers (e.g. auto-send on a status change) alongside the manual actions
