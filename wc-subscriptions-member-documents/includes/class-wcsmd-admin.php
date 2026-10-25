<?php
/**
 * Admin: templates, versions, migration screen.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WCSMD_Admin {

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_wcsmd_save_template', array( __CLASS__, 'save_template' ) );
		add_action( 'admin_post_wcsmd_run_migration', array( __CLASS__, 'run_migration' ) );
	}

	/**
	 * Menu pages.
	 */
	public static function menu() {
		add_menu_page(
			__( 'Member Documents', 'wc-subscriptions-member-documents' ),
			__( 'Member Docs', 'wc-subscriptions-member-documents' ),
			'manage_woocommerce',
			'wcsmd-documents',
			array( __CLASS__, 'render_templates' ),
			'dashicons-media-document',
			56
		);

		add_submenu_page(
			'wcsmd-documents',
			__( 'Migration', 'wc-subscriptions-member-documents' ),
			__( 'Migration', 'wc-subscriptions-member-documents' ),
			'manage_woocommerce',
			'wcsmd-migration',
			array( __CLASS__, 'render_migration' )
		);
	}

	/**
	 * Templates list + create form.
	 */
	public static function render_templates() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$templates = WCSMD_Repository::get_active_templates();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Member document templates', 'wc-subscriptions-member-documents' ); ?></h1>
			<p><?php esc_html_e( 'Versioned templates for agreements and notices. Tokens: {{name}}, {{email}}, {{subscription_id}}, {{date}}, {{version}}.', 'wc-subscriptions-member-documents' ); ?></p>

			<h2><?php esc_html_e( 'Create / version template', 'wc-subscriptions-member-documents' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="wcsmd_save_template" />
				<?php wp_nonce_field( 'wcsmd_save_template' ); ?>
				<table class="form-table">
					<tr>
						<th><label for="wcsmd_slug"><?php esc_html_e( 'Slug', 'wc-subscriptions-member-documents' ); ?></label></th>
						<td><input name="slug" id="wcsmd_slug" class="regular-text" required placeholder="membership-agreement" /></td>
					</tr>
					<tr>
						<th><label for="wcsmd_title"><?php esc_html_e( 'Title', 'wc-subscriptions-member-documents' ); ?></label></th>
						<td><input name="title" id="wcsmd_title" class="regular-text" required /></td>
					</tr>
					<tr>
						<th><label for="wcsmd_body"><?php esc_html_e( 'Body (HTML)', 'wc-subscriptions-member-documents' ); ?></label></th>
						<td><textarea name="body_html" id="wcsmd_body" rows="10" class="large-text" required>Dear {{name}},&#10;&#10;This agreement applies to subscription #{{subscription_id}} as of {{date}}.</textarea></td>
					</tr>
					<tr>
						<th><label for="wcsmd_changelog"><?php esc_html_e( 'Changelog', 'wc-subscriptions-member-documents' ); ?></label></th>
						<td><input name="changelog" id="wcsmd_changelog" class="regular-text" placeholder="Initial version" /></td>
					</tr>
				</table>
				<?php submit_button( __( 'Save version', 'wc-subscriptions-member-documents' ) ); ?>
			</form>

			<h2><?php esc_html_e( 'Active templates', 'wc-subscriptions-member-documents' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th>ID</th><th><?php esc_html_e( 'Title', 'wc-subscriptions-member-documents' ); ?></th><th><?php esc_html_e( 'Slug', 'wc-subscriptions-member-documents' ); ?></th><th><?php esc_html_e( 'Current version', 'wc-subscriptions-member-documents' ); ?></th></tr></thead>
				<tbody>
				<?php if ( empty( $templates ) ) : ?>
					<tr><td colspan="4"><?php esc_html_e( 'None yet.', 'wc-subscriptions-member-documents' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $templates as $row ) : ?>
						<?php $v = WCSMD_Repository::get_current_version( (int) $row->id ); ?>
						<tr>
							<td><?php echo esc_html( (string) $row->id ); ?></td>
							<td><?php echo esc_html( $row->title ); ?></td>
							<td><?php echo esc_html( $row->slug ); ?></td>
							<td><?php echo $v ? 'v' . esc_html( (string) $v->version_number ) : '—'; ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Save template + version.
	 */
	public static function save_template() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Forbidden', 'wc-subscriptions-member-documents' ) );
		}
		check_admin_referer( 'wcsmd_save_template' );

		$slug  = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$body  = isset( $_POST['body_html'] ) ? wp_kses_post( wp_unslash( $_POST['body_html'] ) ) : '';
		$log   = isset( $_POST['changelog'] ) ? sanitize_text_field( wp_unslash( $_POST['changelog'] ) ) : '';

		global $wpdb;
		$t  = WCSMD_Installer::tables();
		$id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t['templates']} WHERE slug = %s", $slug ) );

		if ( ! $id ) {
			$id = WCSMD_Repository::create_template( $slug, $title );
			if ( is_wp_error( $id ) ) {
				wp_die( esc_html( $id->get_error_message() ) );
			}
		}

		$version = WCSMD_Repository::add_version( $id, $body, $log, get_current_user_id() );
		if ( is_wp_error( $version ) ) {
			wp_die( esc_html( $version->get_error_message() ) );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=wcsmd-documents&saved=1' ) );
		exit;
	}

	/**
	 * Migration UI.
	 */
	public static function render_migration() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Legacy document migration', 'wc-subscriptions-member-documents' ); ?></h1>
			<p><?php esc_html_e( 'Imports a CSV sample of old file paths + user emails into the archive table (dry-run supported).', 'wc-subscriptions-member-documents' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<input type="hidden" name="action" value="wcsmd_run_migration" />
				<?php wp_nonce_field( 'wcsmd_run_migration' ); ?>
				<p><label><input type="checkbox" name="dry_run" value="1" checked /> <?php esc_html_e( 'Dry run (report only)', 'wc-subscriptions-member-documents' ); ?></label></p>
				<p><input type="file" name="csv" accept=".csv,text/csv" required /></p>
				<?php submit_button( __( 'Run migration', 'wc-subscriptions-member-documents' ) ); ?>
			</form>
			<p class="description"><?php esc_html_e( 'CSV columns: email, file_path, template_slug, accepted_at (optional)', 'wc-subscriptions-member-documents' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Handle migration upload.
	 */
	public static function run_migration() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Forbidden', 'wc-subscriptions-member-documents' ) );
		}
		check_admin_referer( 'wcsmd_run_migration' );

		$dry = ! empty( $_POST['dry_run'] );
		$file = isset( $_FILES['csv']['tmp_name'] ) ? $_FILES['csv']['tmp_name'] : '';
		if ( ! $file || ! is_uploaded_file( $file ) ) {
			wp_die( esc_html__( 'Upload a CSV file.', 'wc-subscriptions-member-documents' ) );
		}

		$report = WCSMD_Migrator::from_csv( $file, $dry );
		set_transient( 'wcsmd_migration_report_' . get_current_user_id(), $report, HOUR_IN_SECONDS );
		wp_safe_redirect( admin_url( 'admin.php?page=wcsmd-migration&done=1' ) );
		exit;
	}
}
