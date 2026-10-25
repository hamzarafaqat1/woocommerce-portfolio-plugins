<?php
/**
 * Role-based product pricing.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WCRBP_Plugin {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_product_options_pricing', array( __CLASS__, 'fields' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save' ) );
		add_filter( 'woocommerce_product_get_price', array( __CLASS__, 'filter_price' ), 20, 2 );
		add_filter( 'woocommerce_product_get_regular_price', array( __CLASS__, 'filter_price' ), 20, 2 );
		add_filter( 'woocommerce_product_variation_get_price', array( __CLASS__, 'filter_price' ), 20, 2 );
		add_filter( 'woocommerce_product_variation_get_regular_price', array( __CLASS__, 'filter_price' ), 20, 2 );
	}

	/**
	 * Target role (default wholesale customer).
	 *
	 * @return string
	 */
	public static function target_role() {
		return apply_filters( 'wcrbp_target_role', 'wholesale_customer' );
	}

	/**
	 * Product data fields.
	 */
	public static function fields() {
		woocommerce_wp_text_input(
			array(
				'id'          => '_wcrbp_role_price',
				'label'       => __( 'Role price (wholesale)', 'wc-role-based-pricing' ),
				'desc_tip'    => true,
				'description' => __( 'Shown to users with the wholesale_customer role when set.', 'wc-role-based-pricing' ),
				'data_type'   => 'price',
			)
		);
	}

	/**
	 * Save meta.
	 *
	 * @param int $post_id Product ID.
	 */
	public static function save( $post_id ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['_wcrbp_role_price'] ) ) {
			return;
		}
		$price = wc_format_decimal( wp_unslash( $_POST['_wcrbp_role_price'] ) );
		update_post_meta( $post_id, '_wcrbp_role_price', $price );
	}

	/**
	 * Apply role price.
	 *
	 * @param string     $price Price.
	 * @param WC_Product $product Product.
	 * @return string
	 */
	public static function filter_price( $price, $product ) {
		if ( ! is_user_logged_in() ) {
			return $price;
		}
		$user = wp_get_current_user();
		if ( ! in_array( self::target_role(), (array) $user->roles, true ) ) {
			return $price;
		}
		$role_price = $product->get_meta( '_wcrbp_role_price' );
		if ( '' === $role_price || null === $role_price ) {
			return $price;
		}
		return $role_price;
	}
}
