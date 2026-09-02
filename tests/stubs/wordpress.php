<?php
/**
 * Minimal WordPress function stubs.
 *
 * Only what the classes under test actually call. This is deliberately not a
 * WordPress test install: the invariants this suite locks are pure functions
 * of the kukie_settings option and the HTTP outcome, so an in-memory option
 * store plus a canned-response queue is enough to exercise them, and it keeps
 * the suite runnable with nothing but PHP and PHPUnit.
 *
 * State lives in $GLOBALS and is reset per test by Kukie_Test_Case::setUp().
 */

// ---------------------------------------------------------------------------
// Test-side state + helpers
// ---------------------------------------------------------------------------

/**
 * Wipe every stub store. Called between tests.
 */
function kukie_test_reset(): void {
	$GLOBALS['kukie_test_options']    = [];
	$GLOBALS['kukie_test_transients'] = [];
	$GLOBALS['kukie_test_user_meta']  = [];
	$GLOBALS['kukie_test_http_queue'] = [];
	$GLOBALS['kukie_test_http_log']   = [];
	$GLOBALS['kukie_test_hooks']      = [];
	$GLOBALS['kukie_test_enqueued']   = [];
	$GLOBALS['kukie_test_redirects']  = [];
	$GLOBALS['kukie_test_filters']    = [];
	$GLOBALS['kukie_test_is_admin']   = false;
	$GLOBALS['kukie_test_can_manage'] = true;
	$GLOBALS['kukie_test_locale']     = 'en_US';
}

/**
 * Queue one canned HTTP response. Requests consume the queue in order.
 *
 * @param int   $status HTTP status code.
 * @param mixed $body   Decoded body; encoded to JSON for the client to parse.
 */
function kukie_test_queue_response( int $status, mixed $body = [] ): void {
	$GLOBALS['kukie_test_http_queue'][] = [ 'status' => $status, 'body' => wp_json_encode( $body ) ];
}

/**
 * Queue a response whose body is NOT JSON - an HTML error page from a proxy,
 * say. Distinct from kukie_test_queue_response(), which encodes what it is
 * given; a test needing this must not reach into the queue by hand.
 */
function kukie_test_queue_raw_response( int $status, string $body ): void {
	$GLOBALS['kukie_test_http_queue'][] = [ 'status' => $status, 'body' => $body ];
}

/**
 * Queue a transport-level failure (timeout, DNS failure) rather than a status.
 */
function kukie_test_queue_transport_error( string $message = 'cURL error 28: Operation timed out' ): void {
	$GLOBALS['kukie_test_http_queue'][] = [ 'error' => $message ];
}

/**
 * Every wp_remote_request() made so far, in order.
 *
 * @return array<int, array{url: string, args: array}>
 */
function kukie_test_http_log(): array {
	return $GLOBALS['kukie_test_http_log'];
}

/**
 * Whether a callback was registered on an action/filter hook.
 */
function kukie_test_hook_registered( string $hook ): bool {
	return ! empty( $GLOBALS['kukie_test_hooks'][ $hook ] );
}

/**
 * Seed the kukie_settings option directly, bypassing the plugin's writers.
 */
function kukie_test_seed_settings( array $settings ): void {
	$GLOBALS['kukie_test_options']['kukie_settings'] = $settings;
}

/**
 * Read the kukie_settings option directly, bypassing the plugin's readers and
 * its per-request memo - this is what actually landed in "the database".
 */
function kukie_test_stored_settings(): array {
	$stored = $GLOBALS['kukie_test_options']['kukie_settings'] ?? [];
	return is_array( $stored ) ? $stored : [];
}

/**
 * Thrown in place of the `exit` that follows wp_safe_redirect().
 */
class Kukie_Redirect extends RuntimeException {

	public function __construct( public readonly string $location, public readonly int $status ) {
		parent::__construct( 'wp_safe_redirect to ' . $location );
	}
}

/**
 * Thrown in place of wp_send_json_*'s die(), so an AJAX handler's terminal
 * response is observable from a test instead of killing the process.
 */
class Kukie_Json_Response extends RuntimeException {

	public function __construct(
		public readonly bool $ok,
		public readonly mixed $data,
		public readonly int $status
	) {
		parent::__construct( 'wp_send_json_' . ( $ok ? 'success' : 'error' ) );
	}

	/** Convenience for the common assertion on an error payload. */
	public function message(): string {
		return is_array( $this->data ) ? (string) ( $this->data['message'] ?? '' ) : '';
	}
}

// ---------------------------------------------------------------------------
// WordPress core: errors
// ---------------------------------------------------------------------------

class WP_Error {

	public function __construct( private string $message = '' ) {}

	public function get_error_message(): string {
		return $this->message;
	}
}

function is_wp_error( mixed $thing ): bool {
	return $thing instanceof WP_Error;
}

// ---------------------------------------------------------------------------
// WordPress core: options + transients
// ---------------------------------------------------------------------------

