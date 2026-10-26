<?php
/**
 * Plugin Name:       Upscale Mobile Spacing Fix for Elementor
 * Plugin URI:        https://github.com/hamzarafaqat1/woocommerce-portfolio-plugins
 * Description:       Fix common Elementor mobile spacing issues: remove huge gaps, normalize section padding, and tame widget margins on small screens.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Upscale Solutions
 * Author URI:        https://upscalesolutions.digital
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       upscale-mobile-spacing-fix-for-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EMSF_VERSION', '1.0.0' );
define( 'EMSF_FILE', __FILE__ );
define( 'EMSF_PATH', plugin_dir_path( __FILE__ ) );
define( 'EMSF_URL', plugin_dir_url( __FILE__ ) );

require_once EMSF_PATH . 'includes/class-emsf-plugin.php';

add_action(
	'plugins_loaded',
	static function () {
		EMSF_Plugin::init();
	}
);

register_activation_hook(
	__FILE__,
	static function () {
		if ( false === get_option( 'emsf_settings', false ) ) {
			update_option(
				'emsf_settings',
				array(
					'enabled'            => 1,
					'max_section_pad'    => 40,
					'max_widget_margin'  => 24,
					'collapse_empty_gap' => 1,
					'breakpoint'         => 767,
				)
			);
		}
	}
);
