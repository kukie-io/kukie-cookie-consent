<?php

/**
 * 1.9.0 feature lock: the Consent banner > Preferences modal tab.
 *
 * ajax_save_preferences_modal() coerces every value to what the server
 * accepts, forwards the block under `preferences_modal` with the optimistic
 * lock version, refuses an out-of-range width locally, maps the three-way
 * overlay choice to null / true / false, and only sends the title icon when
 * the form carried it (a plan that must keep branding sends none of it).
 * Nothing is mirrored into kukie_settings.
 */
final class PreferencesModalSaveTest extends Kukie_Test_Case {

	private function putBody(): array {
		foreach ( kukie_test_http_log() as $request ) {
			if ( ( $request['args']['method'] ?? '' ) === 'PUT' ) {
				return json_decode( (string) $request['args']['body'], true );
			}
		}

		$this->fail( 'No PUT /settings request was made.' );
	}

	public function test_a_full_form_is_coerced_and_forwarded_with_the_lock_version(): void {
		$this->seedConnectedInstall();
		$before = kukie_test_stored_settings();
		$_POST  = [
			'button_layout'      => 'stacked',
			'button_order'       => [ 'accept', 'reject', 'save' ],
			'max_width'          => '720',
			'show_policy_links'  => '0',
			'show_overlay'       => 'banner',
			'show_icon'          => '1',
			'logo_url'           => ' https://example.com/logo.svg ',
			'logo_max_width'     => '250',
			'logo_border_radius' => '-4',
			'config_version'     => '5',
		];

		kukie_test_queue_response( 200, [ 'message' => 'Settings updated.', 'config_version' => 6 ] );

		$admin    = new Kukie_Admin( Kukie_Plugin::instance() );
		$response = $this->captureJson( fn () => $admin->ajax_save_preferences_modal() );

		$this->assertTrue( $response->ok );
		$this->assertSame( 6, $response->data['config_version'] );
		$body = $this->putBody();
		$this->assertSame( 5, $body['config_version'] );
		$this->assertSame( [
			'button_layout'      => 'stacked',
			'button_order'       => [ 'accept', 'reject', 'save' ],
			'max_width'          => 720,
			'show_policy_links'  => false,
			'show_overlay'       => null,
			'show_icon'          => true,
			'logo_url'           => 'https://example.com/logo.svg',
			'logo_max_width'     => 100,
			'logo_border_radius' => 4,
		], $body['preferences_modal'] );
		// Only the CDN cache-buster moves (every settings save bumps it).
		$after = kukie_test_stored_settings();
		unset( $before['config_version'], $after['config_version'] );
		$this->assertSame( $before, $after, 'Nothing about the modal is mirrored locally.' );
	}

	public function test_overlay_choices_and_an_empty_width(): void {
		foreach ( [ 'on' => true, 'off' => false ] as $choice => $expected ) {
			kukie_test_reset();
			$this->seedConnectedInstall();
			$_POST = [ 'show_overlay' => $choice, 'max_width' => '', 'config_version' => '1' ];
			kukie_test_queue_response( 200, [ 'message' => 'Settings updated.', 'config_version' => 2 ] );
			$admin = new Kukie_Admin( Kukie_Plugin::instance() );
			$this->captureJson( fn () => $admin->ajax_save_preferences_modal() );
			$modal = $this->putBody()['preferences_modal'];
			$this->assertSame( $expected, $modal['show_overlay'] );
			$this->assertNull( $modal['max_width'], 'An empty width means the default 600px.' );
		}
	}

	public function test_an_out_of_range_width_is_refused_before_any_request(): void {
		$this->seedConnectedInstall();
		$_POST = [ 'max_width' => '200', 'config_version' => '1' ];

		$admin    = new Kukie_Admin( Kukie_Plugin::instance() );
		$response = $this->captureJson( fn () => $admin->ajax_save_preferences_modal() );

		$this->assertFalse( $response->ok );
		$this->assertSame( 'Enter a width from 320 to 1200 pixels, or leave it empty for 600 pixels.', $response->message() );
		$this->assertSame( [], kukie_test_http_log() );
	}

	public function test_a_broken_order_and_a_bad_layout_are_dropped_not_sent(): void {
		$this->seedConnectedInstall();
		$_POST = [ 'button_layout' => 'grid', 'button_order' => [ 'save', 'save', 'accept' ], 'config_version' => '1' ];
		kukie_test_queue_response( 200, [ 'message' => 'Settings updated.', 'config_version' => 2 ] );

		$admin = new Kukie_Admin( Kukie_Plugin::instance() );
		$this->captureJson( fn () => $admin->ajax_save_preferences_modal() );

		$modal = $this->putBody()['preferences_modal'];
		$this->assertArrayNotHasKey( 'button_layout', $modal );
		$this->assertArrayNotHasKey( 'button_order', $modal );
	}

	public function test_the_title_icon_is_not_sent_when_the_form_omits_it(): void {
		$this->seedConnectedInstall();
		$_POST = [ 'button_layout' => 'row', 'config_version' => '1' ];
		kukie_test_queue_response( 200, [ 'message' => 'Settings updated.', 'config_version' => 2 ] );

		$admin = new Kukie_Admin( Kukie_Plugin::instance() );
		$this->captureJson( fn () => $admin->ajax_save_preferences_modal() );

		$this->assertSame( [ 'button_layout' => 'row' ], $this->putBody()['preferences_modal'] );
	}

	public function test_a_javascript_logo_url_never_reaches_the_server(): void {
		$this->seedConnectedInstall();
		$_POST = [ 'show_icon' => '1', 'logo_url' => 'javascript:alert(1)', 'config_version' => '1' ];
		kukie_test_queue_response( 200, [ 'message' => 'Settings updated.', 'config_version' => 2 ] );

		$admin = new Kukie_Admin( Kukie_Plugin::instance() );
		$this->captureJson( fn () => $admin->ajax_save_preferences_modal() );

		$this->assertNull( $this->putBody()['preferences_modal']['logo_url'] );
	}

	public function test_the_tab_loads_fresh_settings_never_the_cached_payload(): void {
		// A payload cached before the service update that added the block
		// made the tab report "Couldn't load" on a real site; the tab must
		// bypass the 10-minute transient like the Accessibility widget page.
		$js = (string) file_get_contents( KUKIE_PLUGIN_DIR . 'assets/js/admin.js' );

		$this->assertMatchesRegularExpression(
			"/async function loadModalSettings\\([^)]*\\)\\s*\\{[^}]*kukieAjax\\(\\s*'kukie_get_settings',\\s*\\{\\s*fresh:\\s*'1'\\s*\\}\\s*\\)/s",
			$js
		);

		// And the PHP side really bypasses the transient on fresh=1.
		$this->seedConnectedInstall();
		set_transient( 'kukie_settings_cache', [ 'config_version' => 1 ], 600 );
		$_POST = [ 'fresh' => '1' ];
		kukie_test_queue_response( 200, $this->settingsPayload( [ 'preferences_modal' => [ 'button_layout' => 'row' ] ] ) );

		$admin    = new Kukie_Admin( Kukie_Plugin::instance() );
		$response = $this->captureJson( fn () => $admin->ajax_get_settings() );

		$this->assertSame( 'row', $response->data['preferences_modal']['button_layout'] );
	}
}
