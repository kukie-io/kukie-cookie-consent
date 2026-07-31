<?php

/**
 * KUK-QA-2026-354 (high), first half: which HTTP outcomes may write the global
 * api_key_valid option, and which client is allowed to write it at all.
 *
 * The rule: only a client built from the STORED key (trusted) may change
 * stored trust, and only an authoritative 401 may revoke it. A candidate key
 * typed into the connect form, a 5xx, and a transport timeout must all leave
 * stored trust exactly as they found it.
 *
 * Every assertion reads kukie_test_stored_settings() rather than the plugin's
 * own getter, so a passing test means the value in the option store is right,
 * not merely that a memo agrees with itself.
 */
final class ApiKeyTrustTest extends Kukie_Test_Case {

	public function test_a_candidate_key_rejected_with_401_does_not_revoke_stored_trust(): void {
		$this->seedConnectedInstall();
		Kukie_Plugin::instance();

		kukie_test_queue_response( 401, [ 'error' => 'Invalid API key.' ] );

		// Exactly what ajax_connect() builds: no trusted flag.
		( new Kukie_Api_Client( str_repeat( 'z', 64 ) ) )->post( '/connect' );

		$this->assertTrue(
			kukie_test_stored_settings()['api_key_valid'],
			'A typo in the connect form must not revoke the trust of the stored, working key.'
		);
	}

	public function test_a_candidate_key_accepted_with_200_does_not_grant_stored_trust(): void {
		$this->seedConnectedInstall( [ 'api_key_valid' => false ] );
		Kukie_Plugin::instance();

		kukie_test_queue_response( 200, [ 'site_key' => 'other' ] );

		( new Kukie_Api_Client( str_repeat( 'z', 64 ) ) )->post( '/connect' );

		$this->assertFalse(
			kukie_test_stored_settings()['api_key_valid'],
			'A different key succeeding says nothing about the stored key; trust must not be granted on its behalf.'
		);
	}

	public function test_the_stored_key_rejected_with_401_does_revoke_trust(): void {
		$this->seedConnectedInstall();
		$plugin = Kukie_Plugin::instance();

		kukie_test_queue_response( 401, [ 'error' => 'Invalid API key.' ] );

		$plugin->get_api_client()->get( '/status' );

		$this->assertFalse(
			kukie_test_stored_settings()['api_key_valid'],
			'An authoritative rejection of the stored key must still revoke trust - the fix must not disable the mechanism.'
		);
	}

	public function test_a_server_error_does_not_revoke_trust(): void {
		$this->seedConnectedInstall();
		$plugin = Kukie_Plugin::instance();

		kukie_test_queue_response( 503, [ 'message' => 'Service Unavailable' ] );

		$plugin->get_api_client()->get( '/status' );

		$this->assertTrue(
			kukie_test_stored_settings()['api_key_valid'],
			'A 5xx means the server could not answer, not that the key is bad.'
		);
	}

	public function test_a_transport_timeout_does_not_revoke_trust(): void {
		$this->seedConnectedInstall();
		$plugin = Kukie_Plugin::instance();

		kukie_test_queue_transport_error();

		$result = $plugin->get_api_client()->get( '/status' );

		$this->assertSame( 0, $result['status'] );
		$this->assertTrue(
			kukie_test_stored_settings()['api_key_valid'],
			'We could not reach the server, which is not the same answer as "the key was rejected".'
		);
	}

	public function test_a_401_landing_after_a_concurrent_disconnect_writes_nothing(): void {
		$this->seedConnectedInstall();
		$plugin = Kukie_Plugin::instance();

		// The round trip is exactly the window another request can disconnect
		// in. Model it: the option is gone by the time the response lands.
		kukie_test_queue_response( 401, [ 'error' => 'Invalid API key.' ] );
		delete_option( 'kukie_settings' );

		$plugin->get_api_client()->get( '/status' );

		$this->assertSame(
			[],
			kukie_test_stored_settings(),
			'A disconnected install must stay disconnected; writing trust state here resurrects it as a keyless ghost row.'
		);
	}
}
