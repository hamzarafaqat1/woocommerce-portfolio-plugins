<?php
/**
 * Subscription activity logger.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WCSAL_Plugin {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_subscription_status_updated', array( __CLASS__, 'on_status' ), 10, 3 );
		add_action( 'woocommerce_subscription_renewal_payment_complete', array( __CLASS__, 'on_renewal' ), 10, 2 );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
	}

	/**
	 * Create log table.
	 */
	public static function install() {
		global $wpdb;
		$table   = $wpdb->prefix . 'wcsal_log';
		$charset = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta(
			"CREATE TABLE {$table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				subscription_id bigint(20) unsigned NOT NULL,
				event_type varchar(64) NOT NULL,
				message text NOT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY (id),
				KEY subscription_id (subscription_id)
			) {$charset};"
		);
	}

	/**
	 * Write row.
	 *
	 * @param int    $subscription_id ID.
	 * @param string $event Event key.
	 * @param string $message Message.
	 */
	public static function log( $subscription_id, $event, $message ) {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'wcsal_log',
			array(
				'subscription_id' => absint( $subscription_id ),
				'event_type'      => sanitize_key( $event ),
				'message'         => sanitize_text_field( $message ),
				'created_at'      => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s' )
		);
	}

	/**
	 * Status change.
	 *
	 * @param WC_Subscription $subscription Subscription.
	 * @param string          $new_status New.
	 * @param string          $old_status Old.
	 */
	public static function on_status( $subscription, $new_status, $old_status ) {
		self::log(
			$subscription->get_id(),
			'status_change',
			sprintf( 'Status %s → %s', $old_status, $new_status )
		);
	}

	/**
	 * Renewal paid.
	 *
	 * @param WC_Subscription $subscription Subscription.
	 * @param WC_Order        $order Order.
	 */
	public static function on_renewal( $subscription, $order ) {
		self::log(
			$subscription->get_id(),
			'renewal',
			sprintf( 'Renewal payment complete (order #%d)', $order ? $order->get_id() : 0 )
		);
	}

	/**
	 * Admin list.
	 */
	public static function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Subscription Activity', 'wc-subscriptions-activity-log' ),
			__( 'Sub Activity Log', 'wc-subscriptions-activity-log' ),
			'manage_woocommerce',
			'wcsal-log',
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Render recent events.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		global $wpdb;
		$table = $wpdb->prefix . 'wcsal_log';
		$rows  = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC LIMIT 100" );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Subscription activity log', 'wc-subscriptions-activity-log' ); ?></h1>
			<table class="widefat striped">
				<thead><tr><th>ID</th><th><?php esc_html_e( 'Subscription', 'wc-subscriptions-activity-log' ); ?></th><th><?php esc_html_e( 'Event', 'wc-subscriptions-activity-log' ); ?></th><th><?php esc_html_e( 'Message', 'wc-subscriptions-activity-log' ); ?></th><th><?php esc_html_e( 'When', 'wc-subscriptions-activity-log' ); ?></th></tr></thead>
				<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No events yet.', 'wc-subscriptions-activity-log' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><?php echo esc_html( (string) $row->id ); ?></td>
							<td>#<?php echo esc_html( (string) $row->subscription_id ); ?></td>
							<td><?php echo esc_html( $row->event_type ); ?></td>
							<td><?php echo esc_html( $row->message ); ?></td>
							<td><?php echo esc_html( $row->created_at ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
