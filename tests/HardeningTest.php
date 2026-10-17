<?php

/**
 * 1.11.0 hardening.
 *
 * The cache-plugin exclusion filters run on every public page: a value
 * another plugin put on the same filter (a string, null) must never become a
 * type error there. And the API key header must never follow a redirect.
 */
final class HardeningTest extends Kukie_Test_Case {

	public function test_exclusion_lists_accept_whatever_the_filter_carries(): void {
		$this->assertSame( [ 'cdn.kukie.io', 'app.kukie.io' ], Kukie_Script_Injector::exclude_hosts( null, [ 'cdn.kukie.io', 'app.kukie.io' ] ) );
		$this->assertSame( [ 'jquery.js', 'kukie.io' ], Kukie_Script_Injector::exclude_hosts( 'jquery.js', [ 'kukie.io' ] ) );
		$this->assertSame( [ 'a.js', 'kukie.io' ], Kukie_Script_Injector::exclude_hosts( [ 'a.js' ], [ 'kukie.io' ] ) );
	}

	public function test_kukie_hosts_are_added_once(): void {
		$this->assertSame( [ 'cdn.kukie.io(.*)' ], Kukie_Script_Injector::exclude_hosts( [ 'cdn.kukie.io(.*)' ], [ 'cdn.kukie.io(.*)', 'app.kukie.io(.*)' ] ) );
	}

	public function test_api_requests_never_follow_a_redirect(): void {
		kukie_test_queue_response( 200, [] );

		( new Kukie_Api_Client( str_repeat( 'a', 64 ) ) )->get( '/status' );

		$this->assertSame( 0, kukie_test_http_log()[0]['args']['redirection'] ?? null );
	}

	public function test_exclusion_lists_skip_entries_that_are_not_text(): void {
		$object = new stdClass();

		$this->assertSame( [ $object, 'kukie.io' ], Kukie_Script_Injector::exclude_hosts( [ $object ], [ 'kukie.io' ] ), 'An object entry is kept and skipped, never cast.' );
	}

	public function test_a_redirect_answer_carries_an_error_message(): void {
		kukie_test_queue_response( 302, [] );

		$response = ( new Kukie_Api_Client( str_repeat( 'a', 64 ) ) )->get( '/status' );

		$this->assertFalse( $response['success'] );
		$this->assertNotEmpty( $response['error'] );
	}

	public function test_resource_hints_only_where_the_banner_is_enqueued(): void {
		$this->seedConnectedInstall();
		$injector = new Kukie_Script_Injector( Kukie_Plugin::instance() );

		$this->assertSame( [], $injector->add_resource_hints( [], 'preconnect' ), 'No banner on this page (login, Customizer, hidden for this user): no hints.' );

		$injector->enqueue_banner_script();

		$this->assertSame( [ 'https://cdn.kukie.io' ], $injector->add_resource_hints( [], 'preconnect' ) );
		$this->assertSame( [ '//app.kukie.io' ], $injector->add_resource_hints( [], 'dns-prefetch' ) );
		$this->assertSame( [], $injector->add_resource_hints( [] ), 'A caller that passes one argument gets the list back, never an error.' );
		$this->assertSame( 'not-a-list', $injector->add_resource_hints( 'not-a-list', 'preconnect' ) );
	}
}
