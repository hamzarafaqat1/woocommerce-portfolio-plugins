<?php
/**
 * Renewal reminder scheduler.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WCSRR_Plugin {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'wcsrr_daily_scan', array( __CLASS__, 'scan' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
	}

	/**
	 * Settings page.
	 */
	public static function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Renewal Reminders', 'wc-subscriptions-renewal-reminders' ),
			__( 'Renewal Reminders', 'wc-subscriptions-renewal-reminders' ),
			'manage_woocommerce',
			'wcsrr-settings',
			array( __CLASS__, 'render_settings' )
		);
	}

	/**
	 * Simple settings UI.
	 */
	public static function render_settings() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		if ( isset( $_POST['wcsrr_days'] ) && check_admin_referer( 'wcsrr_save' ) ) {
			update_option( 'wcsrr_days_before', max( 1, absint( $_POST['wcsrr_days'] ) ) );
			echo '<div class="updated"><p>' . esc_html__( 'Saved.', 'wc-subscriptions-renewal-reminders' ) . '</p></div>';
		}
		$days = (int) get_option( 'wcsrr_days_before', 3 );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Subscription renewal reminders', 'wc-subscriptions-renewal-reminders' ); ?></h1>
			<form method="post">
				<?php wp_nonce_field( 'wcsrr_save' ); ?>
				<p>
					<label>
						<?php esc_html_e( 'Days before renewal', 'wc-subscriptions-renewal-reminders' ); ?>
						<input type="number" min="1" name="wcsrr_days" value="<?php echo esc_attr( (string) $days ); ?>" />
					</label>
				</p>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Daily scan of active subscriptions.
	 */
	public static function scan() {
		if ( ! function_exists( 'wcs_get_subscriptions' ) ) {
			return;
		}

		$days = (int) get_option( 'wcsrr_days_before', 3 );
		$subs = wcs_get_subscriptions(
			array(
				'subscription_status' => array( 'active' ),
				'subscriptions_per_page' => 50,
			)
		);

		$target = gmdate( 'Y-m-d', time() + ( $days * DAY_IN_SECONDS ) );

		foreach ( $subs as $subscription ) {
			$next = $subscription->get_date( 'next_payment' );
			if ( ! $next ) {
				continue;
			}
			$next_day = gmdate( 'Y-m-d', strtotime( $next ) );
			if ( $next_day !== $target ) {
				continue;
			}

			$meta_key = 'wcsrr_sent_' . $next_day;
			if ( $subscription->get_meta( $meta_key ) ) {
				continue;
			}

			$user = $subscription->get_user();
			if ( ! $user ) {
				continue;
			}

			$subject = sprintf(
				/* translators: %s site name */
				__( 'Upcoming renewal on %s', 'wc-subscriptions-renewal-reminders' ),
				wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
			);
			$body = sprintf(
				/* translators: 1: name 2: date 3: subscription id */
				__( "Hi %1\$s,\n\nYour subscription #%3\$d renews on %2\$s.\n\nNo action needed if your payment method is up to date.", 'wc-subscriptions-renewal-reminders' ),
				$user->display_name,
				$next_day,
				$subscription->get_id()
			);

			if ( wp_mail( $user->user_email, $subject, $body ) ) {
				$subscription->update_meta_data( $meta_key, 1 );
				$subscription->save();
			}
		}
	}
}
