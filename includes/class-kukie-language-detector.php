<?php
/**
 * Language detector for WPML, Polylang, and WordPress core.
 *
 * Detection priority:
 *   1. Manual override from plugin settings (`force_language`)
 *   2. WPML (`wpml_current_language` filter)
 *   3. Polylang (`pll_current_language()` function)
 *   4. WordPress core (`get_locale()`)
 *
 * Result is normalized to Kukie's short-code convention:
 *   - lowercase
 *   - underscores replaced with hyphens
 *   - region stripped EXCEPT for zh-* (zh-cn, zh-tw preserved)
 *
 * The detected value is passed through the `kukie_script_lang` filter
 * so third parties can override it programmatically.
 *
 * @package Kukie
 * @since 1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Kukie_Language_Detector {

	/**
	 * Get the current page language as a Kukie-normalized short code,
	 * or empty string if detection yields nothing usable.
	 *
	 * The returned value is passed through the `kukie_script_lang` filter,
	 * which receives two arguments: the normalized language code and the
	 * source that produced it (`forced`, `wpml`, `polylang`, `wp_core`,
	 * or `none`).
	 *
	 * @return string e.g. "de", "fr", "zh-cn", or "" on failure
	 */
	public static function detect(): string {
		// 1. Manual override from plugin settings.
		$forced = self::get_forced_language();
		if ( ! empty( $forced ) && $forced !== 'auto' ) {
			$lang = self::normalize( $forced );
			return (string) apply_filters( 'kukie_script_lang', $lang, 'forced' );
		}

		// 2. WPML. The filter returns null when WPML is not active, so no
		//    function_exists check is needed.
		$wpml_lang = apply_filters( 'wpml_current_language', null );
		if ( ! empty( $wpml_lang ) && is_string( $wpml_lang ) ) {
			$lang = self::normalize( $wpml_lang );
			return (string) apply_filters( 'kukie_script_lang', $lang, 'wpml' );
		}

		// 3. Polylang. Must be function_exists-guarded so the plugin does
		//    not fatal on sites without Polylang.
		if ( function_exists( 'pll_current_language' ) ) {
			$pll_lang = pll_current_language( 'slug' );
			if ( ! empty( $pll_lang ) && is_string( $pll_lang ) ) {
				$lang = self::normalize( $pll_lang );
				return (string) apply_filters( 'kukie_script_lang', $lang, 'polylang' );
			}
		}

		// 4. WordPress core.
		$wp_locale = get_locale();
		if ( ! empty( $wp_locale ) ) {
			$lang = self::normalize( $wp_locale );
			return (string) apply_filters( 'kukie_script_lang', $lang, 'wp_core' );
		}

		return (string) apply_filters( 'kukie_script_lang', '', 'none' );
	}

	/**
	 * Normalize a locale code to Kukie's short-code convention.
	 *
	 *   de_DE    -> de
	 *   pt_BR    -> pt
	 *   en-GB    -> en
	 *   zh_CN    -> zh-cn
	 *   zh_TW    -> zh-tw
	 *   zh_Hant  -> zh-tw  (Traditional -> Taiwan variant)
	 *   zh_Hans  -> zh-cn  (Simplified  -> China  variant)
	 *   zh       -> zh-cn  (bare -> default to Simplified, largest speaker base)
	 *
	 * @param string $locale
	 * @return string
	 */
	public static function normalize( string $locale ): string {
		$locale = strtolower( trim( $locale ) );
		$locale = str_replace( '_', '-', $locale );

		if ( empty( $locale ) ) {
			return '';
		}

		// Handle Chinese script variants before generic rules.
		if ( $locale === 'zh-hant' || $locale === 'zh-hant-tw' || $locale === 'zh-tw' ) {
			return 'zh-tw';
		}
		if ( $locale === 'zh-hans' || $locale === 'zh-hans-cn' || $locale === 'zh-cn' ) {
			return 'zh-cn';
		}

		// Bare zh with no region: default to Simplified (largest speaker base).
		if ( $locale === 'zh' ) {
			return 'zh-cn';
		}

		// Preserve other zh-* region codes as-is.
		if ( strpos( $locale, 'zh-' ) === 0 ) {
			return $locale;
		}

		// All other locales: strip region.
		if ( strpos( $locale, '-' ) !== false ) {
			$parts = explode( '-', $locale );
			return $parts[0];
		}

		return $locale;
	}

	/**
	 * Read the admin override from plugin settings.
	 * Returns 'auto' when no override is set.
	 *
	 * The plugin stores settings under the `kukie_settings` option key
	 * (see Kukie_Plugin::get_settings()), so we read directly from that
	 * option without depending on the Kukie_Plugin instance being
	 * bootstrapped — this keeps the detector callable from anywhere,
	 * including unit tests.
	 *
	 * @return string
	 */
	private static function get_forced_language(): string {
		$settings = get_option( 'kukie_settings', array() );
		if ( ! is_array( $settings ) ) {
			return 'auto';
		}
		$forced = isset( $settings['force_language'] ) ? (string) $settings['force_language'] : 'auto';
		return $forced !== '' ? $forced : 'auto';
	}
}
