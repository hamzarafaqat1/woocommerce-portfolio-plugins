<?php
/**
 * Email delivery for member PDFs.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WCSMD_Emailer {

	/**
	 * Init hooks.
	 */
	public static function init() {
		// Reserved for WC email integrations.
	}

	/**
	 * Email a PDF attachment to the member.
	 *
	 * @param WP_User $user User.
	 * @param string  $title Document title.
	 * @param string  $file_path Absolute path.
	 * @return bool|WP_Error
	 */
	public static function send_document( WP_User $user, $title, $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'wcsmd_email', __( 'PDF file missing.', 'wc-subscriptions-member-documents' ) );
		}

		$subject = sprintf(
			/* translators: %s document title */
			__( 'Your document: %s', 'wc-subscriptions-member-documents' ),
			$title
		);

		$body = sprintf(
			/* translators: 1: name 2: title */
			__( "Hi %1\$s,\n\nAttached is your copy of \"%2\$s\".\n\nThank you.", 'wc-subscriptions-member-documents' ),
			$user->display_name,
			$title
		);

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		$sent    = wp_mail( $user->user_email, $subject, $body, $headers, array( $file_path ) );

		return (bool) $sent;
	}
}
