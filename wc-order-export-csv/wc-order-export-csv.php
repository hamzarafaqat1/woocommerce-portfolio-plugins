<?php
/**
 * Plugin Name:       WC Order Export CSV
 * Description:       Export WooCommerce orders to CSV from wp-admin with date and status filters.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Hamza R.
 * License:           GPL-2.0-or-later
 * Text Domain:       wc-order-export-csv
 * Requires Plugins:  woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCOEC_PATH', plugin_dir_path( __FILE__ ) );
require_once WCOEC_PATH . 'includes/class-wcoec-plugin.php';

add_action(
	'plugins_loaded',
	static function () {
		if ( class_exists( 'WooCommerce' ) ) {
			WCOEC_Plugin::init();
		}
	}
);
