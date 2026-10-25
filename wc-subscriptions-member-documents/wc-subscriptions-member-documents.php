<?php
/**
 * Plugin Name:       WC Subscriptions Member Documents
 * Description:       Versioned document templates, member acceptance, PDF generation, per-member archives, email delivery, and migration helpers for WooCommerce Subscriptions sites.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Hamza R.
 * License:           GPL-2.0-or-later
 * Text Domain:       wc-subscriptions-member-documents
 * Requires Plugins:  woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCSMD_VERSION', '1.0.0' );
define( 'WCSMD_FILE', __FILE__ );
define( 'WCSMD_PATH', plugin_dir_path( __FILE__ ) );
define( 'WCSMD_URL', plugin_dir_url( __FILE__ ) );

require_once WCSMD_PATH . 'includes/class-wcsmd-autoloader.php';
WCSMD_Autoloader::register();

/**
 * Bootstrap after plugins load so WooCommerce is available.
 */
function wcsmd_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'wcsmd_missing_woocommerce_notice' );
		return;
	}

	WCSMD_Plugin::instance()->boot();
}
add_action( 'plugins_loaded', 'wcsmd_init' );

/**
 * Activation: tables + rewrite flush.
 */
function wcsmd_activate() {
	require_once WCSMD_PATH . 'includes/class-wcsmd-autoloader.php';
	WCSMD_Autoloader::register();
	WCSMD_Installer::install();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'wcsmd_activate' );

/**
 * Deactivation: flush rewrites only.
 */
function wcsmd_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'wcsmd_deactivate' );

/**
 * Admin notice when WooCommerce is missing.
 */
function wcsmd_missing_woocommerce_notice() {
	echo '<div class="notice notice-error"><p>';
	echo esc_html__( 'WC Subscriptions Member Documents requires WooCommerce.', 'wc-subscriptions-member-documents' );
	echo '</p></div>';
}
