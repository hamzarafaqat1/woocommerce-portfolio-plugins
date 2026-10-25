<?php
/**
 * Plugin Name:       WC Subscription Content Gate
 * Description:       Restrict pages/posts to members with an active WooCommerce Subscription. Shortcode + meta box.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Hamza R.
 * License:           GPL-2.0-or-later
 * Text Domain:       wc-subscription-content-gate
 * Requires Plugins:  woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCSCG_PATH', plugin_dir_path( __FILE__ ) );
require_once WCSCG_PATH . 'includes/class-wcscg-plugin.php';

add_action(
	'plugins_loaded',
	static function () {
		if ( class_exists( 'WooCommerce' ) ) {
			WCSCG_Plugin::init();
		}
	}
);
