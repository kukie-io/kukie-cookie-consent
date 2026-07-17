<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Kukie_Api_Client {

	private string $api_key;

	// Whether this client was built from the STORED key. Only a trusted
	// client may mutate the global api_key_valid option: a candidate key
	// typed into the connect form must neither poison trust on a 401 nor
	// grant it on a 2xx (KUK-QA-2026-354). Defaults to untrusted so any
	// future ad-hoc client is safe by default; Kukie_Plugin::get_api_client()
	// passes true because it builds from get_api_key().
	private bool $trusted;

	public function __construct( string $api_key, bool $trusted = false ) {
		$this->api_key = $api_key;
		$this->trusted = $trusted;
	}

	/**
	 * @param int $timeout Request timeout in seconds. The default suits the
	 *                     fast endpoints; slow ones (e.g. /verify, where the
	 *                     server may probe up to 3 URLs at up to 15s each)
	 *                     must pass a value exceeding the server worst case.
	 * @return array{success: bool, data: array|null, error: string|null, status: int}
	 */
	public function request( string $method, string $endpoint, ?array $body = null, int $timeout = 15 ): array {
		$url = KUKIE_API_BASE . $endpoint;

		$args = [
			'method'    => strtoupper( $method ),
			'timeout'   => $timeout,
			'sslverify' => true,
			'headers'   => [
				'X-Kukie-Api-Key' => $this->api_key,
				'Content-Type'    => 'application/json',
				'Accept'          => 'application/json',
			],
		];

		if ( $body !== null ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'data'    => null,
				'error'   => $response->get_error_message(),
				'status'  => 0,
			];
		}

		$status = wp_remote_retrieve_response_code( $response );
		$data   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $this->trusted ) {
			$plugin = Kukie_Plugin::instance();

			if ( $status === 401 ) {
				$plugin->set_api_key_valid( false );
			} elseif ( $status >= 200 && $status < 300 && ! $plugin->is_api_key_valid() ) {
				$plugin->set_api_key_valid( true );
			}
		}

		return [
			'success' => $status >= 200 && $status < 300,
			'data'    => $data,
			'error'   => $status >= 400 ? $this->extract_error_message( $data ) : null,
			'status'  => $status,
		];
	}

	/**
	 * Best human-readable error from an error-status response body. The
	 * server's plugin middleware speaks a dual envelope (error + message),
	 * but Laravel-native responses (throttle 429, validation 422) carry
	 * only `message` - fall back to it so callers surface the real reason
	 * instead of a generic 'API error.'. Only a non-empty string counts:
	 * a non-JSON body (e.g. an HTML 502 from a proxy) decodes to null, and
	 * Laravel validation bodies can nest `message` as an array.
	 */
	private function extract_error_message( mixed $data ): string {
		if ( is_array( $data ) ) {
			foreach ( [ 'error', 'message' ] as $key ) {
				if ( isset( $data[ $key ] ) && is_string( $data[ $key ] ) && $data[ $key ] !== '' ) {
					return $data[ $key ];
				}
			}
		}

		return __( 'API error.', 'kukie-cookie-consent' );
	}

	public function get( string $endpoint ): array {
		return $this->request( 'GET', $endpoint );
	}

	public function post( string $endpoint, ?array $body = null, int $timeout = 15 ): array {
		return $this->request( 'POST', $endpoint, $body, $timeout );
	}

	public function put( string $endpoint, array $body ): array {
		return $this->request( 'PUT', $endpoint, $body );
	}
}
