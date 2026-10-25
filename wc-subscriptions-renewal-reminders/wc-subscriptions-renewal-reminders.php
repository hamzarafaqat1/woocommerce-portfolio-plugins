<?php
/**
 * Plugin Name:       WC Subscriptions Renewal Reminders
 * Description:       Schedules plain-text renewal reminder emails for WooCommerce Subscriptions before the next payment date.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Hamza R.
 * License:           GPL-2.0-or-later
 * Text Domain:       wc-subscriptions-renewal-reminders
 * Requires Plugins:  woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCSRR_VERSION', '1.0.0' );
define( 'WCSRR_PATH', plugin_dir_path( __FILE__ ) );

require_once WCSRR_PATH . 'includes/class-wcsrr-plugin.php';

add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		WCSRR_Plugin::init();
	}
);

register_activation_hook(
	__FILE__,
	static function () {
		if ( ! wp_next_scheduled( 'wcsrr_daily_scan' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'wcsrr_daily_scan' );
		}
	}
);

register_deactivation_hook(
	__FILE__,
	static function () {
		wp_clear_scheduled_hook( 'wcsrr_daily_scan' );
	}
);
