<?php

/**
 * 1.8.0 feature lock (not a finding lock): the Accessibility widget page.
 *
 * Three properties of ajax_save_a11y():
 * - every posted value is coerced to the server's whitelists BEFORE the PUT,
 *   so a stray form value can never 422 the whole save;
 * - the block is forwarded under `accessibility_widget` and NOTHING is
 *   mirrored into kukie_settings - app.kukie.io is the only source of truth
 *   for these settings (the page re-reads them on every load);
 * - a plan-gated 403 from the server becomes a structured error the page
 *   renders as an upgrade CTA, with the settings cache dropped so the next
 *   load re-checks the plan.
 *
 * These tests cannot fail against 1.7.0 for the interesting reason (the
 * handler does not exist there); they lock a new surface, not a regression.
 */
final class AccessibilityWidgetSaveTest extends Kukie_Test_Case {

	private function postA11y( array $overrides = [] ): void {
		$_POST = array_merge( [
			'enabled'           => '1',
			'position'          => 'bottom-left',
			'color'             => '#112233',
			'size'              => '52',
			'hide_mobile'       => '1',
			'hidden_modules'    => [ 'textSize', 'tts' ],
			'statement_enabled' => '0',
			'statement_url'     => 'https://example.com/accessibility',
			'languages'         => [ 'de', 'pt-br' ],
			'default_language'  => 'de',
			'config_version'    => '5',
		], $overrides );
	}

	/** The JSON body of the PUT /settings request, decoded. */
	private function putBody(): array {
		foreach ( kukie_test_http_log() as $request ) {
			if ( ( $request['args']['method'] ?? '' ) === 'PUT' ) {
				return json_decode( (string) $request['args']['body'], true );
			}
		}

		$this->fail( 'No PUT /settings request was made.' );
	}

	public function test_a_well_formed_form_is_forwarded_as_the_accessibility_block(): void {
		$this->seedConnectedInstall();
		$this->postA11y();

		kukie_test_queue_response( 200, [ 'message' => 'Settings updated.' ] );
		kukie_test_queue_response( 200, $this->settingsPayload( [ 'config_version' => 6 ] ) );

		$admin    = new Kukie_Admin( Kukie_Plugin::instance() );
		$response = $this->captureJson( fn () => $admin->ajax_save_a11y() );

		$this->assertTrue( $response->ok );
		$this->assertSame( 6, $response->data['config_version'] );

		$body = $this->putBody();
		$this->assertSame( 5, $body['config_version'], 'The optimistic lock version rides along.' );
		$this->assertSame( [
			'enabled'           => true,
			'position'          => 'bottom-left',
			'color'             => '#112233',
			'size'              => 52,
			'hide_mobile'       => true,
			'hidden_modules'    => [ 'textSize', 'tts' ],
			'statement_enabled' => false,
			'statement_url'     => 'https://example.com/accessibility',
			'languages'         => [ 'de', 'pt-br' ],
			'default_language'  => 'de',
		], $body['accessibility_widget'] );
	}

	public function test_off_whitelist_values_are_coerced_never_forwarded(): void {
		$this->seedConnectedInstall();
		$this->postA11y( [
			'position'         => 'top-left',                 // removed 2026-08-31
			'size'             => '40',                       // 44 is the floor
			'color'            => 'red',                      // #rrggbb only
			'statement_url'    => 'javascript:alert(1)',      // http(s) only
			'hidden_modules'   => [ 'text Size!', 'tts', 'tts', '' ],
			'languages'        => [ 'pt-br', '<de>' ],
			'default_language' => 'p t-br',
		] );

		kukie_test_queue_response( 200, [ 'message' => 'Settings updated.' ] );
		kukie_test_queue_response( 200, $this->settingsPayload() );

		$admin = new Kukie_Admin( Kukie_Plugin::instance() );
		$this->captureJson( fn () => $admin->ajax_save_a11y() );

		$block = $this->putBody()['accessibility_widget'];
		$this->assertSame( 'bottom-right', $block['position'] );
		$this->assertSame( 44, $block['size'] );
		$this->assertSame( '', $block['color'], 'An invalid colour falls back to inherit, never 422s the save.' );
		$this->assertSame( '', $block['statement_url'] );
		$this->assertSame( [ 'textSize', 'tts' ], $block['hidden_modules'], 'Case preserved (camelCase module keys), junk stripped, de-duplicated.' );
		$this->assertSame( [ 'pt-br', 'de' ], $block['languages'] );
		$this->assertSame( 'pt-br', $block['default_language'] );
	}

