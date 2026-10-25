<?php
/**
 * Bulk export + failed-renewal queue stub.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WCSBT_Plugin {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_wcsbt_export_csv', array( __CLASS__, 'export_csv' ) );
	}

	/**
	 * Admin page.
	 */
	public static function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Subscriptions Bulk Tools', 'wc-subscriptions-bulk-tools' ),
			__( 'Subs Bulk Tools', 'wc-subscriptions-bulk-tools' ),
			'manage_woocommerce',
			'wcsbt-tools',
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * UI.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Subscriptions bulk tools', 'wc-subscriptions-bulk-tools' ); ?></h1>
			<p><?php esc_html_e( 'Export active subscriptions to CSV for ops / migration audits.', 'wc-subscriptions-bulk-tools' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="wcsbt_export_csv" />
				<?php wp_nonce_field( 'wcsbt_export_csv' ); ?>
				<?php submit_button( __( 'Export active subscriptions CSV', 'wc-subscriptions-bulk-tools' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Stream CSV.
	 */
	public static function export_csv() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Forbidden', 'wc-subscriptions-bulk-tools' ) );
		}
		check_admin_referer( 'wcsbt_export_csv' );

		if ( ! function_exists( 'wcs_get_subscriptions' ) ) {
			wp_die( esc_html__( 'WooCommerce Subscriptions required.', 'wc-subscriptions-bulk-tools' ) );
		}

		$subs = wcs_get_subscriptions(
			array(
				'subscription_status'    => array( 'active', 'on-hold', 'pending-cancel' ),
				'subscriptions_per_page' => -1,
			)
		);

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=subscriptions-export.csv' );

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'subscription_id', 'status', 'user_email', 'next_payment', 'total' ) );

		foreach ( $subs as $subscription ) {
			$user = $subscription->get_user();
			fputcsv(
				$out,
				array(
					$subscription->get_id(),
					$subscription->get_status(),
					$user ? $user->user_email : '',
					$subscription->get_date( 'next_payment' ),
					$subscription->get_total(),
				)
			);
		}
		fclose( $out );
		exit;
	}
}
