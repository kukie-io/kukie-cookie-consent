<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Kukie_Api_Client {

	private string $api_key;

	public function __construct( string $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * @return array{success: bool, data: array|null, error: string|null, status: int}
	 */
	public function request( string $method, string $endpoint, ?array $body = null ): array {
		$url = KUKIE_API_BASE . $endpoint;

		$args = [
			'method'    => strtoupper( $method ),
			'timeout'   => 15,
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

		$plugin = Kukie_Plugin::instance();

		if ( $status === 401 ) {
			$plugin->set_api_key_valid( false );
		} elseif ( $status >= 200 && $status < 300 && ! $plugin->is_api_key_valid() ) {
			$plugin->set_api_key_valid( true );
		}

		return [
			'success' => $status >= 200 && $status < 300,
			'data'    => $data,
			'error'   => $status >= 400 ? ( $data['error'] ?? 'API error.' ) : null,
			'status'  => $status,
		];
	}

	public function get( string $endpoint ): array {
		return $this->request( 'GET', $endpoint );
	}

	public function post( string $endpoint, ?array $body = null ): array {
		return $this->request( 'POST', $endpoint, $body );
	}

	public function put( string $endpoint, array $body ): array {
		return $this->request( 'PUT', $endpoint, $body );
	}
}
