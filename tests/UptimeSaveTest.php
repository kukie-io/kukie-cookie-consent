<?php

/**
 * 1.9.0 feature lock (not a finding lock): the Uptime monitoring page.
 *
 * Properties of ajax_get_uptime() / ajax_save_uptime():
 * - the save is PRESENCE-BASED: only the fields the form posts reach the
 *   PUT, so the extra recipients and the webhook (managed on Kukie.io only)
 *   can never be sent, let alone reset, from WordPress;
 * - every value is coerced to what the server accepts before the PUT, and
 *   a URL that is not http(s) is refused locally with a readable message;
 * - nothing is mirrored into kukie_settings - Kukie.io is the only source of
 *   truth for these settings;
 * - a plan-gated 403 becomes the structured upgrade error the page renders,
 *   and a validation 422 reaches the page with the server's own message.
 */
final class UptimeSaveTest extends Kukie_Test_Case {

	/** The JSON body of the PUT /uptime request, decoded. */
	private function putBody(): array {
		foreach ( kukie_test_http_log() as $request ) {
			if ( ( $request['args']['method'] ?? '' ) === 'PUT' ) {
				$this->assertStringEndsWith( '/uptime', $request['url'] );
				return json_decode( (string) $request['args']['body'], true );
			}
		}

		$this->fail( 'No PUT /uptime request was made.' );
	}

	private function uptimePayload( array $overrides = [] ): array {
		return array_merge( [
			'available' => true,
			'settings'  => [ 'enabled' => true, 'url' => 'https://example.com' ],
		], $overrides );
	}

	public function test_a_full_form_is_coerced_and_forwarded(): void {
		$this->seedConnectedInstall();
		$before = kukie_test_stored_settings();
		$_POST  = [
			'enabled'                => '1',
			'url'                    => ' https://www.example.com/shop ',
			'check_interval_seconds' => '900',
			'notify_email'           => '0',
			'report_enabled'         => 'true',
		];

		kukie_test_queue_response( 200, $this->uptimePayload() );

		$admin    = new Kukie_Admin( Kukie_Plugin::instance() );
		$response = $this->captureJson( fn () => $admin->ajax_save_uptime() );

		$this->assertTrue( $response->ok );
		$this->assertSame( [
			'enabled'                => true,
			'notify_email'           => false,
			'report_enabled'         => true,
			'url'                    => 'https://www.example.com/shop',
			'check_interval_seconds' => 900,
		], $this->putBody() );
		$this->assertTrue( $response->data['uptime']['available'], 'The server payload rides back so the page re-renders without a second request.' );
		$this->assertSame( $before, kukie_test_stored_settings(), 'Nothing is mirrored locally.' );
	}

	public function test_only_the_posted_fields_are_sent(): void {
		$this->seedConnectedInstall();
		$_POST = [ 'enabled' => '0' ];

		kukie_test_queue_response( 200, $this->uptimePayload() );

		$admin = new Kukie_Admin( Kukie_Plugin::instance() );
		$this->captureJson( fn () => $admin->ajax_save_uptime() );

		$this->assertSame( [ 'enabled' => false ], $this->putBody() );
	}

	public function test_recipients_and_the_webhook_can_never_be_sent_from_wordpress(): void {
		$this->seedConnectedInstall();
		$_POST = [
			'enabled'         => '1',
			'alert_emails'    => [ 'attacker@example.net' ],
			'webhook_enabled' => '1',
			'webhook_url'     => 'https://attacker.example.net/hook',
		];

		kukie_test_queue_response( 200, $this->uptimePayload() );

		$admin = new Kukie_Admin( Kukie_Plugin::instance() );
		$this->captureJson( fn () => $admin->ajax_save_uptime() );

		$this->assertSame( [ 'enabled' => true ], $this->putBody() );
	}

	public function test_an_off_list_interval_is_dropped_and_an_empty_one_means_the_plans_cadence(): void {
		$this->seedConnectedInstall();
		$_POST = [ 'check_interval_seconds' => '7' ];
		kukie_test_queue_response( 200, $this->uptimePayload() );
		$admin = new Kukie_Admin( Kukie_Plugin::instance() );
		$this->captureJson( fn () => $admin->ajax_save_uptime() );
		$this->assertSame( [], $this->putBody(), 'A cadence the service does not offer is never forwarded.' );

		kukie_test_reset();
		$this->seedConnectedInstall();
		$_POST = [ 'check_interval_seconds' => '' ];
		kukie_test_queue_response( 200, $this->uptimePayload() );
		$admin = new Kukie_Admin( Kukie_Plugin::instance() );
		$this->captureJson( fn () => $admin->ajax_save_uptime() );
		$this->assertSame( [ 'check_interval_seconds' => null ], $this->putBody() );
	}

	public function test_a_url_that_is_not_http_is_refused_before_any_request(): void {
		$this->seedConnectedInstall();
		$_POST = [ 'url' => 'javascript:alert(1)' ];

		$admin    = new Kukie_Admin( Kukie_Plugin::instance() );
		$response = $this->captureJson( fn () => $admin->ajax_save_uptime() );

		$this->assertFalse( $response->ok );
		$this->assertSame( 'Enter a full web address that starts with https:// or http://.', $response->message() );
		$this->assertSame( [], kukie_test_http_log() );
	}

