<?php
/**
 * Plugin Name:       WC Subscriptions Activity Log
 * Description:       Append-only activity log for subscription lifecycle events (created, status changed, renewed, cancelled) for support and audits.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Hamza R.
 * License:           GPL-2.0-or-later
 * Text Domain:       wc-subscriptions-activity-log
 * Requires Plugins:  woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCSAL_PATH', plugin_dir_path( __FILE__ ) );
require_once WCSAL_PATH . 'includes/class-wcsal-plugin.php';

add_action(
	'plugins_loaded',
	static function () {
		if ( class_exists( 'WooCommerce' ) ) {
			WCSAL_Plugin::init();
		}
	}
);

register_activation_hook(
	__FILE__,
	static function () {
		require_once WCSAL_PATH . 'includes/class-wcsal-plugin.php';
		WCSAL_Plugin::install();
	}
);
