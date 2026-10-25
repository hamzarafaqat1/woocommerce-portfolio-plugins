<?php
/**
 * Data access for templates, versions, acceptances, archives.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WCSMD_Repository {

	/**
	 * Create a template shell.
	 *
	 * @param string $slug Slug.
	 * @param string $title Title.
	 * @param string $doc_type Type key.
	 * @return int|WP_Error
	 */
	public static function create_template( $slug, $title, $doc_type = 'agreement' ) {
		global $wpdb;
		$t    = WCSMD_Installer::tables();
		$now  = current_time( 'mysql' );
		$slug = sanitize_title( $slug );

		$ok = $wpdb->insert(
			$t['templates'],
			array(
				'slug'       => $slug,
				'title'      => sanitize_text_field( $title ),
				'doc_type'   => sanitize_key( $doc_type ),
				'status'     => 'active',
				'created_at' => $now,
				'updated_at' => $now,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( ! $ok ) {
			return new WP_Error( 'wcsmd_db', __( 'Could not create template.', 'wc-subscriptions-member-documents' ) );
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Add a new version and mark it current.
	 *
	 * @param int    $template_id Template ID.
	 * @param string $body_html HTML body.
	 * @param string $changelog Optional notes.
	 * @param int    $user_id Author.
	 * @return int|WP_Error
	 */
	public static function add_version( $template_id, $body_html, $changelog = '', $user_id = 0 ) {
		global $wpdb;
		$t = WCSMD_Installer::tables();

		$template_id = absint( $template_id );
		$next        = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(MAX(version_number), 0) + 1 FROM {$t['versions']} WHERE template_id = %d",
				$template_id
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$t['versions']} SET is_current = 0 WHERE template_id = %d",
				$template_id
			)
		);

		$ok = $wpdb->insert(
			$t['versions'],
			array(
				'template_id'    => $template_id,
				'version_number' => $next,
				'body_html'      => wp_kses_post( $body_html ),
				'changelog'      => sanitize_textarea_field( $changelog ),
				'is_current'     => 1,
				'created_at'     => current_time( 'mysql' ),
				'created_by'     => absint( $user_id ),
			),
			array( '%d', '%d', '%s', '%s', '%d', '%s', '%d' )
		);

		if ( ! $ok ) {
			return new WP_Error( 'wcsmd_db', __( 'Could not create version.', 'wc-subscriptions-member-documents' ) );
		}

		$wpdb->update(
			$t['templates'],
			array( 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $template_id ),
			array( '%s' ),
			array( '%d' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Current version row for a template.
	 *
	 * @param int $template_id Template ID.
	 * @return object|null
	 */
	public static function get_current_version( $template_id ) {
		global $wpdb;
		$t = WCSMD_Installer::tables();
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$t['versions']} WHERE template_id = %d AND is_current = 1 LIMIT 1",
				absint( $template_id )
			)
		);
	}

	/**
	 * Record acceptance.
	 *
	 * @param int $user_id User.
	 * @param int $subscription_id Subscription.
	 * @param int $template_id Template.
	 * @param int $version_id Version.
	 * @return int|WP_Error
	 */
	public static function record_acceptance( $user_id, $subscription_id, $template_id, $version_id ) {
		global $wpdb;
		$t = WCSMD_Installer::tables();

		$ok = $wpdb->insert(
			$t['acceptances'],
			array(
				'user_id'         => absint( $user_id ),
				'subscription_id' => absint( $subscription_id ),
				'template_id'     => absint( $template_id ),
				'version_id'      => absint( $version_id ),
				'accepted_at'     => current_time( 'mysql' ),
				'ip_address'      => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
				'user_agent'      => isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 ) : '',
			),
			array( '%d', '%d', '%d', '%d', '%s', '%s', '%s' )
		);

		if ( ! $ok ) {
			return new WP_Error( 'wcsmd_db', __( 'Could not record acceptance.', 'wc-subscriptions-member-documents' ) );
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Has user accepted this version?
	 *
	 * @param int $user_id User.
	 * @param int $version_id Version.
	 * @return bool
	 */
	public static function has_accepted_version( $user_id, $version_id ) {
		global $wpdb;
		$t = WCSMD_Installer::tables();
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$t['acceptances']} WHERE user_id = %d AND version_id = %d",
				absint( $user_id ),
				absint( $version_id )
			)
		);
		return $count > 0;
	}

	/**
	 * Store archive row.
	 *
	 * @param array $data Row data.
	 * @return int|WP_Error
	 */
	public static function add_archive( array $data ) {
		global $wpdb;
		$t = WCSMD_Installer::tables();

		$ok = $wpdb->insert(
			$t['archives'],
			array(
				'user_id'         => absint( $data['user_id'] ),
				'subscription_id' => absint( $data['subscription_id'] ?? 0 ),
				'template_id'     => absint( $data['template_id'] ),
				'version_id'      => absint( $data['version_id'] ),
				'file_path'       => sanitize_text_field( $data['file_path'] ),
				'file_hash'       => sanitize_text_field( $data['file_hash'] ?? '' ),
				'emailed_at'      => $data['emailed_at'] ?? null,
				'created_at'      => current_time( 'mysql' ),
			)
		);

		if ( ! $ok ) {
			return new WP_Error( 'wcsmd_db', __( 'Could not save archive.', 'wc-subscriptions-member-documents' ) );
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Archives for a member.
	 *
	 * @param int $user_id User.
	 * @return array
	 */
	public static function get_user_archives( $user_id ) {
		global $wpdb;
		$t = WCSMD_Installer::tables();
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.*, tmpl.title AS template_title, v.version_number
				FROM {$t['archives']} a
				LEFT JOIN {$t['templates']} tmpl ON tmpl.id = a.template_id
				LEFT JOIN {$t['versions']} v ON v.id = a.version_id
				WHERE a.user_id = %d
				ORDER BY a.created_at DESC",
				absint( $user_id )
			)
		);
	}

	/**
	 * Active templates.
	 *
	 * @return array
	 */
	public static function get_active_templates() {
		global $wpdb;
		$t = WCSMD_Installer::tables();
		return $wpdb->get_results(
			"SELECT * FROM {$t['templates']} WHERE status = 'active' ORDER BY title ASC"
		);
	}
}
