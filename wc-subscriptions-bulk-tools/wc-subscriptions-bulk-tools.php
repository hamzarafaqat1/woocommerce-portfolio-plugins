<?php
/**
 * Plugin Name:       WC Subscriptions Bulk Tools
 * Description:       Admin bulk helpers for WooCommerce Subscriptions — export CSV and retry failed renewals in a controlled queue.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Hamza R.
 * License:           GPL-2.0-or-later
 * Text Domain:       wc-subscriptions-bulk-tools
 * Requires Plugins:  woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCSBT_PATH', plugin_dir_path( __FILE__ ) );
require_once WCSBT_PATH . 'includes/class-wcsbt-plugin.php';

add_action(
	'plugins_loaded',
	static function () {
		if ( class_exists( 'WooCommerce' ) ) {
			WCSBT_Plugin::init();
		}
	}
);
