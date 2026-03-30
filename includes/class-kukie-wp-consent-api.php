<?php
/**
 * WP Consent API Integration
 *
 * Integrates Kukie cookie consent with the WP Consent API plugin.
 * Activates automatically when WP Consent API is installed.
 *
 * @package Kukie_Cookie_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Kukie_WP_Consent_API {

	/**
	 * Category mapping: Kukie categories -> WP Consent API categories
	 */
	private const CATEGORY_MAP = [
		'necessary'  => [ 'functional' ],
		'functional' => [ 'functional', 'preferences' ],
		'analytics'  => [ 'statistics', 'statistics-anonymous' ],
		'marketing'  => [ 'marketing' ],
	];

	/**
	 * Initialize hooks if WP Consent API is available.
	 */
	public function init(): void {
		// Only integrate if WP Consent API plugin is active
		if ( ! function_exists( 'wp_set_consent' ) ) {
			return;
		}

		// Register Kukie as the active consent management plugin
		$plugin = plugin_basename( KUKIE_PLUGIN_FILE );
		add_filter( "wp_consent_api_registered_{$plugin}", '__return_true' );

		// Set the consent type based on Kukie's consent model
		add_filter( 'wp_get_consent_type', [ $this, 'get_consent_type' ] );

		// Enqueue the JS bridge script
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_bridge_script' ] );
	}

	/**
	 * Return consent type for WP Consent API.
	 *
	 * Maps Kukie consent models to WP Consent API types.
	 * Default to 'optin' (safest - requires explicit consent).
	 */
	public function get_consent_type( string $consent_type ): string {
		return 'optin';
	}

	/**
	 * Enqueue the JS bridge that syncs Kukie consent -> WP Consent API.
	 */
	public function enqueue_bridge_script(): void {
		// Only enqueue if WP Consent API JS is available
		if ( ! wp_script_is( 'wp-consent-api', 'registered' ) && ! wp_script_is( 'wp-consent-api', 'enqueued' ) ) {
			return;
		}

		// Set consent type BEFORE wp-consent-api loads
		wp_add_inline_script( 'wp-consent-api', "window.wp_consent_type = 'optin';", 'before' );

		// Sync bridge AFTER wp-consent-api loads
		wp_add_inline_script( 'wp-consent-api', $this->get_bridge_js(), 'after' );
	}

	/**
	 * Generate the JS bridge code.
	 *
	 * Listens for Kukie consent events and syncs to WP Consent API.
	 */
	private function get_bridge_js(): string {
		$category_map_json = wp_json_encode( self::CATEGORY_MAP );

		return <<<JS
(function() {
	var kukieToWpMap = {$category_map_json};

	function syncConsentToWpApi() {
		if (typeof wp_set_consent !== 'function') return;

		var consentCookie = getCookieValue('_cc_consent');
		if (!consentCookie) {
			setAllCategories('deny');
			return;
		}

		try {
			var decoded = JSON.parse(atob(consentCookie));
			var accepted = decoded.c || [];

			var allowedWpCategories = {};
			for (var kukieCat in kukieToWpMap) {
				if (kukieToWpMap.hasOwnProperty(kukieCat)) {
					var wpCats = kukieToWpMap[kukieCat];
					var isAccepted = accepted.indexOf(kukieCat) !== -1;
					for (var i = 0; i < wpCats.length; i++) {
						if (isAccepted) {
							allowedWpCategories[wpCats[i]] = true;
						} else if (!(wpCats[i] in allowedWpCategories)) {
							allowedWpCategories[wpCats[i]] = false;
						}
					}
				}
			}

			for (var wpCat in allowedWpCategories) {
				if (allowedWpCategories.hasOwnProperty(wpCat)) {
					wp_set_consent(wpCat, allowedWpCategories[wpCat] ? 'allow' : 'deny');
				}
			}
		} catch (e) {
			setAllCategories('deny');
		}
	}

	function setAllCategories(value) {
		if (typeof wp_set_consent !== 'function') return;
		var allCats = ['functional', 'preferences', 'statistics', 'statistics-anonymous', 'marketing'];
		for (var i = 0; i < allCats.length; i++) {
			wp_set_consent(allCats[i], value);
		}
	}

	function getCookieValue(name) {
		var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
		return match ? decodeURIComponent(match[2]) : null;
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', syncConsentToWpApi);
	} else {
		syncConsentToWpApi();
	}

	document.addEventListener('cc:consent-updated', syncConsentToWpApi);
})();
JS;
	}
}
