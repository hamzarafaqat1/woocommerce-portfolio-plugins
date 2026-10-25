<?php
/**
 * CSV migration from legacy plugin exports.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WCSMD_Migrator {

	/**
	 * Import legacy rows.
	 *
	 * @param string $csv_path Path.
	 * @param bool   $dry_run Dry run.
	 * @return array{imported:int,skipped:int,errors:string[]}
	 */
	public static function from_csv( $csv_path, $dry_run = true ) {
		$report = array(
			'imported' => 0,
			'skipped'  => 0,
			'errors'   => array(),
			'dry_run'  => (bool) $dry_run,
		);

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$fh = fopen( $csv_path, 'r' );
		if ( ! $fh ) {
			$report['errors'][] = 'Could not open CSV.';
			return $report;
		}

		$header = fgetcsv( $fh );
		if ( ! $header ) {
			fclose( $fh );
			$report['errors'][] = 'Empty CSV.';
			return $report;
		}

		$map = array();
		foreach ( $header as $i => $col ) {
			$map[ strtolower( trim( $col ) ) ] = $i;
		}

		foreach ( array( 'email', 'file_path', 'template_slug' ) as $need ) {
			if ( ! isset( $map[ $need ] ) ) {
				$report['errors'][] = "Missing column: {$need}";
			}
		}
		if ( $report['errors'] ) {
			fclose( $fh );
			return $report;
		}

		global $wpdb;
		$t = WCSMD_Installer::tables();

		while ( ( $row = fgetcsv( $fh ) ) !== false ) {
			$email = sanitize_email( $row[ $map['email'] ] );
			$path  = sanitize_text_field( $row[ $map['file_path'] ] );
			$slug  = sanitize_title( $row[ $map['template_slug'] ] );

			$user = get_user_by( 'email', $email );
			if ( ! $user ) {
				++$report['skipped'];
				$report['errors'][] = "User not found: {$email}";
				continue;
			}

			$template_id = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT id FROM {$t['templates']} WHERE slug = %s", $slug )
			);
			if ( ! $template_id ) {
				++$report['skipped'];
				$report['errors'][] = "Template missing: {$slug}";
				continue;
			}

			$version = WCSMD_Repository::get_current_version( $template_id );
			if ( ! $version ) {
				++$report['skipped'];
				continue;
			}

			if ( $dry_run ) {
				++$report['imported'];
				continue;
			}

			$hash = file_exists( $path ) ? hash_file( 'sha256', $path ) : '';
			WCSMD_Repository::add_archive(
				array(
					'user_id'         => $user->ID,
					'subscription_id' => 0,
					'template_id'     => $template_id,
					'version_id'      => (int) $version->id,
					'file_path'       => $path,
					'file_hash'       => $hash ?: '',
					'emailed_at'      => null,
				)
			);
			++$report['imported'];
		}

		fclose( $fh );
		return $report;
	}
}
