<?php
/**
 * Conflict reporter admin tool.
 *
 * @package Plugin_Conflict_Reporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PCR_Plugin {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'log_upgrade' ), 10, 2 );
		add_action( 'activated_plugin', array( __CLASS__, 'log_activation' ), 10, 2 );
		add_action( 'deactivated_plugin', array( __CLASS__, 'log_deactivation' ), 10, 2 );
	}

	/**
	 * Tools menu.
	 */
	public static function menu() {
		add_management_page(
			__( 'Conflict Reporter', 'plugin-conflict-reporter' ),
			__( 'Conflict Reporter', 'plugin-conflict-reporter' ),
			'manage_options',
			'pcr-reporter',
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Event log option.
	 *
	 * @return array
	 */
	public static function get_log() {
		$log = get_option( 'pcr_event_log', array() );
		return is_array( $log ) ? $log : array();
	}

	/**
	 * Append event.
	 *
	 * @param string $type Type.
	 * @param string $message Message.
	 */
	public static function push_event( $type, $message ) {
		$log   = self::get_log();
		$log[] = array(
			'time'    => time(),
			'type'    => sanitize_key( $type ),
			'message' => sanitize_text_field( $message ),
		);
		if ( count( $log ) > 100 ) {
			$log = array_slice( $log, -100 );
		}
		update_option( 'pcr_event_log', $log, false );
	}

	/**
	 * Upgrade logger.
	 *
	 * @param WP_Upgrader $upgrader Upgrader.
	 * @param array       $options Options.
	 */
	public static function log_upgrade( $upgrader, $options ) {
		if ( empty( $options['type'] ) ) {
			return;
		}
		$type = $options['type'];
		if ( 'plugin' === $type && ! empty( $options['plugins'] ) && is_array( $options['plugins'] ) ) {
			foreach ( $options['plugins'] as $plugin ) {
				self::push_event( 'plugin_update', sprintf( 'Updated plugin: %s', $plugin ) );
			}
		}
		if ( 'theme' === $type && ! empty( $options['themes'] ) && is_array( $options['themes'] ) ) {
			foreach ( $options['themes'] as $theme ) {
				self::push_event( 'theme_update', sprintf( 'Updated theme: %s', $theme ) );
			}
		}
		if ( 'core' === $type ) {
			self::push_event( 'core_update', 'WordPress core updated' );
		}
	}

	/**
	 * Activation log.
	 *
	 * @param string $plugin Plugin.
	 * @param bool   $network Network.
	 */
	public static function log_activation( $plugin, $network ) {
		unset( $network );
		self::push_event( 'activate', sprintf( 'Activated: %s', $plugin ) );
	}

	/**
	 * Deactivation log.
	 *
	 * @param string $plugin Plugin.
	 * @param bool   $network Network.
	 */
	public static function log_deactivation( $plugin, $network ) {
		unset( $network );
		self::push_event( 'deactivate', sprintf( 'Deactivated: %s', $plugin ) );
	}

	/**
	 * Health snapshot.
	 *
	 * @return array
	 */
	public static function snapshot() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$active  = (array) get_option( 'active_plugins', array() );
		$all     = get_plugins();
		$theme   = wp_get_theme();
		$recent  = array();

		foreach ( $active as $file ) {
			$path = WP_PLUGIN_DIR . '/' . $file;
			$mtime = file_exists( $path ) ? filemtime( $path ) : 0;
			$recent[] = array(
				'name'  => isset( $all[ $file ]['Name'] ) ? $all[ $file ]['Name'] : $file,
				'file'  => $file,
				'mtime' => $mtime,
			);
		}

		usort(
			$recent,
			static function ( $a, $b ) {
				return $b['mtime'] <=> $a['mtime'];
			}
		);

		return array(
			'php'           => PHP_VERSION,
			'wp'            => get_bloginfo( 'version' ),
			'theme'         => $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ),
			'active_count'  => count( $active ),
			'memory_limit'  => ini_get( 'memory_limit' ),
			'max_exec'      => ini_get( 'max_execution_time' ),
			'recent_active' => array_slice( $recent, 0, 15 ),
			'debug'         => defined( 'WP_DEBUG' ) && WP_DEBUG,
		);
	}

	/**
	 * Admin page.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$snap = self::snapshot();
		$log  = array_reverse( self::get_log() );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Plugin Conflict Reporter', 'plugin-conflict-reporter' ); ?></h1>
			<p><?php esc_html_e( 'Use this after “the site broke” reports. Check what changed recently, then deactivate the newest plugins one by one on staging.', 'plugin-conflict-reporter' ); ?></p>

			<h2><?php esc_html_e( 'Health snapshot', 'plugin-conflict-reporter' ); ?></h2>
			<table class="widefat striped" style="max-width:800px">
				<tbody>
					<tr><th><?php esc_html_e( 'WordPress', 'plugin-conflict-reporter' ); ?></th><td><?php echo esc_html( $snap['wp'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'PHP', 'plugin-conflict-reporter' ); ?></th><td><?php echo esc_html( $snap['php'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Theme', 'plugin-conflict-reporter' ); ?></th><td><?php echo esc_html( $snap['theme'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Active plugins', 'plugin-conflict-reporter' ); ?></th><td><?php echo esc_html( (string) $snap['active_count'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Memory limit', 'plugin-conflict-reporter' ); ?></th><td><?php echo esc_html( (string) $snap['memory_limit'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'WP_DEBUG', 'plugin-conflict-reporter' ); ?></th><td><?php echo $snap['debug'] ? esc_html__( 'On', 'plugin-conflict-reporter' ) : esc_html__( 'Off', 'plugin-conflict-reporter' ); ?></td></tr>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Active plugins by file time (newest first)', 'plugin-conflict-reporter' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Newest files are the first suspects after a conflict.', 'plugin-conflict-reporter' ); ?></p>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Plugin', 'plugin-conflict-reporter' ); ?></th><th><?php esc_html_e( 'File', 'plugin-conflict-reporter' ); ?></th><th><?php esc_html_e( 'File time', 'plugin-conflict-reporter' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $snap['recent_active'] as $row ) : ?>
					<tr>
						<td><?php echo esc_html( $row['name'] ); ?></td>
						<td><code><?php echo esc_html( $row['file'] ); ?></code></td>
						<td><?php echo $row['mtime'] ? esc_html( gmdate( 'Y-m-d H:i:s', $row['mtime'] ) . ' UTC' ) : '—'; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Recent change log', 'plugin-conflict-reporter' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'When', 'plugin-conflict-reporter' ); ?></th><th><?php esc_html_e( 'Type', 'plugin-conflict-reporter' ); ?></th><th><?php esc_html_e( 'Event', 'plugin-conflict-reporter' ); ?></th></tr></thead>
				<tbody>
				<?php if ( empty( $log ) ) : ?>
					<tr><td colspan="3"><?php esc_html_e( 'No events yet. Updates and activate/deactivate actions will appear here.', 'plugin-conflict-reporter' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( array_slice( $log, 0, 40 ) as $event ) : ?>
						<tr>
							<td><?php echo esc_html( gmdate( 'Y-m-d H:i:s', (int) $event['time'] ) . ' UTC' ); ?></td>
							<td><?php echo esc_html( $event['type'] ); ?></td>
							<td><?php echo esc_html( $event['message'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
