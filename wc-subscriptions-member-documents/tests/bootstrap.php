<?php
/**
 * PHPUnit bootstrap — uses WP test lib when available, otherwise light stubs.
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( $_tests_dir && file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	require_once $_tests_dir . '/includes/functions.php';

	tests_add_filter(
		'muplugins_loaded',
		function () {
			require dirname( __DIR__ ) . '/wc-subscriptions-member-documents.php';
		}
	);

	require $_tests_dir . '/includes/bootstrap.php';
	return;
}

// Lightweight fallback so `phpunit` still loads classes without full WP suite.
if ( ! class_exists( 'WP_UnitTestCase' ) ) {
	class WP_UnitTestCase extends PHPUnit\Framework\TestCase {}
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
if ( ! defined( 'WCSMD_PATH' ) ) {
	define( 'WCSMD_VERSION', '1.0.0' );
	define( 'WCSMD_FILE', dirname( __DIR__ ) . '/wc-subscriptions-member-documents.php' );
	define( 'WCSMD_PATH', dirname( __DIR__ ) . '/' );
	define( 'WCSMD_URL', 'http://example.test/wp-content/plugins/wc-subscriptions-member-documents/' );
}

require_once dirname( __DIR__ ) . '/includes/class-wcsmd-autoloader.php';
WCSMD_Autoloader::register();

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $string ) {
		return strip_tags( $string );
	}
}
