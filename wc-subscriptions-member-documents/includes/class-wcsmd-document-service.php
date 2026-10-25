<?php
/**
 * Orchestrates accept → PDF → archive → email.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WCSMD_Document_Service {

	/**
	 * Accept current template version, generate PDF, archive, optionally email.
	 *
	 * @param int  $user_id User.
	 * @param int  $template_id Template.
	 * @param int  $subscription_id Subscription ID (optional).
	 * @param bool $send_email Email PDF.
	 * @return array|WP_Error
	 */
	public static function accept_and_issue( $user_id, $template_id, $subscription_id = 0, $send_email = true ) {
		$user = get_user_by( 'id', absint( $user_id ) );
		if ( ! $user ) {
			return new WP_Error( 'wcsmd_user', __( 'Invalid user.', 'wc-subscriptions-member-documents' ) );
		}

		$version = WCSMD_Repository::get_current_version( $template_id );
		if ( ! $version ) {
			return new WP_Error( 'wcsmd_version', __( 'No current template version.', 'wc-subscriptions-member-documents' ) );
		}

		$acceptance_id = WCSMD_Repository::record_acceptance(
			$user->ID,
			$subscription_id,
			$template_id,
			(int) $version->id
		);
		if ( is_wp_error( $acceptance_id ) ) {
			return $acceptance_id;
		}

		/** @var WCSMD_Pdf_Generator $generator */
		$generator = apply_filters( 'wcsmd_pdf_generator', new WCSMD_Pdf_Generator() );

		$tokens = array(
			'name'            => $user->display_name,
			'email'           => $user->user_email,
			'subscription_id' => (string) absint( $subscription_id ),
			'date'            => wp_date( get_option( 'date_format' ) ),
			'version'         => (string) $version->version_number,
		);

		global $wpdb;
		$t        = WCSMD_Installer::tables();
		$template = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$t['templates']} WHERE id = %d", absint( $template_id ) )
		);
		$title = $template ? $template->title : __( 'Member Document', 'wc-subscriptions-member-documents' );

		$pdf = $generator->generate( $title, $version->body_html, $tokens );
		if ( is_wp_error( $pdf ) ) {
			return $pdf;
		}

		$stored = $generator->store( $user->ID, $template_id, (int) $version->id, $pdf );
		if ( is_wp_error( $stored ) ) {
			return $stored;
		}

		$emailed_at = null;
		if ( $send_email ) {
			$sent = WCSMD_Emailer::send_document( $user, $title, $stored['path'] );
			if ( ! is_wp_error( $sent ) && $sent ) {
				$emailed_at = current_time( 'mysql' );
			}
		}

		$archive_id = WCSMD_Repository::add_archive(
			array(
				'user_id'         => $user->ID,
				'subscription_id' => $subscription_id,
				'template_id'     => $template_id,
				'version_id'      => (int) $version->id,
				'file_path'       => $stored['path'],
				'file_hash'       => $stored['hash'],
				'emailed_at'      => $emailed_at,
			)
		);

		if ( is_wp_error( $archive_id ) ) {
			return $archive_id;
		}

		return array(
			'acceptance_id' => $acceptance_id,
			'archive_id'    => $archive_id,
			'file_path'     => $stored['path'],
			'file_hash'     => $stored['hash'],
			'emailed'       => (bool) $emailed_at,
		);
	}
}
