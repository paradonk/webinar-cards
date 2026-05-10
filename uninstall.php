<?php
/**
 * Uninstall handler.
 *
 * @package WebinarCards
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Safe default: keep data on uninstall.
// Delete webinar posts and meta manually only if you explicitly want destructive cleanup.
