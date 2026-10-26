<?php
/**
 * Plugin Name:       Woo CTA Normalizer
 * Plugin URI:        https://github.com/hamzarafaqat1/woocommerce-portfolio-plugins
 * Description:       Makes WooCommerce shop/archive buttons consistent — normalize “Add to cart”, “Select options”, and “Read more” labels and styles.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Hamza R.
 * Author URI:        https://github.com/hamzarafaqat1
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       woo-cta-normalizer
 * Requires Plugins:  woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCTAN_VERSION', '1.0.0' );
define( 'WCTAN_PATH', plugin_dir_path( __FILE__ ) );

require_once WCTAN_PATH . 'includes/class-wctan-plugin.php';

add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'Woo CTA Normalizer requires WooCommerce.', 'woo-cta-normalizer' ) . '</p></div>';
				}
			);
			return;
		}
		WCTAN_Plugin::init();
	}
);

register_activation_hook(
	__FILE__,
	static function () {
		if ( false === get_option( 'wctan_settings', false ) ) {
			update_option(
				'wctan_settings',
				array(
					'unify_labels'   => 1,
					'simple_label'   => 'Add to cart',
					'variable_label' => 'Choose options',
					'external_label' => 'Buy now',
					'grouped_label'  => 'View products',
					'style_buttons'  => 1,
				)
			);
		}
	}
);
