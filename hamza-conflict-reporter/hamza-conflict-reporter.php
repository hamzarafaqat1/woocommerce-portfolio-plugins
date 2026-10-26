<?php
/**
 * Plugin Name:       Hamza Conflict Reporter
 * Plugin URI:        https://github.com/hamzarafaqat1/woocommerce-portfolio-plugins
 * Description:       Shows recently updated extensions/themes and a health snapshot to help diagnose “site broke after an update” conflicts.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Hamza R.
 * Author URI:        https://github.com/hamzarafaqat1
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       hamza-conflict-reporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PCR_VERSION', '1.0.0' );
define( 'PCR_PATH', plugin_dir_path( __FILE__ ) );

require_once PCR_PATH . 'includes/class-pcr-plugin.php';

add_action(
	'plugins_loaded',
	static function () {
		PCR_Plugin::init();
	}
);
