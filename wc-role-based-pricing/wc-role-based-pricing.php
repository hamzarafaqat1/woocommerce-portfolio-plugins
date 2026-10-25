<?php
/**
 * Plugin Name:       WC Role-Based Pricing
 * Description:       Set per-role product prices (e.g. wholesale) via product meta and apply them on the front end and cart.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Hamza R.
 * License:           GPL-2.0-or-later
 * Text Domain:       wc-role-based-pricing
 * Requires Plugins:  woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCRBP_PATH', plugin_dir_path( __FILE__ ) );
require_once WCRBP_PATH . 'includes/class-wcrbp-plugin.php';

add_action(
	'plugins_loaded',
	static function () {
		if ( class_exists( 'WooCommerce' ) ) {
			WCRBP_Plugin::init();
		}
	}
);
