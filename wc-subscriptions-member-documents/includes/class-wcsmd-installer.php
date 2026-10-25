<?php
/**
 * DB schema for templates, versions, acceptances, and archives.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WCSMD_Installer {

	const DB_VERSION = '1.0.0';

	/**
	 * Create/upgrade custom tables.
	 */
	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$t       = self::tables();

		$sql_templates = "CREATE TABLE {$t['templates']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			slug varchar(191) NOT NULL,
			title varchar(255) NOT NULL,
			doc_type varchar(64) NOT NULL DEFAULT 'agreement',
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug)
		) $charset;";

		$sql_versions = "CREATE TABLE {$t['versions']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			template_id bigint(20) unsigned NOT NULL,
			version_number int(11) NOT NULL,
			body_html longtext NOT NULL,
			changelog text NULL,
			is_current tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY template_id (template_id),
			UNIQUE KEY template_version (template_id, version_number)
		) $charset;";

		$sql_acceptances = "CREATE TABLE {$t['acceptances']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			subscription_id bigint(20) unsigned NOT NULL DEFAULT 0,
			template_id bigint(20) unsigned NOT NULL,
			version_id bigint(20) unsigned NOT NULL,
			accepted_at datetime NOT NULL,
			ip_address varchar(45) NULL,
			user_agent varchar(255) NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY template_version (template_id, version_id)
		) $charset;";

		$sql_archives = "CREATE TABLE {$t['archives']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			subscription_id bigint(20) unsigned NOT NULL DEFAULT 0,
			template_id bigint(20) unsigned NOT NULL,
			version_id bigint(20) unsigned NOT NULL,
			file_path text NOT NULL,
			file_hash varchar(64) NOT NULL DEFAULT '',
			emailed_at datetime NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY template_id (template_id)
		) $charset;";

		dbDelta( $sql_templates );
		dbDelta( $sql_versions );
		dbDelta( $sql_acceptances );
		dbDelta( $sql_archives );

		update_option( 'wcsmd_db_version', self::DB_VERSION );

		$upload = wp_upload_dir();
		$dir    = trailingslashit( $upload['basedir'] ) . 'wcsmd-archives';
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
			file_put_contents( trailingslashit( $dir ) . 'index.php', "<?php\n// Silence is golden.\n" );
		}
	}

	/**
	 * Table name map.
	 *
	 * @return array<string,string>
	 */
	public static function tables() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'wcsmd_';
		return array(
			'templates'   => $prefix . 'templates',
			'versions'    => $prefix . 'versions',
			'acceptances' => $prefix . 'acceptances',
			'archives'    => $prefix . 'archives',
		);
	}
}
