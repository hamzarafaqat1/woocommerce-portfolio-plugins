<?php
/**
 * Acceptance helper tests (logic-level; DB tests need WP test install).
 *
 * @package WCSMD
 */

class WCSMD_Acceptance_Logic_Test extends WP_UnitTestCase {

	public function test_plugin_constants_defined() {
		$this->assertTrue( defined( 'WCSMD_VERSION' ) );
		$this->assertSame( '1.0.0', WCSMD_VERSION );
	}

	public function test_table_names_use_prefix() {
		global $wpdb;
		$tables = WCSMD_Installer::tables();
		$this->assertArrayHasKey( 'templates', $tables );
		$this->assertStringContainsString( $wpdb->prefix . 'wcsmd_', $tables['templates'] );
	}
}
