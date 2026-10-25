<?php
/**
 * Minimal PDF generator (portfolio demo).
 *
 * Produces a simple valid PDF from HTML-ish text without Composer deps.
 * Swap this class for Dompdf/mPDF in production via the `wcsmd_pdf_generator` filter.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WCSMD_Pdf_Generator {

	/**
	 * Build PDF binary for a member document.
	 *
	 * @param string $title Document title.
	 * @param string $body_html Template HTML.
	 * @param array  $tokens Merge tokens (name, email, subscription_id, date).
	 * @return string|WP_Error Raw PDF bytes.
	 */
	public function generate( $title, $body_html, array $tokens = array() ) {
		$html = $this->merge_tokens( $body_html, $tokens );
		$text = wp_strip_all_tags( $html );
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );

		$lines = $this->wrap_lines( $title . "\n\n" . $text, 90 );
		return $this->build_simple_pdf( $lines );
	}

	/**
	 * Replace {{token}} placeholders.
	 *
	 * @param string $html HTML.
	 * @param array  $tokens Tokens.
	 * @return string
	 */
	public function merge_tokens( $html, array $tokens ) {
		foreach ( $tokens as $key => $value ) {
			$html = str_replace( '{{' . $key . '}}', (string) $value, $html );
		}
		return $html;
	}

	/**
	 * Persist PDF under uploads/wcsmd-archives/.
	 *
	 * @param int    $user_id User.
	 * @param int    $template_id Template.
	 * @param int    $version_id Version.
	 * @param string $pdf_bytes PDF binary.
	 * @return array{path:string,hash:string}|WP_Error
	 */
	public function store( $user_id, $template_id, $version_id, $pdf_bytes ) {
		$upload = wp_upload_dir();
		if ( ! empty( $upload['error'] ) ) {
			return new WP_Error( 'wcsmd_upload', $upload['error'] );
		}

		$dir = trailingslashit( $upload['basedir'] ) . 'wcsmd-archives/' . absint( $user_id );
		if ( ! wp_mkdir_p( $dir ) ) {
			return new WP_Error( 'wcsmd_upload', __( 'Could not create archive folder.', 'wc-subscriptions-member-documents' ) );
		}

		$filename = sprintf(
			'doc-%d-v%d-%s.pdf',
			absint( $template_id ),
			absint( $version_id ),
			gmdate( 'Ymd-His' )
		);
		$path = trailingslashit( $dir ) . $filename;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$written = file_put_contents( $path, $pdf_bytes );
		if ( false === $written ) {
			return new WP_Error( 'wcsmd_upload', __( 'Could not write PDF.', 'wc-subscriptions-member-documents' ) );
		}

		return array(
			'path' => $path,
			'hash' => hash( 'sha256', $pdf_bytes ),
		);
	}

	/**
	 * Wrap plain text into lines.
	 *
	 * @param string $text Text.
	 * @param int    $width Width.
	 * @return string[]
	 */
	private function wrap_lines( $text, $width ) {
		$out = array();
		foreach ( preg_split( "/\r\n|\n|\r/", $text ) as $paragraph ) {
			$wrapped = wordwrap( $paragraph, $width, "\n", true );
			foreach ( explode( "\n", $wrapped ) as $line ) {
				$out[] = $line;
			}
			$out[] = '';
		}
		return $out;
	}

	/**
	 * Very small multi-line text PDF (Type1 Helvetica).
	 *
	 * @param string[] $lines Lines.
	 * @return string
	 */
	private function build_simple_pdf( array $lines ) {
		$content = "BT /F1 11 Tf 50 780 Td 14 TL\n";
		$first   = true;
		$count   = 0;
		foreach ( $lines as $line ) {
			if ( $count > 48 ) {
				break; // One page for demo generator.
			}
			$safe = $this->escape_pdf_text( $line );
			if ( $first ) {
				$content .= "({$safe}) Tj\n";
				$first = false;
			} else {
				$content .= "T* ({$safe}) Tj\n";
			}
			++$count;
		}
		$content .= "ET";

		$objects   = array();
		$objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
		$objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
		$objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>';
		$objects[] = '<< /Length ' . strlen( $content ) . " >>\nstream\n" . $content . "\nendstream";
		$objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

		$pdf    = "%PDF-1.4\n";
		$offsets = array( 0 );
		foreach ( $objects as $i => $obj ) {
			$offsets[ $i + 1 ] = strlen( $pdf );
			$pdf .= ( $i + 1 ) . " 0 obj\n" . $obj . "\nendobj\n";
		}
		$xref_pos = strlen( $pdf );
		$pdf     .= "xref\n0 " . ( count( $objects ) + 1 ) . "\n";
		$pdf     .= "0000000000 65535 f \n";
		for ( $i = 1; $i <= count( $objects ); $i++ ) {
			$pdf .= sprintf( "%010d 00000 n \n", $offsets[ $i ] );
		}
		$pdf .= 'trailer << /Size ' . ( count( $objects ) + 1 ) . ' /Root 1 0 R >>' . "\n";
		$pdf .= "startxref\n{$xref_pos}\n%%EOF";

		return $pdf;
	}

	/**
	 * Escape PDF string literals.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private function escape_pdf_text( $text ) {
		$text = str_replace( '\\', '\\\\', $text );
		$text = str_replace( array( '(', ')' ), array( '\\(', '\\)' ), $text );
		// Keep ASCII for Type1 demo font.
		return preg_replace( '/[^\x20-\x7E]/', '?', $text );
	}
}
