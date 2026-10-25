<?php
/**
 * Simple PSR-4-ish autoloader for WCSMD_* classes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WCSMD_Autoloader {

	/**
	 * Register spl autoload.
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'load' ) );
	}

	/**
	 * Map WCSMD_Foo_Bar → class-wcsmd-foo-bar.php under includes/.
	 *
	 * @param string $class Class name.
	 */
	public static function load( $class ) {
		if ( 0 !== strpos( $class, 'WCSMD_' ) ) {
			return;
		}

		$slug = 'class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';
		$paths = array(
			WCSMD_PATH . 'includes/' . $slug,
			WCSMD_PATH . 'includes/pdf/' . $slug,
		);

		foreach ( $paths as $path ) {
			if ( file_exists( $path ) ) {
				require_once $path;
				return;
			}
		}
	}
}
