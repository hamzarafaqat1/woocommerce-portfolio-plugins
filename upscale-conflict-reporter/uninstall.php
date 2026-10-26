<?php
/**
 * Uninstall.
 *
 * @package Plugin_Conflict_Reporter
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'pcr_event_log' );
