<?php
/**
 * Main plugin orchestrator.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WCSMD_Plugin {

	/** @var self|null */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Wire hooks.
	 */
	public function boot() {
		WCSMD_Admin::init();
		WCSMD_Frontend::init();
		WCSMD_Emailer::init();
		WCSMD_Rest::init();

		add_shortcode( 'wcsmd_documents', array( 'WCSMD_Frontend', 'shortcode_documents' ) );
	}
}