	public function test_an_absent_array_means_an_empty_list(): void {
		// FormData never sends an empty array; absence IS the empty list
		// (all modules visible, no custom language selection).
		$this->seedConnectedInstall();
		$this->postA11y();
		unset( $_POST['hidden_modules'], $_POST['languages'] );

		kukie_test_queue_response( 200, [ 'message' => 'Settings updated.' ] );
		kukie_test_queue_response( 200, $this->settingsPayload() );

		$admin = new Kukie_Admin( Kukie_Plugin::instance() );
		$this->captureJson( fn () => $admin->ajax_save_a11y() );

		$block = $this->putBody()['accessibility_widget'];
		$this->assertSame( [], $block['hidden_modules'] );
		$this->assertSame( [], $block['languages'] );
	}

	public function test_nothing_is_mirrored_into_the_local_option(): void {
		$this->seedConnectedInstall();
		$this->postA11y();

		kukie_test_queue_response( 200, [ 'message' => 'Settings updated.' ] );
		kukie_test_queue_response( 200, $this->settingsPayload() );

		$admin = new Kukie_Admin( Kukie_Plugin::instance() );
		$this->captureJson( fn () => $admin->ajax_save_a11y() );

		$stored = kukie_test_stored_settings();
		foreach ( array_keys( $stored ) as $key ) {
			$this->assertStringNotContainsString( 'a11y', $key, 'Widget settings live only on app.kukie.io.' );
		}
		$this->assertArrayNotHasKey( 'accessibility_widget', $stored );
		$this->assertArrayHasKey( 'config_version', $stored, 'Only the CDN cache-buster moves, like every other save.' );
	}

	public function test_a_plan_gate_403_becomes_a_structured_upgrade_error(): void {
		$this->seedConnectedInstall();
		$this->postA11y();
		set_transient( 'kukie_settings_cache', [ 'config_version' => 5 ], 600 );

		kukie_test_queue_response( 403, [
			'error'         => 'The accessibility widget is available on the Pro plan and above. Upgrade to enable it.',
			'message'       => 'The accessibility widget is available on the Pro plan and above. Upgrade to enable it.',
			'code'          => 'plan_upgrade_required',
			'feature'       => 'accessibility_widget',
			'required_plan' => 'Pro',
			'upgrade_url'   => 'https://app.kukie.io/billing',
		] );

		$admin    = new Kukie_Admin( Kukie_Plugin::instance() );
		$response = $this->captureJson( fn () => $admin->ajax_save_a11y() );

		$this->assertFalse( $response->ok );
		$this->assertSame( 'plan_upgrade_required', $response->data['code'] );
		$this->assertSame( 'Pro', $response->data['required_plan'] );
		$this->assertSame( 'https://app.kukie.io/billing', $response->data['upgrade_url'] );
		$this->assertStringContainsString( 'Pro plan', $response->message() );
		$this->assertFalse( get_transient( 'kukie_settings_cache' ), 'The cached (now stale) verdict is dropped so the next load re-checks the plan.' );
	}

	public function test_a_plain_403_is_still_an_ordinary_error(): void {
		// Only the structured plan-gate payload gets the CTA treatment; a
		// frozen-site style 403 keeps the plain message path.
		$this->seedConnectedInstall();
		$this->postA11y();

		kukie_test_queue_response( 403, [ 'error' => 'Site is frozen.', 'message' => 'Site is frozen.' ] );

		$admin    = new Kukie_Admin( Kukie_Plugin::instance() );
		$response = $this->captureJson( fn () => $admin->ajax_save_a11y() );

		$this->assertFalse( $response->ok );
		$this->assertSame( 'Site is frozen.', $response->message() );
		$this->assertArrayNotHasKey( 'code', $response->data );
	}

	public function test_a_fresh_settings_load_bypasses_the_cache(): void {
		// The page loads with fresh=1 so an upgrade on Kukie.io is seen at
		// once instead of after the 10-minute cache.
		$this->seedConnectedInstall();
		set_transient( 'kukie_settings_cache', [ 'config_version' => 1, 'stale' => true ], 600 );

		$admin = new Kukie_Admin( Kukie_Plugin::instance() );

		$_POST    = [];
		$response = $this->captureJson( fn () => $admin->ajax_get_settings() );
		$this->assertTrue( $response->data['stale'] ?? false, 'Without fresh=1 the cache is served.' );

		kukie_test_queue_response( 200, $this->settingsPayload( [ 'accessibility_widget' => [ 'available' => true ] ] ) );
		$_POST    = [ 'fresh' => '1' ];
		$response = $this->captureJson( fn () => $admin->ajax_get_settings() );
		$this->assertArrayNotHasKey( 'stale', $response->data );
		$this->assertTrue( $response->data['accessibility_widget']['available'] );
	}
}
