<?php
/**
 * Uninstall.
 *
 * @package Woo_CTA_Normalizer
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wctan_settings' );
