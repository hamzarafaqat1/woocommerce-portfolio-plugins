<?php
/**
 * Uninstall cleanup.
 *
 * @package Elementor_Mobile_Spacing_Fixer
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'emsf_settings' );
