<?php

/**
 * KUK-QA-2026-385: local-only fields (script_position, force_language) must be
 * committed only AFTER the server accepted the save.
 *
 * The asymmetry this closes: 1.7.0 already mirrored banner_enabled after the
 * PUT, but wrote the two local-only fields before it, so a failed or
 * conflict-cancelled save left the toast saying "failed" while the placement
 * change had already taken effect - and switching to manual stops automatic
 * injection.
 *
 * Also covers the concurrent-disconnect guard on the same commit: a disconnect
 * landing during the PUT must not be resurrected by the local write that
 * follows it.
 */
final class SettingsSaveTest extends Kukie_Test_Case {

	private function postSave( array $overrides = [] ): void {
		$_POST = array_merge( [
			'script_position'   => 'manual',
			'force_language'    => 'de',
			'banner_enabled'    => '1',
			'auto_translate'    => '1',
			'default_language'  => 'en',
			'enabled_languages' => [ 'en', 'de' ],
			'config_version'    => '5',
		], $overrides );
	}

	public function test_a_failed_save_leaves_local_placement_untouched(): void {
		$this->seedConnectedInstall( [ 'script_position' => 'head', 'force_language' => 'auto' ] );
		$this->postSave();

		kukie_test_queue_response( 500, [ 'message' => 'Server Error' ] );

		$admin    = new Kukie_Admin( Kukie_Plugin::instance() );
		$response = $this->captureJson( fn () => $admin->ajax_save_settings() );

		$this->assertFalse( $response->ok );

		$settings = kukie_test_stored_settings();
		$this->assertSame( 'head', $settings['script_position'], 'The save failed, so the placement must not have moved.' );
		$this->assertSame( 'auto', $settings['force_language'] );
	}

	public function test_a_conflict_cancelled_save_leaves_local_placement_untouched(): void {
		$this->seedConnectedInstall( [ 'script_position' => 'head' ] );
		$this->postSave();

		kukie_test_queue_response( 409, [
			'error'           => 'Settings were modified elsewhere since they were loaded.',
			'current_version' => 9,
		] );

		$admin    = new Kukie_Admin( Kukie_Plugin::instance() );
		$response = $this->captureJson( fn () => $admin->ajax_save_settings() );

		$this->assertFalse( $response->ok );
		$this->assertSame( 'version_conflict', $response->data['code'] );
		$this->assertSame( 9, $response->data['current_version'], '0 is a real version, so the JS needs the number, not a truthiness check.' );
		$this->assertSame( 'head', kukie_test_stored_settings()['script_position'] );
	}

	public function test_a_successful_save_commits_local_placement(): void {
		$this->seedConnectedInstall( [ 'script_position' => 'head', 'force_language' => 'auto' ] );
		$this->postSave();

		kukie_test_queue_response( 200, $this->settingsPayload( [ 'config_version' => 6 ] ) );
		// send_settings_saved() re-reads /settings to return a usable version.
		kukie_test_queue_response( 200, $this->settingsPayload( [ 'config_version' => 6 ] ) );

		$admin    = new Kukie_Admin( Kukie_Plugin::instance() );
		$response = $this->captureJson( fn () => $admin->ajax_save_settings() );

		$this->assertTrue( $response->ok );
		$this->assertSame( 6, $response->data['config_version'] );

		$settings = kukie_test_stored_settings();
		$this->assertSame( 'manual', $settings['script_position'] );
		$this->assertSame( 'de', $settings['force_language'] );
	}

	public function test_an_unknown_force_language_falls_back_to_auto(): void {
		$this->seedConnectedInstall();
		$this->postSave( [ 'force_language' => 'klingon' ] );

		kukie_test_queue_response( 200, $this->settingsPayload() );
		kukie_test_queue_response( 200, $this->settingsPayload() );

		$admin = new Kukie_Admin( Kukie_Plugin::instance() );
		$this->captureJson( fn () => $admin->ajax_save_settings() );

		$this->assertSame( 'auto', kukie_test_stored_settings()['force_language'] );
	}

	public function test_a_save_completing_after_a_concurrent_disconnect_does_not_resurrect_the_install(): void {
		$this->seedConnectedInstall( [ 'script_position' => 'head' ] );
		$this->postSave();

		kukie_test_queue_response( 200, $this->settingsPayload() );
		kukie_test_queue_response( 200, $this->settingsPayload() );

		$admin = new Kukie_Admin( Kukie_Plugin::instance() );

		// The disconnect lands while the PUT is in flight.
		delete_option( 'kukie_settings' );

		$this->captureJson( fn () => $admin->ajax_save_settings() );

		$this->assertSame(
			[],
			kukie_test_stored_settings(),
			'A settings save must never re-create the option a disconnect just deleted.'
		);
	}

	public function test_the_server_error_message_reaches_the_user(): void {
		// KUK-QA-2026-384: Laravel-native responses (throttle, validation)
		// carry only `message`, so a client reading `error` alone showed a
		// generic string in place of the real, actionable reason.
		$this->seedConnectedInstall();
		$this->postSave();

		kukie_test_queue_response( 429, [ 'message' => 'Too Many Attempts.' ] );

		$admin    = new Kukie_Admin( Kukie_Plugin::instance() );
		$response = $this->captureJson( fn () => $admin->ajax_save_settings() );

		$this->assertSame( 'Too Many Attempts.', $response->message() );
	}

	public function test_the_dual_envelope_error_key_still_wins_when_present(): void {
		$this->seedConnectedInstall();
		$this->postSave();

		kukie_test_queue_response( 422, [ 'error' => 'Invalid default language.', 'message' => 'Invalid default language.' ] );

		$admin    = new Kukie_Admin( Kukie_Plugin::instance() );
		$response = $this->captureJson( fn () => $admin->ajax_save_settings() );

		$this->assertSame( 'Invalid default language.', $response->message() );
	}

	public function test_a_non_json_error_body_falls_back_to_the_generic_message(): void {
		// An HTML 502 from a proxy decodes to null, which must not fatal or
		// surface as an empty toast.
		$this->seedConnectedInstall();
		$this->postSave();

		kukie_test_queue_raw_response( 502, '<html><body>Bad Gateway</body></html>' );

		$admin    = new Kukie_Admin( Kukie_Plugin::instance() );
		$response = $this->captureJson( fn () => $admin->ajax_save_settings() );

		$this->assertSame( 'API error.', $response->message() );
	}
}
