<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Kukie_Encryption {

	/**
	 * Format marker for ciphertext written since 1.7.0. The legacy format
	 * (<= 1.6.3) stored base64( raw 16-byte IV . '::' . base64 ciphertext ),
	 * which is ambiguous when the random IV itself contains the 0x3a3a byte
	 * pair: explode() then splits inside the IV and decryption fails
	 * permanently for that install. The new format is
	 * 'v2:' . base64( raw IV . raw ciphertext ) with fixed-length IV
	 * extraction, so IV bytes can never be misparsed.
	 *
	 * ':' is not part of the base64 alphabet, so no legacy value can start
	 * with this prefix - the two formats are unambiguously distinguishable.
	 *
	 * @since 1.7.0
	 */
	private const FORMAT_PREFIX = 'v2:';

	private const IV_LENGTH = 16;

	// Key derivation must stay byte-identical between encrypt() and BOTH
	// decrypt paths - a divergence permanently breaks stored keys.
	private static function derive_key(): string {
		return hash( 'sha256', wp_salt( 'auth' ), true );
	}

	public static function encrypt( string $value ): string {
		$key = self::derive_key();
		$iv  = random_bytes( self::IV_LENGTH );

		$encrypted = openssl_encrypt( $value, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );

		if ( $encrypted === false ) {
			return '';
		}

		return self::FORMAT_PREFIX . base64_encode( $iv . $encrypted );
	}

	public static function decrypt( string $value ): string {
		if ( self::is_legacy( $value ) ) {
			return self::decrypt_legacy( $value );
		}

		$key  = self::derive_key();
		$data = base64_decode( substr( $value, strlen( self::FORMAT_PREFIX ) ), true );

		if ( $data === false || strlen( $data ) <= self::IV_LENGTH ) {
			return '';
		}

		$iv        = substr( $data, 0, self::IV_LENGTH );
		$encrypted = substr( $data, self::IV_LENGTH );

		$decrypted = openssl_decrypt( $encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );

		return $decrypted !== false ? $decrypted : '';
	}

	/**
	 * Whether a stored value is in the pre-1.7.0 '::'-delimited format.
	 * Callers can use this to opportunistically re-encrypt on a successful
	 * decrypt. Legacy decryption support should be kept for a release or two.
	 *
	 * @since 1.7.0
	 */
	public static function is_legacy( string $value ): bool {
		return ! str_starts_with( $value, self::FORMAT_PREFIX );
	}

	private static function decrypt_legacy( string $value ): string {
		$key  = self::derive_key();
		$data = base64_decode( $value );

		// The legacy layout is raw IV_LENGTH-byte IV . '::' . base64
		// ciphertext. Parse by FIXED OFFSET, never by delimiter search: a
		// random IV can itself contain the '::' byte pair (~0.02% of IVs),
		// which made explode() split inside the IV and fail decryption
		// permanently for those installs. Base64 ciphertext can never
		// contain ':', so the fixed-offset parse is fully deterministic.
		if ( $data === false || strlen( $data ) <= self::IV_LENGTH + 2 ) {
			return '';
		}

		if ( substr( $data, self::IV_LENGTH, 2 ) !== '::' ) {
			return '';
		}

		$iv        = substr( $data, 0, self::IV_LENGTH );
		$encrypted = substr( $data, self::IV_LENGTH + 2 );

		// Flag 0 (base64 ciphertext input) is deliberate and load-bearing:
		// the legacy writer base64-encoded the ciphertext separately.
		$decrypted = openssl_decrypt( $encrypted, 'aes-256-cbc', $key, 0, $iv );

		return $decrypted !== false ? $decrypted : '';
	}
}
