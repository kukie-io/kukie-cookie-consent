<?php

/**
 * KUK-QA-2026-354 (high), second half - the one that reaches the public site.
 *
 * The banner is a compliance artefact; the API key is a management credential.
 * A key problem must degrade the dashboard connection and nothing else. The
 * only thing that may stop injection is the absence of a site_key (a real
 * disconnect deletes the whole option) or a deliberate manual placement.
 *
 * Observable: whether Kukie_Script_Injector::init() registered the
 * wp_enqueue_scripts hook. That is the single decision point - everything
 * downstream of it is unconditional.
 */
final class BannerInjectionGateTest extends Kukie_Test_Case {

	public function test_an_invalid_api_key_does_not_remove_the_banner(): void {
		$this->seedConnectedInstall( [ 'api_key_valid' => false ] );

		Kukie_Plugin::instance();

		$this->assertTrue(
			kukie_test_hook_registered( 'wp_enqueue_scripts' ),
			'A revoked or mistyped API key must never take the consent banner off a live site.'
		);
	}

	public function test_an_undecryptable_api_key_does_not_remove_the_banner(): void {
		// What a salt rotation or a cross-environment database clone leaves
		// behind: ciphertext that no longer decrypts, so is_connected() is
		// false while the site is still genuinely connected server-side.
		$this->seedConnectedInstall( [ 'api_key_encrypted' => 'v2:' . base64_encode( 'not-really-ciphertext' ) ] );

		$plugin = Kukie_Plugin::instance();

		$this->assertFalse( $plugin->is_connected(), 'fixture guard: the key must be unusable for this test to mean anything' );
		$this->assertTrue(
			kukie_test_hook_registered( 'wp_enqueue_scripts' ),
			'The banner must outlive a broken management credential.'
		);
	}

	public function test_a_disconnected_install_does_not_inject(): void {
		// ajax_disconnect() deletes the whole option; nothing is left to key on.
		Kukie_Plugin::instance();

		$this->assertFalse(
			kukie_test_hook_registered( 'wp_enqueue_scripts' ),
			'Without a site_key there is no bundle to load.'
		);
	}

	public function test_manual_placement_suppresses_automatic_injection(): void {
		$this->seedConnectedInstall( [ 'script_position' => 'manual' ] );

		Kukie_Plugin::instance();

		$this->assertFalse(
			kukie_test_hook_registered( 'wp_enqueue_scripts' ),
			'Manual placement means the user embeds the snippet themselves; injecting too would double-tag the page.'
		);
	}

	/**
	 * "Hide for administrators" (1.8.2): a page-builder safety valve. It is
	 * decided per request in enqueue_banner_script(), not in init(), because
	 * the current user is not known when init() registers the hook.
	 */
	public function test_hide_for_administrators_withholds_the_banner_from_an_administrator(): void {
		$this->seedConnectedInstall( [ 'disable_for_admins' => true ] );
		$GLOBALS['kukie_test_can_manage'] = true;

		$plugin = Kukie_Plugin::instance();
		( new Kukie_Script_Injector( $plugin ) )->enqueue_banner_script();

		$this->assertArrayNotHasKey( 'kukie-banner-script', $GLOBALS['kukie_test_enqueued'] );
	}

	public function test_hide_for_administrators_leaves_the_banner_for_visitors(): void {
		$this->seedConnectedInstall( [ 'disable_for_admins' => true ] );
		$GLOBALS['kukie_test_can_manage'] = false;

		$plugin = Kukie_Plugin::instance();
		( new Kukie_Script_Injector( $plugin ) )->enqueue_banner_script();

		$this->assertArrayHasKey( 'kukie-banner-script', $GLOBALS['kukie_test_enqueued'], 'The setting is about administrators only; visitors must always get the banner.' );
	}

	public function test_an_administrator_gets_the_banner_while_the_setting_is_off(): void {
		$this->seedConnectedInstall();
		$GLOBALS['kukie_test_can_manage'] = true;

		$plugin = Kukie_Plugin::instance();
		( new Kukie_Script_Injector( $plugin ) )->enqueue_banner_script();

		$this->assertArrayHasKey( 'kukie-banner-script', $GLOBALS['kukie_test_enqueued'], 'Default off: nothing changes for existing installs.' );
	}

	public function test_the_hide_filter_can_widen_the_decision(): void {
		$this->seedConnectedInstall();
		$GLOBALS['kukie_test_can_manage'] = false;
		kukie_test_set_filter( 'kukie_hide_banner_for_user', static fn ( bool $hidden, int $user_id ): bool => $user_id === 1 );

		$plugin = Kukie_Plugin::instance();
		( new Kukie_Script_Injector( $plugin ) )->enqueue_banner_script();

		$this->assertArrayNotHasKey( 'kukie-banner-script', $GLOBALS['kukie_test_enqueued'], 'A site may hide the banner for its builder-using editors through the filter.' );
	}

	public function test_the_enqueued_url_is_built_from_the_site_key_not_the_stored_embed_url(): void {
		// Legacy installs hold a stale app.kukie.io embed_url that no longer
		// serves anything, so injection must never trust it.
		$this->seedConnectedInstall( [
			'site_key'   => 'abc-123',
			'embed_url'  => 'https://app.kukie.io/c.js?stale=1',
		] );

		$plugin = Kukie_Plugin::instance();
		( new Kukie_Script_Injector( $plugin ) )->enqueue_banner_script();

		$this->assertSame(
			'https://cdn.kukie.io/s/abc-123/c.js',
			$GLOBALS['kukie_test_enqueued']['kukie-banner-script']['src']
		);
	}
}