	public function test_the_plan_gate_becomes_the_structured_upgrade_error(): void {
		$this->seedConnectedInstall();
		$_POST = [ 'enabled' => '1' ];

		kukie_test_queue_response( 403, [
			'error'         => 'Uptime monitoring is available on the Agency plan and above. Upgrade to enable it.',
			'message'       => 'Uptime monitoring is available on the Agency plan and above. Upgrade to enable it.',
			'code'          => 'plan_upgrade_required',
			'feature'       => 'uptime_monitoring',
			'required_plan' => 'Agency',
			'upgrade_url'   => 'https://app.kukie.io/billing',
		] );

		$admin    = new Kukie_Admin( Kukie_Plugin::instance() );
		$response = $this->captureJson( fn () => $admin->ajax_save_uptime() );

		$this->assertFalse( $response->ok );
		$this->assertSame( 'plan_upgrade_required', $response->data['code'] );
		$this->assertSame( 'uptime_monitoring', $response->data['feature'] );
		$this->assertSame( 'Agency', $response->data['required_plan'] );
		$this->assertSame( 'https://app.kukie.io/billing', $response->data['upgrade_url'] );
	}

	public function test_an_upgrade_url_that_is_not_https_is_replaced(): void {
		$this->seedConnectedInstall();
		$_POST = [ 'enabled' => '1' ];

		kukie_test_queue_response( 403, [
			'message'     => 'Uptime monitoring is not included in your plan.',
			'code'        => 'plan_upgrade_required',
			'upgrade_url' => 'http://evil.example.net/billing',
		] );

		$admin    = new Kukie_Admin( Kukie_Plugin::instance() );
		$response = $this->captureJson( fn () => $admin->ajax_save_uptime() );

		$this->assertSame( 'https://app.kukie.io/billing', $response->data['upgrade_url'] );
	}

	public function test_a_validation_error_reaches_the_page_verbatim(): void {
		$this->seedConnectedInstall();
		$_POST = [ 'url' => 'https://elsewhere.example.org/' ];

		kukie_test_queue_response( 422, [
			'message' => 'The URL must point at example.com (or www.example.com).',
			'errors'  => [ 'url' => [ 'The URL must point at example.com (or www.example.com).' ] ],
		] );

		$admin    = new Kukie_Admin( Kukie_Plugin::instance() );
		$response = $this->captureJson( fn () => $admin->ajax_save_uptime() );

		$this->assertSame( 'The URL must point at example.com (or www.example.com).', $response->message() );
	}

	public function test_the_page_load_is_never_cached(): void {
		$this->seedConnectedInstall();
		kukie_test_queue_response( 200, $this->uptimePayload() );
		kukie_test_queue_response( 200, $this->uptimePayload( [ 'available' => false ] ) );

		$admin = new Kukie_Admin( Kukie_Plugin::instance() );
		$first = $this->captureJson( fn () => $admin->ajax_get_uptime() );
		$this->assertTrue( $first->data['available'] );

		// An upgrade or downgrade on Kukie.io shows on the very next load.
		$second = $this->captureJson( fn () => $admin->ajax_get_uptime() );
		$this->assertFalse( $second->data['available'] );
		$this->assertCount( 2, kukie_test_http_log() );
	}

	public function test_an_older_service_without_the_endpoint_gets_a_readable_message(): void {
		$this->seedConnectedInstall();
		kukie_test_queue_response( 404, [ 'message' => 'Not Found' ] );

		$admin    = new Kukie_Admin( Kukie_Plugin::instance() );
		$response = $this->captureJson( fn () => $admin->ajax_get_uptime() );

		$this->assertFalse( $response->ok );
		$this->assertSame( "Couldn't load uptime monitoring from Kukie.io. Try again in a few minutes.", $response->message() );
	}

	public function test_scan_status_is_uncached_and_a_finished_scan_drops_the_dashboard_cache(): void {
		$this->seedConnectedInstall();
		set_transient( 'kukie_dashboard_data', [ 'last_scan' => [ 'status' => 'running' ] ], 300 );

		kukie_test_queue_response( 200, [ 'status' => 'running', 'pages_scanned' => 3, 'total_pages' => 40, 'cookies_found' => 9 ] );
		$admin   = new Kukie_Admin( Kukie_Plugin::instance() );
		$running = $this->captureJson( fn () => $admin->ajax_scan_status() );
		$this->assertSame( 3, $running->data['pages_scanned'] );
		$this->assertSame( 40, $running->data['total_pages'] );
		$this->assertIsArray( get_transient( 'kukie_dashboard_data' ), 'A running scan keeps the dashboard cache.' );

		kukie_test_queue_response( 200, [ 'status' => 'completed', 'pages_scanned' => 40, 'total_pages' => 40, 'cookies_found' => 21 ] );
		$done = $this->captureJson( fn () => $admin->ajax_scan_status() );
		$this->assertSame( 'completed', $done->data['status'] );
		$this->assertFalse( get_transient( 'kukie_dashboard_data' ), 'A finished scan drops the cache so /status carries the result.' );
	}

	public function test_a_settings_save_uses_the_version_the_put_returned_and_skips_the_refresh(): void {
		$this->seedConnectedInstall();
		$_POST = [ 'gcm_v2_enabled' => '1', 'config_version' => '5' ];

		kukie_test_queue_response( 200, [ 'message' => 'Settings updated.', 'config_version' => 6 ] );

		$admin    = new Kukie_Admin( Kukie_Plugin::instance() );
		$response = $this->captureJson( fn () => $admin->ajax_save_gcm() );

		$this->assertSame( 6, $response->data['config_version'] );
		$this->assertCount( 1, kukie_test_http_log(), 'One request per save since the 1 October 2026 service update.' );
	}
}
