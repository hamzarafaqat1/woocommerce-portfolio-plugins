# WC Subscriptions Member Documents

Portfolio / reference plugin for WooCommerce Subscriptions membership sites.

Demonstrates a production-style approach to:

- Versioned document templates (agreements, notices)
- Recording member acceptance (IP + user agent)
- PDF generation + per-member archive
- Email delivery of PDFs
- Member documents page (`[wcsmd_documents]`)
- CSV migration from a legacy plugin export
- REST helpers for staging QA
- PHPUnit tests for PDF generation and schema helpers

## Requirements

- WordPress 6+
- WooCommerce
- WooCommerce Subscriptions (optional but used for linking active subscriptions on accept)

## Install

1. Copy `wc-subscriptions-member-documents` into `wp-content/plugins/`
2. Activate
3. **WooCommerce → Member Docs** to create a template version
4. Add shortcode `[wcsmd_documents]` on a page

## Tokens

`{{name}}` `{{email}}` `{{subscription_id}}` `{{date}}` `{{version}}`

## Tests

```bash
composer require --dev phpunit/phpunit
# With WP test suite:
WP_TESTS_DIR=/path/to/wordpress-tests-lib vendor/bin/phpunit
```

## License

GPL-2.0-or-later
