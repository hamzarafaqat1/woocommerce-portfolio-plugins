<?php
/**
 * REST endpoints for staging QA / automation.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WCSMD_Rest {

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Routes.
	 */
	public static function register() {
		register_rest_route(
			'wcsmd/v1',
			'/templates',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'list_templates' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_woocommerce' );
				},
			)
		);

		register_rest_route(
			'wcsmd/v1',
			'/issue',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'issue' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_woocommerce' );
				},
				'args'                => array(
					'user_id'         => array( 'required' => true, 'type' => 'integer' ),
					'template_id'     => array( 'required' => true, 'type' => 'integer' ),
					'subscription_id' => array( 'required' => false, 'type' => 'integer', 'default' => 0 ),
					'send_email'      => array( 'required' => false, 'type' => 'boolean', 'default' => false ),
				),
			)
		);
	}

	/**
	 * GET templates.
	 *
	 * @return WP_REST_Response
	 */
	public static function list_templates() {
		return rest_ensure_response( WCSMD_Repository::get_active_templates() );
	}

	/**
	 * POST issue document.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function issue( WP_REST_Request $request ) {
		$result = WCSMD_Document_Service::accept_and_issue(
			(int) $request['user_id'],
			(int) $request['template_id'],
			(int) $request['subscription_id'],
			(bool) $request['send_email']
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}
}
