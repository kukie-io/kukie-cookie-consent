<?php

/**
 * KUK-QA-2026-386: a reconnect must not silently undo a deliberate script
 * placement.
 *
 * This matters because the 1.7.0 salt-rotation and invalid-key notices funnel
 * ALREADY-CONNECTED installs back through the connect form, so "reconnect" is
 * a routine repair, not a rare event. Resetting placement to head there
 * resumes automatic injection on a site whose owner chose to embed the
 * snippet by hand.
 *
 * The narrowing that makes this safe: placement survives only a SAME-SITE
 * reconnect. Pointing the install at a different Kukie site must reset to
 * head, because the hand-placed snippet still names the old site's bundle.
 */
final class ConnectPlacementTest extends Kukie_Test_Case {

	/**
	 * @return array The stored settings after the connect handler ran.
	 */
	private function connectTo( string $new_site_key ): array {
		$_POST['api_key'] = str_repeat( 'b', 64 );

		kukie_test_queue_response( 200, [
			'site_key'       => $new_site_key,
			'site_id'        => 7,
			'domain'         => 'example.test',
			'organisation'   => 'Example Org',
			'plan'           => [ 'name' => 'Pro' ],
			'embed_url'      => 'https://cdn.kukie.io/s/' . $new_site_key . '/c.js',
			'dashboard_url'  => 'https://app.kukie.io',
			'banner_enabled' => true,
		] );

		$admin    = new Kukie_Admin( Kukie_Plugin::instance() );
		$response = $this->captureJson( fn () => $admin->ajax_connect() );

		$this->assertTrue( $response->ok, 'fixture guard: the connect itself must have succeeded' );

		return kukie_test_stored_settings();
	}

	public function test_a_same_site_reconnect_preserves_manual_placement(): void {
		$this->seedConnectedInstall( [ 'site_key' => 'site-a', 'script_position' => 'manual' ] );

		$settings = $this->connectTo( 'site-a' );

		$this->assertSame( 'manual', $settings['script_position'] );
	}

	public function test_a_same_site_reconnect_preserves_body_placement(): void {
		$this->seedConnectedInstall( [ 'site_key' => 'site-a', 'script_position' => 'body' ] );

		$settings = $this->connectTo( 'site-a' );

		$this->assertSame( 'body', $settings['script_position'] );
	}

	public function test_connecting_to_a_different_site_resets_placement_to_head(): void {
		$this->seedConnectedInstall( [ 'site_key' => 'site-a', 'script_position' => 'manual' ] );

		$settings = $this->connectTo( 'site-b' );

		$this->assertSame(
			'head',
			$settings['script_position'],
			'The hand-placed snippet still points at site-a, so keeping manual would leave the new site with no banner at all.'
		);
	}

	public function test_a_fresh_connect_defaults_to_head(): void {
		$settings = $this->connectTo( 'site-a' );

		$this->assertSame( 'head', $settings['script_position'] );
	}

	public function test_a_corrupted_stored_placement_cannot_survive_a_reconnect(): void {
		$this->seedConnectedInstall( [ 'site_key' => 'site-a', 'script_position' => 'somewhere-else' ] );

		$settings = $this->connectTo( 'site-a' );

		$this->assertSame( 'head', $settings['script_position'] );
	}
}
