<?php
/**
 * Product list row duplicate action.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WCPQD_Plugin {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_filter( 'post_row_actions', array( __CLASS__, 'row_action' ), 10, 2 );
		add_action( 'admin_action_wcpqd_duplicate', array( __CLASS__, 'duplicate' ) );
	}

	/**
	 * Add link.
	 *
	 * @param array   $actions Actions.
	 * @param WP_Post $post Post.
	 * @return array
	 */
	public static function row_action( $actions, $post ) {
		if ( 'product' !== $post->post_type || ! current_user_can( 'edit_product', $post->ID ) ) {
			return $actions;
		}
		$url = wp_nonce_url(
			admin_url( 'admin.php?action=wcpqd_duplicate&product_id=' . $post->ID ),
			'wcpqd_duplicate_' . $post->ID
		);
		$actions['wcpqd_duplicate'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Quick duplicate', 'wc-product-quick-duplicate' ) . '</a>';
		return $actions;
	}

	/**
	 * Duplicate product as draft.
	 */
	public static function duplicate() {
		$product_id = isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : 0;
		check_admin_referer( 'wcpqd_duplicate_' . $product_id );

		if ( ! current_user_can( 'edit_product', $product_id ) ) {
			wp_die( esc_html__( 'Forbidden', 'wc-product-quick-duplicate' ) );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			wp_die( esc_html__( 'Invalid product.', 'wc-product-quick-duplicate' ) );
		}

		$new = clone $product;
		$new->set_id( 0 );
		$new->set_status( 'draft' );
		$new->set_slug( '' );
		$new->set_name( $product->get_name() . ' - copy' );
		$new->set_date_created( null );
		$new->set_total_sales( 0 );
		$new_id = $new->save();

		// Copy simple meta that WC clone may miss for variations parents is out of scope for this demo.
		wp_safe_redirect( admin_url( 'post.php?post=' . $new_id . '&action=edit' ) );
		exit;
	}
}