function get_option( string $option, mixed $default_value = false ): mixed {
	return $GLOBALS['kukie_test_options'][ $option ] ?? $default_value;
}

function update_option( string $option, mixed $value, mixed $autoload = null ): bool {
	$GLOBALS['kukie_test_options'][ $option ] = $value;
	return true;
}

function delete_option( string $option ): bool {
	unset( $GLOBALS['kukie_test_options'][ $option ] );
	return true;
}

function get_transient( string $transient ): mixed {
	return $GLOBALS['kukie_test_transients'][ $transient ] ?? false;
}

function set_transient( string $transient, mixed $value, int $expiration = 0 ): bool {
	$GLOBALS['kukie_test_transients'][ $transient ] = $value;
	return true;
}

function delete_transient( string $transient ): bool {
	unset( $GLOBALS['kukie_test_transients'][ $transient ] );
	return true;
}

// The plugin evicts these to defeat the alloptions snapshot. There is no such
// snapshot here (get_option reads the store directly), so eviction is a no-op
// that must still exist for refresh_settings() to run.
function wp_cache_delete( string $key, string $group = '' ): bool {
	return true;
}

function get_user_meta( int $user_id, string $key, bool $single = false ): mixed {
	return $GLOBALS['kukie_test_user_meta'][ $user_id ][ $key ] ?? ( $single ? '' : [] );
}

function update_user_meta( int $user_id, string $key, mixed $value ): bool {
	$GLOBALS['kukie_test_user_meta'][ $user_id ][ $key ] = $value;
	return true;
}

// ---------------------------------------------------------------------------
// WordPress core: HTTP
// ---------------------------------------------------------------------------

function wp_remote_request( string $url, array $args = [] ): mixed {
	$GLOBALS['kukie_test_http_log'][] = [ 'url' => $url, 'args' => $args ];

	if ( empty( $GLOBALS['kukie_test_http_queue'] ) ) {
		// Loud on purpose: an unqueued request means the test does not model
		// the path it thinks it does, and a silent WP_Error here would let
		// several of these assertions pass for the wrong reason.
		throw new RuntimeException( 'Unexpected HTTP request to ' . $url . ' - queue a response first.' );
	}

	$next = array_shift( $GLOBALS['kukie_test_http_queue'] );

	if ( isset( $next['error'] ) ) {
		return new WP_Error( $next['error'] );
	}

	return [ 'response' => [ 'code' => $next['status'] ], 'body' => $next['body'] ];
}

function wp_remote_retrieve_response_code( mixed $response ): int {
	return is_array( $response ) ? (int) ( $response['response']['code'] ?? 0 ) : 0;
}

function wp_remote_retrieve_body( mixed $response ): string {
	return is_array( $response ) ? (string) ( $response['body'] ?? '' ) : '';
}

function wp_json_encode( mixed $value, int $flags = 0 ): string|false {
	return json_encode( $value, $flags );
}

// ---------------------------------------------------------------------------
// WordPress core: hooks
// ---------------------------------------------------------------------------

function add_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
	$GLOBALS['kukie_test_hooks'][ $hook ][] = $callback;
	return true;
}

function add_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
	$GLOBALS['kukie_test_hooks'][ $hook ][] = $callback;
	return true;
}

/**
 * Runs callbacks a TEST registered via kukie_test_set_filter(); anything else
 * passes the value straight through. The plugin's own add_filter() calls are
 * recorded (above) rather than executed, so a hook the plugin registers does
 * not accidentally answer its own apply_filters().
 */
function apply_filters( string $hook, mixed $value, mixed ...$args ): mixed {
	if ( isset( $GLOBALS['kukie_test_filters'][ $hook ] ) ) {
		return ( $GLOBALS['kukie_test_filters'][ $hook ] )( $value, ...$args );
	}
	return $value;
}

function kukie_test_set_filter( string $hook, callable $callback ): void {
	$GLOBALS['kukie_test_filters'][ $hook ] = $callback;
}

// ---------------------------------------------------------------------------
// WordPress core: context, capabilities, admin
// ---------------------------------------------------------------------------

function is_admin(): bool {
	return (bool) $GLOBALS['kukie_test_is_admin'];
}

function is_customize_preview(): bool {
	return false;
}

function is_admin_bar_showing(): bool {
	return true;
}

function current_user_can( string $capability ): bool {
	return (bool) $GLOBALS['kukie_test_can_manage'];
}

function get_current_user_id(): int {
	return 1;
}

function get_current_screen(): mixed {
	return null;
}

function check_ajax_referer( string $action, string $query_arg = '', bool $stop = true ): bool {
	return true;
}

function wp_create_nonce( string $action = '' ): string {
	return 'test-nonce';
}

function wp_doing_ajax(): bool {
	return false;
}

/**
 * Handlers follow wp_safe_redirect() with a bare `exit`, which no stub can
 * intercept (it is a language construct). Throwing here is what keeps the
 * PHPUnit process alive; the redirect is recorded first so a test can assert
 * the destination either way.
 */
