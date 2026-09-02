<?php

/**
 * 1.8.0 feature lock: the Settings page's Connection card must follow
 * Kukie.io. Plan name, organisation and domain were stored once at connect
 * time, so a plan change or an organisation rename stayed invisible in
 * WordPress until a reconnect. /status now carries them and
 * ajax_get_status() mirrors them into the option (same discipline as the
 * banner_enabled mirror: only from a successful response, only when changed,
 * never into a disconnected install).
 */
final class ConnectionMirrorTest extends Kukie_Test_Case {

	private function statusPayload( array $overrides = [] ): array {
		return array_merge( [
			'plan'            => [ 'name' => 'Pro', 'trial' => false, 'trial_days_remaining' => null ],
			'consent_stats'   => [ 'today' => [ 'accept_all' => 0, 'reject_all' => 0, 'custom_consent' => 0 ], 'this_week' => [ 'total' => 0 ], 'this_month' => [ 'total' => 0 ] ],
			'last_scan'       => null,
			'banner_enabled'  => true,
			'script_verified' => true,
			'organisation'    => 'Pixadoro',
			'domain'          => 'obshti-uslovia.com',
		], $overrides );
	}

	public function test_a_status_fetch_refreshes_the_stored_connection_details(): void {
		$this->seedConnectedInstall( [ 'plan_name' => 'Free', 'organisation' => 'Old name', 'domain' => 'old.example' ] );

		kukie_test_queue_response( 200, $this->statusPayload() );

		$admin    = new Kukie_Admin( Kukie_Plugin::instance() );
		$response = $this->captureJson( fn () => $admin->ajax_get_status() );

		$this->assertTrue( $response->ok );

		$settings = kukie_test_stored_settings();
		$this->assertSame( 'Pro', $settings['plan_name'] );
		$this->assertSame( 'Pixadoro', $settings['organisation'] );
		$this->assertSame( 'obshti-uslovia.com', $settings['domain'] );
	}

	public function test_a_status_payload_without_the_keys_leaves_the_stored_values_alone(): void {
		// A pre-1.8.0 service answer has no organisation/domain keys.
		$this->seedConnectedInstall( [ 'plan_name' => 'Free', 'organisation' => 'Kept', 'domain' => 'kept.example' ] );

		$payload = $this->statusPayload();
		unset( $payload['organisation'], $payload['domain'] );
		kukie_test_queue_response( 200, $payload );

		$admin = new Kukie_Admin( Kukie_Plugin::instance() );
		$this->captureJson( fn () => $admin->ajax_get_status() );

		$settings = kukie_test_stored_settings();
		$this->assertSame( 'Pro', $settings['plan_name'], 'The plan name is in every status answer.' );
		$this->assertSame( 'Kept', $settings['organisation'] );
		$this->assertSame( 'kept.example', $settings['domain'] );
	}

	public function test_a_mirror_after_a_concurrent_disconnect_writes_nothing(): void {
		$this->seedConnectedInstall( [ 'plan_name' => 'Free' ] );

		kukie_test_queue_response( 200, $this->statusPayload() );

		$admin = new Kukie_Admin( Kukie_Plugin::instance() );

		// The disconnect lands while /status is in flight.
		delete_option( 'kukie_settings' );

		$this->captureJson( fn () => $admin->ajax_get_status() );

		$this->assertSame( [], kukie_test_stored_settings(), 'No ghost row from a mirror.' );
	}
}
