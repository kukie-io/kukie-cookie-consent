<?php

/**
 * Two smaller state-machine invariants from the same round-D batch.
 *
 * KUK-QA-2026-389 - the one-time connect redirect. WordPress includes the
 * plugin file (which bootstraps the singleton, which writes kukie_settings)
 * BEFORE firing the activation hook, so activate() cannot detect a fresh
 * install by option absence. The marker written during that first write is
 * what makes the redirect reachable at all.
 *
 * KUK-QA-2026-387 - the admin-bar dot. It reads a local mirror, which is
 * correct (a front-end page load must not make a blocking HTTP call), but the
 * mirror has to be refreshed from the only payload that sees dashboard-side
 * toggles: GET /status.
 */
final class ActivationAndMirrorTest extends Kukie_Test_Case {

	// -----------------------------------------------------------------
	// KUK-QA-2026-389
	// -----------------------------------------------------------------

	public function test_a_fresh_activation_arms_the_connect_redirect(): void {
		// No option at all: a genuinely fresh install.
		Kukie_Plugin::instance();

		$this->assertTrue(
			kukie_test_stored_settings()['first_activation'] ?? false,
			'The first-ever write is the only moment a fresh install is distinguishable.'
		);

		Kukie_Plugin::activate();

		$this->assertNotFalse( get_transient( 'kukie_activation_redirect' ) );
		$this->assertArrayNotHasKey(
			'first_activation',
			kukie_test_stored_settings(),
			'The marker is one-shot; leaving it armed would bounce a later reactivation.'
		);
	}

	public function test_the_redirect_actually_fires_once_and_then_stops(): void {
		Kukie_Plugin::instance();
		Kukie_Plugin::activate();

		$admin = new Kukie_Admin( Kukie_Plugin::instance() );

		try {
			$admin->maybe_redirect();
			$this->fail( 'The armed transient should have produced a redirect.' );
		} catch ( Kukie_Redirect $redirect ) {
			$this->assertSame( 'https://example.test/wp-admin/admin.php?page=kukie-connect', $redirect->location );
		}

		// Second admin page load: the transient was consumed, so this must
		// return normally rather than redirect again.
		$admin->maybe_redirect();

		$this->assertCount( 1, $GLOBALS['kukie_test_redirects'], 'A one-time redirect that fires twice is a redirect loop.' );
	}

	public function test_an_already_connected_install_is_never_bounced_to_the_connect_page(): void {
		// A stale marker on a connected install (disconnect then reconnect on
		// an older build) must not send a working setup back to setup.
		$this->seedConnectedInstall( [ 'first_activation' => true ] );

		Kukie_Plugin::activate();

		$this->assertFalse( get_transient( 'kukie_activation_redirect' ) );
	}

	public function test_an_upgrade_from_an_earlier_version_does_not_arm_the_redirect(): void {
		$this->seedConnectedInstall( [ 'plugin_version' => '1.6.3' ] );

		Kukie_Plugin::instance();

		$this->assertArrayNotHasKey( 'first_activation', kukie_test_stored_settings() );
		$this->assertSame( KUKIE_VERSION, kukie_test_stored_settings()['plugin_version'] );
	}

	// -----------------------------------------------------------------
	// KUK-QA-2026-387
	// -----------------------------------------------------------------

	public function test_a_dashboard_side_toggle_reaches_the_admin_bar_mirror(): void {
		$this->seedConnectedInstall( [ 'banner_enabled' => true ] );

		// Someone turned the banner off at app.kukie.io. The plugin learns
		// about it here and nowhere else.
		kukie_test_queue_response( 200, [ 'banner_enabled' => false ] );

		$admin = new Kukie_Admin( Kukie_Plugin::instance() );
		$this->captureJson( fn () => $admin->ajax_get_status() );

		$this->assertFalse( kukie_test_stored_settings()['banner_enabled'] );
	}

	public function test_a_failed_status_call_leaves_the_mirror_alone(): void {
		$this->seedConnectedInstall( [ 'banner_enabled' => true ] );

		kukie_test_queue_response( 503, [ 'message' => 'Service Unavailable' ] );

		$admin    = new Kukie_Admin( Kukie_Plugin::instance() );
		$response = $this->captureJson( fn () => $admin->ajax_get_status() );

		$this->assertFalse( $response->ok );
		$this->assertTrue(
			kukie_test_stored_settings()['banner_enabled'],
			'An unreachable server is not a signal about the banner state.'
		);
	}

	public function test_a_status_call_completing_after_a_disconnect_writes_nothing(): void {
		$this->seedConnectedInstall( [ 'banner_enabled' => true ] );

		kukie_test_queue_response( 200, [ 'banner_enabled' => false ] );

		$admin = new Kukie_Admin( Kukie_Plugin::instance() );
		delete_option( 'kukie_settings' );

		$this->captureJson( fn () => $admin->ajax_get_status() );

		$this->assertSame( [], kukie_test_stored_settings() );
		$this->assertFalse(
			get_transient( 'kukie_dashboard_data' ),
			'The cached branch is served before any connection check, so caching here would fake a connected admin UI for 5 minutes.'
		);
	}
}