function wp_safe_redirect( string $location, int $status = 302 ): never {
	$GLOBALS['kukie_test_redirects'][] = $location;
	throw new Kukie_Redirect( $location, $status );
}

function admin_url( string $path = '' ): string {
	return 'https://example.test/wp-admin/' . ltrim( $path, '/' );
}

function plugin_basename( string $file ): string {
	return 'kukie-cookie-consent/kukie-cookie-consent.php';
}

function plugin_dir_path( string $file ): string {
	return dirname( $file ) . '/';
}

function plugin_dir_url( string $file ): string {
	return 'https://example.test/wp-content/plugins/kukie-cookie-consent/';
}

function wp_salt( string $scheme = 'auth' ): string {
	return 'kukie-test-salt-' . $scheme;
}

function wp_send_json_success( mixed $data = null, int $status = 200 ): never {
	throw new Kukie_Json_Response( true, $data, $status );
}

function wp_send_json_error( mixed $data = null, int $status = 200 ): never {
	throw new Kukie_Json_Response( false, $data, $status );
}

// ---------------------------------------------------------------------------
// WordPress core: assets + menus (recorded, never rendered)
// ---------------------------------------------------------------------------

function wp_enqueue_script( string $handle, string $src = '', array $deps = [], mixed $ver = false, mixed $args = [] ): void {
	$GLOBALS['kukie_test_enqueued'][ $handle ] = [ 'src' => $src, 'ver' => $ver, 'args' => $args ];
}

function wp_enqueue_style( string $handle, string $src = '', array $deps = [], mixed $ver = false ): void {}

function wp_localize_script( string $handle, string $name, array $data ): bool {
	return true;
}

function add_menu_page( ...$args ): string {
	return 'toplevel_page_kukie';
}

function add_submenu_page( ...$args ): string {
	return 'kukie_page_sub';
}

// ---------------------------------------------------------------------------
// WordPress core: sanitizers, escapers, i18n
// ---------------------------------------------------------------------------

function sanitize_text_field( mixed $str ): string {
	return is_scalar( $str ) ? trim( strip_tags( (string) $str ) ) : '';
}

function wp_unslash( mixed $value ): mixed {
	return is_string( $value ) ? stripslashes( $value ) : $value;
}

function absint( mixed $value ): int {
	return abs( (int) $value );
}

function sanitize_textarea_field( mixed $str ): string {
	return is_scalar( $str ) ? trim( strip_tags( (string) $str ) ) : '';
}

function sanitize_key( mixed $key ): string {
	$key = is_scalar( $key ) ? strtolower( (string) $key ) : '';
	return (string) preg_replace( '/[^a-z0-9_\-]/', '', $key );
}

/**
 * Only the two-argument form the plugin uses (array of args, base URL).
 */
function add_query_arg( mixed ...$args ): string {
	if ( count( $args ) === 2 && is_array( $args[0] ) ) {
		[ $params, $url ] = $args;
	} else {
		[ $key, $value, $url ] = array_pad( $args, 3, '' );
		$params              = [ $key => $value ];
	}
	$parts = explode( '?', (string) $url, 2 );
	$query = [];
	if ( isset( $parts[1] ) ) {
		parse_str( $parts[1], $query );
	}
	$query = array_merge( $query, $params );
	return $parts[0] . ( $query ? '?' . http_build_query( $query ) : '' );
}

function rest_sanitize_boolean( mixed $value ): bool {
	if ( is_string( $value ) ) {
		return ! in_array( strtolower( $value ), [ '', '0', 'false', 'no' ], true );
	}
	return (bool) $value;
}

function map_deep( mixed $value, callable $callback ): mixed {
	if ( is_array( $value ) ) {
		foreach ( $value as $key => $item ) {
			$value[ $key ] = map_deep( $item, $callback );
		}
		return $value;
	}
	return $callback( $value );
}

function current_time( string $type = 'mysql', bool $gmt = false ): string {
	return $type === 'c' ? '2026-01-01T00:00:00+00:00' : '2026-01-01 00:00:00';
}

function get_locale(): string {
	return (string) $GLOBALS['kukie_test_locale'];
}

function esc_html( string $text ): string {
	return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
}

function esc_attr( string $text ): string {
	return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
}

function esc_url( string $url ): string {
	return $url;
}

function esc_url_raw( string $url ): string {
	return $url;
}

function wp_kses( string $content, array $allowed ): string {
	return $content;
}

function __( string $text, string $domain = 'default' ): string {
	return $text;
}

function esc_html__( string $text, string $domain = 'default' ): string {
	return $text;
}

function esc_html_e( string $text, string $domain = 'default' ): void {
	echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
}

function esc_attr__( string $text, string $domain = 'default' ): string {
	return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
}

function esc_attr_e( string $text, string $domain = 'default' ): void {
	echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
}

function _e( string $text, string $domain = 'default' ): void {
	echo $text;
}
