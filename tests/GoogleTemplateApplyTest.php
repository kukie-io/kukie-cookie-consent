<?php

/**
 * 1.10.0 feature lock (KUK-QA-2026-1008): "Apply Google's banner text" on the
 * Google Consent Mode v2 tab.
 *
 * Properties of ajax_apply_google_template():
 * - it sends ONE request, POST /google-template, with no body: the text and
 *   the switches it turns on are the Kukie.io editor's own implementation,
 *   never a copy in this repository;
 * - a success hands the page the new template state and config_version (so
 *   the next Save carries the version the apply produced) and moves only the
 *   CDN cache-buster locally, like a settings save;
 * - the settings cache is dropped, so the next page load reads the new state;
 * - a plan without Consent Mode gets the structured upgrade error the other
 *   plan-gated pages render;
 * - a reply landing after a concurrent disconnect re-creates nothing.
 *
 * Plus the GCM tab's settings load asks for fresh=1 (admin.js), so a cached
 * pre-1.10.0 payload without the block cannot hide the card.
 *
 * These tests cannot fail against 1.7.0 for the interesting reason (the
 * handler does not exist there); they lock a new surface.
 */
final class GoogleTemplateApplyTest extends Kukie_Test_Case {

	private function template( array $overrides = [] ): array {
		return array_merge( [
			'applied'           => true,
			'intact'            => true,
			'languages'         => [],
			'accept_all_hidden' => [],
			'english'           => [],
			'translated'        => [ [ 'locale' => 'en', 'name' => 'English' ] ],
		], $overrides );
	}

	public function test_the_apply_posts_once_and_hands_back_the_state_and_version(): void {
		$this->seedConnectedInstall( [ 'config_version' => '100' ] );
		$_POST = [];
		set_transient( 'kukie_settings_cache', [ 'config_version' => 5 ], 600 );

		kukie_test_queue_response( 200, [
			'message'         => "Google's banner text is applied, and Google Consent Mode is on.",
			'gcm_v2_enabled'  => true,
			'google_template' => $this->template(),
			'config_version'  => 6,
		] );

		$admin    = new Kukie_Admin( Kukie_Plugin::instance() );
		$response = $this->captureJson( fn () => $admin->ajax_apply_google_template() );

		$this->assertTrue( $response->ok );
		$this->assertSame( 6, $response->data['config_version'] );
		$this->assertTrue( $response->data['google_template']['intact'] );
		$this->assertNotSame( '', $response->message() );

		$log = kukie_test_http_log();
		$this->assertCount( 1, $log, 'One request: the apply answers with everything the tab needs.' );
		$this->assertSame( 'POST', $log[0]['args']['method'] );
		$this->assertStringEndsWith( '/google-template', $log[0]['url'] );
		$this->assertArrayNotHasKey( 'body', $log[0]['args'], 'The plugin sends no text of its own.' );

		$this->assertFalse( get_transient( 'kukie_settings_cache' ), 'The next load must read the new template state.' );
		$this->assertNotSame( '100', kukie_test_stored_settings()['config_version'], 'The CDN cache-buster moves, like a settings save.' );
	}

	public function test_nothing_but_the_cache_buster_is_stored_locally(): void {
		$this->seedConnectedInstall();
		$before = kukie_test_stored_settings();
		$_POST  = [];

		kukie_test_queue_response( 200, [ 'google_template' => $this->template(), 'config_version' => 6 ] );

		$admin = new Kukie_Admin( Kukie_Plugin::instance() );
		$this->captureJson( fn () => $admin->ajax_apply_google_template() );

		$after = kukie_test_stored_settings();
		unset( $before['config_version'], $after['config_version'] );
		$this->assertSame( $before, $after );
	}

	public function test_a_plan_without_consent_mode_becomes_the_structured_upgrade_error(): void {
		$this->seedConnectedInstall();
		$_POST = [];
		set_transient( 'kukie_settings_cache', [ 'config_version' => 5 ], 600 );

		kukie_test_queue_response( 403, [
			'error'         => 'Google Consent Mode is not included in your plan.',
			'message'       => 'Google Consent Mode is not included in your plan.',
			'code'          => 'plan_upgrade_required',
			'feature'       => 'google_consent_mode',
			'required_plan' => 'Pro',
			'upgrade_url'   => 'https://app.kukie.io/billing',
		] );

		$admin    = new Kukie_Admin( Kukie_Plugin::instance() );
		$response = $this->captureJson( fn () => $admin->ajax_apply_google_template() );

		$this->assertFalse( $response->ok );
		$this->assertSame( 'plan_upgrade_required', $response->data['code'] );
		$this->assertSame( 'google_consent_mode', $response->data['feature'] );
		$this->assertSame( 'Google Consent Mode is not included in your plan.', $response->message() );
		$this->assertFalse( get_transient( 'kukie_settings_cache' ) );
	}

	public function test_a_failure_is_an_error_with_the_services_reason_and_stores_nothing(): void {
		$this->seedConnectedInstall( [ 'config_version' => '100' ] );
		$_POST = [];

		kukie_test_queue_response( 404, [ 'error' => 'Banner not configured.', 'message' => 'Banner not configured.' ] );

		$admin    = new Kukie_Admin( Kukie_Plugin::instance() );
		$response = $this->captureJson( fn () => $admin->ajax_apply_google_template() );

		$this->assertFalse( $response->ok );
		$this->assertSame( 'Banner not configured.', $response->message() );
		$this->assertSame( '100', kukie_test_stored_settings()['config_version'], 'A failed apply moves nothing locally.' );
	}

	public function test_a_reply_after_a_concurrent_disconnect_recreates_nothing(): void {
		$this->seedConnectedInstall();
		$_POST = [];

		kukie_test_queue_response( 200, [ 'google_template' => $this->template(), 'config_version' => 6 ] );

		$admin = new Kukie_Admin( Kukie_Plugin::instance() );

		// The disconnect lands while the POST is in flight.
		delete_option( 'kukie_settings' );

		$this->captureJson( fn () => $admin->ajax_apply_google_template() );

		$this->assertSame( [], kukie_test_stored_settings(), 'The apply must never re-create the option a disconnect just deleted.' );
	}

	public function test_the_handler_is_registered_and_the_tab_loads_fresh(): void {
		$admin = new Kukie_Admin( Kukie_Plugin::instance() );
		$admin->init();
		$this->assertTrue( kukie_test_hook_registered( 'wp_ajax_kukie_apply_google_template' ) );

		$js = (string) file_get_contents( KUKIE_PLUGIN_DIR . 'assets/js/admin.js' );
		$this->assertMatchesRegularExpression(
			"/async function loadGcmSettings[\\s\\S]*?kukieAjax\\('kukie_get_settings', \\{ fresh: '1' \\}\\)/",
			$js,
			'The Consent Mode tab must load fresh, or a cached payload from before the service update hides the card.'
		);
	}
}
