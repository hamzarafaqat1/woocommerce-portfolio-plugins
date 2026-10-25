<?php
/**
 * Plugin Name:       WC Checkout Field Extras
 * Description:       Adds configurable checkout fields (VAT / delivery notes) and shows them on orders and emails.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Hamza R.
 * License:           GPL-2.0-or-later
 * Text Domain:       wc-checkout-field-extras
 * Requires Plugins:  woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCCFE_PATH', plugin_dir_path( __FILE__ ) );
require_once WCCFE_PATH . 'includes/class-wccfe-plugin.php';

add_action(
	'plugins_loaded',
	static function () {
		if ( class_exists( 'WooCommerce' ) ) {
			WCCFE_Plugin::init();
		}
	}
);
