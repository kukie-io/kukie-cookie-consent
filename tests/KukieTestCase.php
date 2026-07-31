<?php

use PHPUnit\Framework\TestCase;

/**
 * Shared fixture: resets the stub stores AND the Kukie_Plugin singleton
 * between tests.
 *
 * Resetting the singleton matters more than it looks. Kukie_Plugin memoises
 * kukie_settings for the life of the request and its constructor runs
 * maybe_upgrade() (which writes the option), so a singleton leaking across
 * tests would carry a previous test's state into the next one and could make
 * an assertion pass against a memo rather than against the store.
 */
abstract class Kukie_Test_Case extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		kukie_test_reset();
		self::reset_plugin_singleton();
		$_POST = [];
	}

	protected function tearDown(): void {
		$_POST = [];
		self::reset_plugin_singleton();
		parent::tearDown();
	}

	private static function reset_plugin_singleton(): void {
		$instance = new ReflectionProperty( Kukie_Plugin::class, 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );
	}

	/**
	 * A connected install with a usable (encryptable/decryptable) API key.
	 *
	 * @param array $overrides Merged over the defaults.
	 */
	protected function seedConnectedInstall( array $overrides = [] ): void {
		kukie_test_seed_settings( array_merge( [
			'api_key_encrypted' => Kukie_Encryption::encrypt( str_repeat( 'a', 64 ) ),
			'api_key_valid'     => true,
			'site_key'          => 'site-key-original',
			'site_id'           => 7,
			'banner_enabled'    => true,
			'script_position'   => 'head',
			'plugin_version'    => KUKIE_VERSION,
		], $overrides ) );
	}

	/**
	 * The body GET/PUT /settings answers with. Only the keys the handlers read.
	 */
	protected function settingsPayload( array $overrides = [] ): array {
		return array_merge( [
			'config_version'    => 5,
			'banner_enabled'    => true,
			'auto_translate'    => true,
			'default_language'  => 'en',
			'enabled_languages' => [ 'en', 'de' ],
		], $overrides );
	}

	/**
	 * Run an AJAX handler and return its terminal wp_send_json_* payload.
	 * Every handler ends in one, so not throwing is itself a failure.
	 */
	protected function captureJson( callable $handler ): Kukie_Json_Response {
		try {
			$handler();
		} catch ( Kukie_Json_Response $response ) {
			return $response;
		}

		$this->fail( 'Handler returned without sending a JSON response.' );
	}
}
