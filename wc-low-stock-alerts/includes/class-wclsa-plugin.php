<?php
/**
 * Low stock email alerts.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WCLSA_Plugin {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_product_set_stock', array( __CLASS__, 'maybe_alert' ) );
		add_action( 'woocommerce_variation_set_stock', array( __CLASS__, 'maybe_alert' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
	}

	/**
	 * Settings.
	 */
	public static function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Low Stock Alerts', 'wc-low-stock-alerts' ),
			__( 'Low Stock Alerts', 'wc-low-stock-alerts' ),
			'manage_woocommerce',
			'wclsa-settings',
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Settings UI.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		if ( isset( $_POST['wclsa_threshold'] ) && check_admin_referer( 'wclsa_save' ) ) {
			update_option( 'wclsa_threshold', max( 0, absint( $_POST['wclsa_threshold'] ) ) );
			$email = isset( $_POST['wclsa_email'] ) ? sanitize_email( wp_unslash( $_POST['wclsa_email'] ) ) : '';
			update_option( 'wclsa_email', $email ? $email : get_option( 'admin_email' ) );
			echo '<div class="updated"><p>' . esc_html__( 'Saved.', 'wc-low-stock-alerts' ) . '</p></div>';
		}
		$threshold = (int) get_option( 'wclsa_threshold', 5 );
		$email     = get_option( 'wclsa_email', get_option( 'admin_email' ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Low stock alerts', 'wc-low-stock-alerts' ); ?></h1>
			<form method="post">
				<?php wp_nonce_field( 'wclsa_save' ); ?>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Threshold', 'wc-low-stock-alerts' ); ?></th>
						<td><input type="number" min="0" name="wclsa_threshold" value="<?php echo esc_attr( (string) $threshold ); ?>" /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Alert email', 'wc-low-stock-alerts' ); ?></th>
						<td><input type="email" class="regular-text" name="wclsa_email" value="<?php echo esc_attr( $email ); ?>" /></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Fire alert when stock crosses threshold.
	 *
	 * @param WC_Product $product Product.
	 */
	public static function maybe_alert( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return;
		}
		if ( ! $product->managing_stock() ) {
			return;
		}

		$threshold = (int) get_option( 'wclsa_threshold', 5 );
		$qty       = (int) $product->get_stock_quantity();
		if ( $qty > $threshold ) {
			$product->delete_meta_data( '_wclsa_alerted' );
			$product->save();
			return;
		}

		if ( $product->get_meta( '_wclsa_alerted' ) ) {
			return;
		}

		$email = get_option( 'wclsa_email', get_option( 'admin_email' ) );
		$subj  = sprintf(
			/* translators: %s product name */
			__( 'Low stock: %s', 'wc-low-stock-alerts' ),
			$product->get_name()
		);
		$body = sprintf(
			/* translators: 1: product 2: qty 3: id */
			__( "Product \"%1\$s\" (ID %3\$d) is at %2\$d units.", 'wc-low-stock-alerts' ),
			$product->get_name(),
			$qty,
			$product->get_id()
		);

		if ( wp_mail( $email, $subj, $body ) ) {
			$product->update_meta_data( '_wclsa_alerted', 1 );
			$product->save();
		}
	}
}
