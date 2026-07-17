<?php

// The only thing standing between this file and a direct web request.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Per-site cleanup, shared by the single-site and multisite branches so the
 * two cannot drift.
 */
function kukie_uninstall_site(): void {
	delete_option( 'kukie_settings' );
	delete_transient( 'kukie_dashboard_data' );
	delete_transient( 'kukie_settings_cache' );
	delete_transient( 'kukie_activation_redirect' );
}

if ( is_multisite() ) {
	// number => 0 returns every site. Uninstall is a one-shot operation, so
	// walking even a large network in one pass is acceptable here.
	foreach ( get_sites( [ 'fields' => 'ids', 'number' => 0 ] ) as $kukie_blog_id ) {
		switch_to_blog( $kukie_blog_id );
		kukie_uninstall_site();
		restore_current_blog();
	}
} else {
	kukie_uninstall_site();
}

// User meta is network-wide on multisite, so this runs once, outside the
// per-site loop. The 0 user id with the true delete-all flag removes the
// dismissal flag for every user; a no-op when no user ever dismissed it.
delete_metadata( 'user', 0, 'kukie_wp_rocket_notice_dismissed', '', true );
