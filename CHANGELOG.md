# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
- Email preview functionality
- Integration with WooCommerce email templates
- Conditional logic for sending emails
