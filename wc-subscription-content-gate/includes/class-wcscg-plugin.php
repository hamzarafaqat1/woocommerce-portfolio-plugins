<?php
/**
 * Content gate for active subscribers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WCSCG_Plugin {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'metabox' ) );
		add_action( 'save_post', array( __CLASS__, 'save_meta' ) );
		add_filter( 'the_content', array( __CLASS__, 'filter_content' ), 20 );
		add_shortcode( 'wcs_members_only', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * Meta box.
	 */
	public static function metabox() {
		foreach ( array( 'post', 'page' ) as $type ) {
			add_meta_box(
				'wcscg_gate',
				__( 'Subscription gate', 'wc-subscription-content-gate' ),
				array( __CLASS__, 'render_metabox' ),
				$type,
				'side'
			);
		}
	}

	/**
	 * Metabox UI.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_metabox( $post ) {
		$enabled = (int) get_post_meta( $post->ID, '_wcscg_require_active_sub', true );
		wp_nonce_field( 'wcscg_save', 'wcscg_nonce' );
		echo '<label><input type="checkbox" name="wcscg_require_active_sub" value="1" ' . checked( 1, $enabled, false ) . '> ';
		echo esc_html__( 'Require active subscription', 'wc-subscription-content-gate' );
		echo '</label>';
	}

	/**
	 * Save meta.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function save_meta( $post_id ) {
		if ( ! isset( $_POST['wcscg_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wcscg_nonce'] ) ), 'wcscg_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		$value = ! empty( $_POST['wcscg_require_active_sub'] ) ? 1 : 0;
		update_post_meta( $post_id, '_wcscg_require_active_sub', $value );
	}

	/**
	 * User has any active subscription?
	 *
	 * @param int $user_id User.
	 * @return bool
	 */
	public static function user_has_active_subscription( $user_id ) {
		if ( ! $user_id ) {
			return false;
		}
		if ( ! function_exists( 'wcs_user_has_subscription' ) ) {
			return false;
		}
		return (bool) wcs_user_has_subscription( $user_id, '', 'active' );
	}

	/**
	 * Filter full content when gated.
	 *
	 * @param string $content Content.
	 * @return string
	 */
	public static function filter_content( $content ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$post_id = get_the_ID();
		if ( ! get_post_meta( $post_id, '_wcscg_require_active_sub', true ) ) {
			return $content;
		}
		if ( self::user_has_active_subscription( get_current_user_id() ) ) {
			return $content;
		}
		return '<div class="wcscg-locked"><p>' . esc_html__( 'This content is available to active subscribers only.', 'wc-subscription-content-gate' ) . '</p></div>';
	}

	/**
	 * [wcs_members_only]...[/wcs_members_only]
	 *
	 * @param array       $atts Atts.
	 * @param string|null $content Content.
	 * @return string
	 */
	public static function shortcode( $atts, $content = null ) {
		if ( self::user_has_active_subscription( get_current_user_id() ) ) {
			return do_shortcode( (string) $content );
		}
		return '<em>' . esc_html__( 'Subscribers only.', 'wc-subscription-content-gate' ) . '</em>';
	}
}
