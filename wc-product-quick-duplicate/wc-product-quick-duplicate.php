<?php
/**
 * Plugin Name:       WC Product Quick Duplicate
 * Description:       One-click duplicate action on the WooCommerce products list (draft copy with “- copy” suffix).
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Hamza R.
 * License:           GPL-2.0-or-later
 * Text Domain:       wc-product-quick-duplicate
 * Requires Plugins:  woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCPQD_PATH', plugin_dir_path( __FILE__ ) );
require_once WCPQD_PATH . 'includes/class-wcpqd-plugin.php';

add_action(
	'plugins_loaded',
	static function () {
		if ( class_exists( 'WooCommerce' ) ) {
			WCPQD_Plugin::init();
		}
	}
);
