<?php
/**
 * Member-facing documents page (shortcode).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WCSMD_Frontend {

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_wcsmd_accept_document', array( __CLASS__, 'handle_accept' ) );
		add_action( 'admin_post_nopriv_wcsmd_accept_document', array( __CLASS__, 'handle_accept_guest' ) );
	}

	/**
	 * Front styles.
	 */
	public static function assets() {
		if ( ! is_singular() ) {
			return;
		}
		wp_register_style( 'wcsmd-front', false, array(), WCSMD_VERSION );
		wp_enqueue_style( 'wcsmd-front' );
		wp_add_inline_style(
			'wcsmd-front',
			'.wcsmd-docs{max-width:720px;margin:1.5rem auto;font-family:system-ui,sans-serif}.wcsmd-docs table{width:100%;border-collapse:collapse}.wcsmd-docs th,.wcsmd-docs td{border-bottom:1px solid #ddd;padding:.6rem;text-align:left}.wcsmd-docs .button{display:inline-block;padding:.45rem .9rem;background:#1e3a5f;color:#fff;text-decoration:none;border-radius:4px}'
		);
	}

	/**
	 * [wcsmd_documents]
	 *
	 * @return string
	 */
	public static function shortcode_documents() {
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Please log in to view your documents.', 'wc-subscriptions-member-documents' ) . '</p>';
		}

		$user_id   = get_current_user_id();
		$templates = WCSMD_Repository::get_active_templates();
		$archives  = WCSMD_Repository::get_user_archives( $user_id );

		ob_start();
		echo '<div class="wcsmd-docs">';
		echo '<h2>' . esc_html__( 'Membership documents', 'wc-subscriptions-member-documents' ) . '</h2>';

		if ( empty( $templates ) ) {
			echo '<p>' . esc_html__( 'No documents are available yet.', 'wc-subscriptions-member-documents' ) . '</p>';
		} else {
			echo '<table><thead><tr>';
			echo '<th>' . esc_html__( 'Document', 'wc-subscriptions-member-documents' ) . '</th>';
			echo '<th>' . esc_html__( 'Version', 'wc-subscriptions-member-documents' ) . '</th>';
			echo '<th>' . esc_html__( 'Status', 'wc-subscriptions-member-documents' ) . '</th>';
			echo '<th></th></tr></thead><tbody>';

			foreach ( $templates as $template ) {
				$version = WCSMD_Repository::get_current_version( (int) $template->id );
				$ver_num = $version ? (int) $version->version_number : 0;
				$accepted = $version ? WCSMD_Repository::has_accepted_version( $user_id, (int) $version->id ) : false;

				echo '<tr>';
				echo '<td>' . esc_html( $template->title ) . '</td>';
				echo '<td>v' . esc_html( (string) $ver_num ) . '</td>';
				echo '<td>' . ( $accepted ? esc_html__( 'Accepted', 'wc-subscriptions-member-documents' ) : esc_html__( 'Action needed', 'wc-subscriptions-member-documents' ) ) . '</td>';
				echo '<td>';
				if ( $version && ! $accepted ) {
					$url = wp_nonce_url(
						admin_url( 'admin-post.php?action=wcsmd_accept_document&template_id=' . (int) $template->id ),
						'wcsmd_accept_' . (int) $template->id
					);
					echo '<a class="button" href="' . esc_url( $url ) . '">' . esc_html__( 'Review & accept', 'wc-subscriptions-member-documents' ) . '</a>';
				}
				echo '</td></tr>';
			}
			echo '</tbody></table>';
		}

		echo '<h3>' . esc_html__( 'Your archive', 'wc-subscriptions-member-documents' ) . '</h3>';
		if ( empty( $archives ) ) {
			echo '<p>' . esc_html__( 'No archived PDFs yet.', 'wc-subscriptions-member-documents' ) . '</p>';
		} else {
			echo '<ul>';
			foreach ( $archives as $row ) {
				echo '<li>';
				echo esc_html( $row->template_title . ' (v' . $row->version_number . ') — ' . $row->created_at );
				echo '</li>';
			}
			echo '</ul>';
		}

		echo '</div>';
		return (string) ob_get_clean();
	}

	/**
	 * Accept handler (logged-in).
	 */
	public static function handle_accept() {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Login required.', 'wc-subscriptions-member-documents' ) );
		}

		$template_id = isset( $_GET['template_id'] ) ? absint( $_GET['template_id'] ) : 0;
		check_admin_referer( 'wcsmd_accept_' . $template_id );

		$subscription_id = 0;
		if ( function_exists( 'wcs_get_users_subscriptions' ) ) {
			$subs = wcs_get_users_subscriptions( get_current_user_id() );
			foreach ( $subs as $sub ) {
				if ( $sub->has_status( array( 'active', 'pending-cancel' ) ) ) {
					$subscription_id = $sub->get_id();
					break;
				}
			}
		}

		$result = WCSMD_Document_Service::accept_and_issue(
			get_current_user_id(),
			$template_id,
			$subscription_id,
			true
		);

		$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );
		if ( is_wp_error( $result ) ) {
			$redirect = add_query_arg( 'wcsmd_error', rawurlencode( $result->get_error_message() ), $redirect );
		} else {
			$redirect = add_query_arg( 'wcsmd_ok', '1', $redirect );
		}
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Guests cannot accept.
	 */
	public static function handle_accept_guest() {
		auth_redirect();
	}
}
