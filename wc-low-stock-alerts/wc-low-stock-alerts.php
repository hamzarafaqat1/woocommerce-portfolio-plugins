<?php
/**
 * Plugin Name:       WC Low Stock Alerts
 * Description:       Emails store managers when a product stock quantity drops to or below a configurable threshold.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Hamza R.
 * License:           GPL-2.0-or-later
 * Text Domain:       wc-low-stock-alerts
 * Requires Plugins:  woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCLSA_PATH', plugin_dir_path( __FILE__ ) );
require_once WCLSA_PATH . 'includes/class-wclsa-plugin.php';

add_action(
	'plugins_loaded',
	static function () {
		if ( class_exists( 'WooCommerce' ) ) {
			WCLSA_Plugin::init();
		}
	}
);
