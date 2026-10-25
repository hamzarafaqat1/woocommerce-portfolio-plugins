<?php
/**
 * Unit tests for PDF token merge + generator (PHPUnit / WP test suite compatible).
 *
 * Run with: vendor/bin/phpunit -c phpunit.xml.dist
 *
 * @package WCSMD
 */

class WCSMD_Pdf_Generator_Test extends WP_UnitTestCase {

	public function test_merge_tokens_replaces_placeholders() {
		$gen  = new WCSMD_Pdf_Generator();
		$html = 'Hello {{name}}, sub {{subscription_id}}';
		$out  = $gen->merge_tokens(
			$html,
			array(
				'name'            => 'Ada',
				'subscription_id' => '42',
			)
		);

		$this->assertSame( 'Hello Ada, sub 42', $out );
	}

	public function test_generate_returns_pdf_header() {
		$gen = new WCSMD_Pdf_Generator();
		$pdf = $gen->generate(
			'Agreement',
			'<p>Member {{name}} accepts on {{date}}.</p>',
			array(
				'name' => 'Test User',
				'date' => '2026-01-01',
			)
		);

		$this->assertIsString( $pdf );
		$this->assertStringStartsWith( '%PDF', $pdf );
		$this->assertStringContainsString( '%%EOF', $pdf );
	}
}
