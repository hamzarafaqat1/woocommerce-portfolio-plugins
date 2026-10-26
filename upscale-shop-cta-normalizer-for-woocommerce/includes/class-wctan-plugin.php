<?php
/**
 * Normalize WooCommerce loop CTAs.
 *
 * @package Woo_CTA_Normalizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WCTAN_Plugin {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_filter( 'woocommerce_product_add_to_cart_text', array( __CLASS__, 'loop_text' ), 20, 2 );
		add_filter( 'woocommerce_product_single_add_to_cart_text', array( __CLASS__, 'single_text' ), 20, 2 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'styles' ), 40 );
	}

	/**
	 * Defaults.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'unify_labels'   => 1,
			'simple_label'   => 'Add to cart',
			'variable_label' => 'Choose options',
			'external_label' => 'Buy now',
			'grouped_label'  => 'View products',
			'style_buttons'  => 1,
		);
	}

	/**
	 * Settings.
	 *
	 * @return array
	 */
	public static function settings() {
		return wp_parse_args( (array) get_option( 'wctan_settings', array() ), self::defaults() );
	}

	/**
	 * Menu under WooCommerce.
	 */
	public static function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'CTA Normalizer', 'upscale-shop-cta-normalizer-for-woocommerce' ),
			__( 'CTA Normalizer', 'upscale-shop-cta-normalizer-for-woocommerce' ),
			'manage_woocommerce',
			'wctan-settings',
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Register.
	 */
	public static function register_settings() {
		register_setting(
			'wctan_group',
			'wctan_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Sanitize.
	 *
	 * @param array $input Raw.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		$out   = self::defaults();

		$out['unify_labels']   = empty( $input['unify_labels'] ) ? 0 : 1;
		$out['style_buttons']  = empty( $input['style_buttons'] ) ? 0 : 1;
		$out['simple_label']   = sanitize_text_field( $input['simple_label'] ?? $out['simple_label'] );
		$out['variable_label'] = sanitize_text_field( $input['variable_label'] ?? $out['variable_label'] );
		$out['external_label'] = sanitize_text_field( $input['external_label'] ?? $out['external_label'] );
		$out['grouped_label']  = sanitize_text_field( $input['grouped_label'] ?? $out['grouped_label'] );

		return $out;
	}

	/**
	 * Settings UI.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$s = self::settings();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Upscale Shop CTA Normalizer for WooCommerce', 'upscale-shop-cta-normalizer-for-woocommerce' ); ?></h1>
			<p><?php esc_html_e( 'Keep shop buttons consistent so customers are not confused by mixed â€œAdd to cart / Select options / Read moreâ€ labels.', 'upscale-shop-cta-normalizer-for-woocommerce' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( 'wctan_group' ); ?>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Normalize labels', 'upscale-shop-cta-normalizer-for-woocommerce' ); ?></th>
						<td><label><input type="checkbox" name="wctan_settings[unify_labels]" value="1" <?php checked( $s['unify_labels'], 1 ); ?> /> <?php esc_html_e( 'On', 'upscale-shop-cta-normalizer-for-woocommerce' ); ?></label></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Simple product', 'upscale-shop-cta-normalizer-for-woocommerce' ); ?></th>
						<td><input class="regular-text" name="wctan_settings[simple_label]" value="<?php echo esc_attr( $s['simple_label'] ); ?>" /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Variable product', 'upscale-shop-cta-normalizer-for-woocommerce' ); ?></th>
						<td><input class="regular-text" name="wctan_settings[variable_label]" value="<?php echo esc_attr( $s['variable_label'] ); ?>" /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'External product', 'upscale-shop-cta-normalizer-for-woocommerce' ); ?></th>
						<td><input class="regular-text" name="wctan_settings[external_label]" value="<?php echo esc_attr( $s['external_label'] ); ?>" /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Grouped product', 'upscale-shop-cta-normalizer-for-woocommerce' ); ?></th>
						<td><input class="regular-text" name="wctan_settings[grouped_label]" value="<?php echo esc_attr( $s['grouped_label'] ); ?>" /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Unify button look', 'upscale-shop-cta-normalizer-for-woocommerce' ); ?></th>
						<td><label><input type="checkbox" name="wctan_settings[style_buttons]" value="1" <?php checked( $s['style_buttons'], 1 ); ?> /> <?php esc_html_e( 'Apply shared CSS on loop buttons', 'upscale-shop-cta-normalizer-for-woocommerce' ); ?></label></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Loop button text.
	 *
	 * @param string     $text Text.
	 * @param WC_Product $product Product.
	 * @return string
	 */
	public static function loop_text( $text, $product ) {
		$s = self::settings();
		if ( empty( $s['unify_labels'] ) || ! $product instanceof WC_Product ) {
			return $text;
		}

		switch ( $product->get_type() ) {
			case 'variable':
				return $s['variable_label'];
			case 'external':
				return $s['external_label'];
			case 'grouped':
				return $s['grouped_label'];
			default:
				return $product->is_purchasable() && $product->is_in_stock() ? $s['simple_label'] : $text;
		}
	}

	/**
	 * Single page button (simple mainly).
	 *
	 * @param string     $text Text.
	 * @param WC_Product $product Product.
	 * @return string
	 */
	public static function single_text( $text, $product ) {
		$s = self::settings();
		if ( empty( $s['unify_labels'] ) || ! $product instanceof WC_Product ) {
			return $text;
		}
		if ( 'simple' === $product->get_type() ) {
			return $s['simple_label'];
		}
		if ( 'external' === $product->get_type() ) {
			return $s['external_label'];
		}
		return $text;
	}

	/**
	 * Shared loop button CSS.
	 */
	public static function styles() {
		$s = self::settings();
		if ( empty( $s['style_buttons'] ) ) {
			return;
		}
		$css = '.woocommerce ul.products li.product .button,.woocommerce ul.products li.product .added_to_cart{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:.6rem 1rem;border-radius:4px;text-align:center;width:100%;box-sizing:border-box}';
		wp_register_style( 'wctan-front', false, array(), WCTAN_VERSION );
		wp_enqueue_style( 'wctan-front' );
		wp_add_inline_style( 'wctan-front', $css );
	}
}
