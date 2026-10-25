<?php
/**
 * Order CSV export.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WCOEC_Plugin {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_wcoec_export', array( __CLASS__, 'export' ) );
	}

	/**
	 * Menu.
	 */
	public static function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Order Export CSV', 'wc-order-export-csv' ),
			__( 'Order Export CSV', 'wc-order-export-csv' ),
			'manage_woocommerce',
			'wcoec-export',
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
			<h1><?php esc_html_e( 'Export orders to CSV', 'wc-order-export-csv' ); ?></h1>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="wcoec_export" />
				<?php wp_nonce_field( 'wcoec_export' ); ?>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'From', 'wc-order-export-csv' ); ?></th>
						<td><input type="date" name="date_from" /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'To', 'wc-order-export-csv' ); ?></th>
						<td><input type="date" name="date_to" /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Status', 'wc-order-export-csv' ); ?></th>
						<td>
							<select name="status">
								<option value="any"><?php esc_html_e( 'Any', 'wc-order-export-csv' ); ?></option>
								<?php foreach ( wc_get_order_statuses() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Download CSV', 'wc-order-export-csv' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Stream CSV.
	 */
	public static function export() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Forbidden', 'wc-order-export-csv' ) );
		}
		check_admin_referer( 'wcoec_export' );

		$args = array(
			'limit'   => -1,
			'orderby' => 'date',
			'order'   => 'DESC',
			'return'  => 'objects',
		);

		$status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'any';
		if ( 'any' !== $status ) {
			$args['status'] = str_replace( 'wc-', '', $status );
		}

		$from = isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : '';
		$to   = isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : '';
		if ( $from || $to ) {
			$args['date_created'] = trim( ( $from ? $from : '1970-01-01' ) . '...' . ( $to ? $to : gmdate( 'Y-m-d' ) ) );
		}

		$orders = wc_get_orders( $args );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=wc-orders-export.csv' );

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'order_id', 'date', 'status', 'email', 'total', 'currency', 'payment_method' ) );

		foreach ( $orders as $order ) {
			fputcsv(
				$out,
				array(
					$order->get_id(),
					$order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : '',
					$order->get_status(),
					$order->get_billing_email(),
					$order->get_total(),
					$order->get_currency(),
					$order->get_payment_method_title(),
				)
			);
		}
		fclose( $out );
		exit;
	}
}
