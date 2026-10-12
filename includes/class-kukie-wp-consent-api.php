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

		// Declare the consent type: always 'optin' (see get_consent_type()).
		add_filter( 'wp_get_consent_type', [ $this, 'get_consent_type' ] );

		// Enqueue the JS bridge script
		// Must run after WP Consent API enqueues its script at PHP_INT_MAX - 100
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_bridge_script' ], PHP_INT_MAX - 50 );
	}

	/**
	 * Return consent type for WP Consent API: always 'optin', whatever the
	 * visitor's Kukie.io region rule (decided 1.10.0, KUK-QA-2026-1010).
	 *
	 * Why not the rule's own model (opt_out, notice_only ...):
	 *
	 * - This filter runs in PHP while the page is built, but Kukie.io places
	 *   the visitor in the browser, after the page loads (the banner's region
	 *   lookup), and a page cache serves one HTML to every region. PHP cannot
	 *   know the model.
	 * - WP Consent API does let a banner set window.wp_consent_type later and
	 *   dispatch wp_consent_type_defined. But 'optout' changes nothing while
	 *   the bridge below writes an explicit 'deny' for every category until
	 *   the visitor chooses, and dropping those writes would make
	 *   wp_has_consent() answer true before any choice. Google Site Kit's
	 *   Consent Mode script (checked in its October 2026 build) sends
	 *   gtag('consent', 'update') granted at page load for every category
	 *   wp_has_consent() allows, so the visitor would be granted while
	 *   Kukie's banner is still on screen: the pattern Kukie.io itself
	 *   stopped sending in v1.112.0 (Google's Consent Mode requirement that
	 *   no granted update goes out before the visitor acts).
	 *
	 * So consumers see 'optin' and 'deny' until the visitor's stored choice
	 * says otherwise. In opt-out regions that is stricter than the banner,
	 * never looser.
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
