<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Kukie_Script_Injector {

	private Kukie_Plugin $plugin;

	public function __construct( Kukie_Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	public function init(): void {
		// The front-end banner needs only the site_key - never gate it on the
		// API key. The API key is a MANAGEMENT credential (stats, scans,
		// settings sync); the CDN bundle at cdn.kukie.io/s/{site_key}/c.js
		// serves regardless of its state, so an invalid or undecryptable key
		// (salt rotation, revoked key) must degrade the dashboard connection,
		// never remove a live consent banner. A disconnect deletes the whole
		// kukie_settings option, so this site_key gate is what stops injection.
		if ( empty( $this->plugin->get_option( 'site_key' ) ) ) {
			return;
		}

		$position = $this->plugin->get_option( 'script_position', 'head' );

		if ( $position === 'manual' ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_banner_script' ] );
		add_filter( 'script_loader_tag', [ $this, 'add_compatibility_attributes' ], 10, 2 );
		add_filter( 'wp_resource_hints', [ $this, 'add_resource_hints' ], 10, 2 );

		// Admin bar indicator
		add_action( 'admin_bar_menu', [ $this, 'admin_bar_status' ], 999 );
	}

	/**
	 * Register cache/minification exclusion filters for popular WordPress caching plugins.
	 *
	 * The banner script is already optimized and minified. Further processing by caching
	 * plugins can truncate or corrupt it, breaking consent management entirely.
	 *
	 * Programmatically excludes cdn.kukie.io and app.kukie.io from:
	 * - WP Rocket: Minify JS (rocket_exclude_js)
	 * - WP Rocket: External JS minify (rocket_minify_excluded_external_js)
	 * - WP Rocket: Delay JavaScript execution (rocket_delay_js_exclusions)
	 * - WP Rocket: Load JavaScript deferred (rocket_exclude_defer_js) [since 1.5.0]
	 *
	 * These exclusions are applied at runtime per request via each caching plugin's
	 * own filter hooks, so they take effect without writing to stored settings and
	 * without requiring the user to touch any settings UI.
	 *
	 * Must be called early and unconditionally (even before API key is configured),
	 * because caching plugins scan all enqueued scripts regardless.
	 *
	 * @since 1.0.0
	 * @since 1.5.0 Added rocket_exclude_defer_js filter.
	 */
	public static function register_cache_exclusions(): void {
		// Every callback accepts whatever the filter carries: another plugin
		// on the same filter may hand over a string or null instead of the
		// documented array, and a type error here would break every page.

		// --- Autoptimize (a comma-separated string) ---
		add_filter( 'autoptimize_filter_js_exclude', static function ( $exclude ) {
			$kukie = 'kukie.io,c.js,kukie-cookie';
			if ( $exclude === null || $exclude === '' || $exclude === false ) {
				return $kukie;
			}
			// Another plugin's non-string value is passed on untouched.
			return is_string( $exclude ) ? $exclude . ',' . $kukie : $exclude;
		} );

		// --- WP Rocket ---
		add_filter( 'rocket_exclude_js', static function ( $excluded ) {
			return self::exclude_hosts( $excluded, [ 'cdn.kukie.io(.*)', 'app.kukie.io(.*)' ] );
		} );
		add_filter( 'rocket_delay_js_exclusions', static function ( $excluded ) {
			return self::exclude_hosts( $excluded, [ 'cdn.kukie.io', 'app.kukie.io' ] );
		} );
		add_filter( 'rocket_minify_excluded_external_js', static function ( $excluded ) {
			return self::exclude_hosts( $excluded, [ 'cdn.kukie.io', 'app.kukie.io' ] );
		} );
		// WP Rocket: exclude from Load JavaScript deferred (Defer JS)
		add_filter( 'rocket_exclude_defer_js', static function ( $excluded ) {
			return self::exclude_hosts( $excluded, [ 'cdn.kukie.io', 'app.kukie.io' ] );
		} );

		// --- WP Fastest Cache ---
		add_filter( 'wpfc_minify_js_exclude', static function ( $exclude ) {
			return self::exclude_hosts( $exclude, [ 'kukie.io' ] );
		} );

		// --- LiteSpeed Cache ---
		add_filter( 'litespeed_optimize_js_excludes', static function ( $excludes ) {
			return self::exclude_hosts( $excludes, [ 'cdn.kukie.io', 'app.kukie.io' ] );
		} );

		// --- W3 Total Cache ---
		add_filter( 'w3tc_minify_js_do_tag_minification', static function ( $do_minify, $script_tag = '', $file = '' ) {
			$file = is_string( $file ) ? $file : '';
			if ( str_contains( $file, 'kukie.io' ) || str_contains( $file, 'kukie-cookie' ) ) {
				return false;
			}
			return $do_minify;
		}, 10, 3 );

		// --- SG Optimizer ---
		add_filter( 'sgo_js_minify_exclude', static function ( $exclude ) {
			return self::exclude_hosts( $exclude, [ 'kukie.io' ] );
		} );
		add_filter( 'sgo_javascript_combine_exclude', static function ( $exclude ) {
			return self::exclude_hosts( $exclude, [ 'kukie.io' ] );
		} );
	}

	/**
	 * Add Kukie's hosts to a cache plugin's exclusion list, once. A value
	 * that is not an array (null, or a string another plugin returned) is
	 * kept as a list entry rather than dropped or fatal.
	 *
	 * @since 1.11.0
	 * @param mixed    $list  The filter's current value.
	 * @param string[] $hosts Entries to add when no entry names kukie.io yet.
	 * @return array
	 */
	public static function exclude_hosts( mixed $list, array $hosts ): array {
		if ( ! is_array( $list ) ) {
			$list = is_string( $list ) && $list !== '' ? [ $list ] : [];
		}

		if ( ! kukie_array_contains_domain( $list, 'kukie.io' ) ) {
			array_push( $list, ...$hosts );
		}

		return $list;
	}

	/**
	 * Resource hints for the banner: a preconnect to the CDN that serves the
	 * script (it saves the TLS handshake when the script sits at the end of
	 * the page) and a DNS lookup ahead of app.kukie.io, which the banner
	 * calls for the visitor's region and to record a choice.
	 *
	 * Only when this page really enqueued the banner (wp_enqueue_scripts runs
	 * on wp_head before the hints are printed), so the login page, the
	 * Customizer and administrators who do not get the banner get no hints.
	 * Both parameters tolerate a caller that passes fewer or other values:
	 * this runs on every public page.
	 *
	 * @since 1.11.0
	 * @param mixed $urls          Hints WordPress already prints.
	 * @param mixed $relation_type preconnect, dns-prefetch, ...
	 * @return mixed
	 */
	public function add_resource_hints( mixed $urls = [], mixed $relation_type = '' ): mixed {
		if ( ! is_array( $urls ) || ! wp_script_is( 'kukie-banner-script', 'enqueued' ) ) {
			return $urls;
		}

		if ( $relation_type === 'preconnect' ) {
			$urls[] = 'https://cdn.kukie.io';
		} elseif ( $relation_type === 'dns-prefetch' ) {
			$urls[] = '//app.kukie.io';
		}

		return $urls;
	}

	public function enqueue_banner_script(): void {
		// Don't inject in admin area or for preview/customizer
		if ( is_admin() || is_customize_preview() ) {
			return;
		}

		if ( $this->is_hidden_for_current_user() ) {
			return;
		}

		$site_key = $this->plugin->get_option( 'site_key', '' );

		if ( empty( $site_key ) ) {
			return;
		}

		// Always build the CDN bundle URL canonically from the site key. The
		// stored embed_url is never trusted for injection: legacy installs may
		// hold a stale app.kukie.io URL that no longer serves anything. CDN
		// bundles carry their config in the URL path -- no data-site-key needed.
		$src = sprintf( 'https://cdn.kukie.io/s/%s/c.js', rawurlencode( $site_key ) );

		$position = $this->plugin->get_option( 'script_position', 'head' );

		// Use config_version for cache-busting (changes on each settings save), fall back to plugin version
		$config_version = $this->plugin->get_option( 'config_version', '' );
		$version        = ! empty( $config_version ) ? $config_version : KUKIE_VERSION;

		wp_enqueue_script(
			'kukie-banner-script',
			esc_url( $src ),
			[],
			$version,
			[
				'strategy'  => 'async',
				'in_footer' => ( $position === 'body' ),
			]
		);
	}

	/**
	 * Whether the banner is withheld from the current visitor because the
	 * "Hide for administrators" setting is on and they can manage the site.
	 *
	 * Front-end page builders (Bricks, Elementor and others) run on the public
	 * side of the site and can break when their own cookies or scripts are
	 * blocked after a rejected consent; hiding the banner for administrators
	 * keeps the builder working while visitors still see the banner. Decided
	 * per request on wp_enqueue_scripts (never in init(), which runs before
	 * the current user is known), so a cached page for a visitor is never
	 * served without the script.
	 *
	 * Filter: `kukie_hide_banner_for_user` receives the decision plus the
	 * current user id, so a site can widen it (editors who use the builder)
	 * or narrow it.
	 *
	 * @since 1.8.2
	 */
	public function is_hidden_for_current_user(): bool {
		$hidden = (bool) $this->plugin->get_option( 'disable_for_admins', false )
			&& current_user_can( 'manage_options' );

		return (bool) apply_filters( 'kukie_hide_banner_for_user', $hidden, get_current_user_id() );
	}

	/**
	 * Add cache/minification exclusion attributes to the banner script tag and
	 * wrap with <!-- noptimize --> comments for Autoptimize compatibility.
	 *
	 * Also injects a `data-lang` attribute reflecting the current page language
	 * as detected by Kukie_Language_Detector (WPML / Polylang / WP core). The
	 * banner script reads this attribute as its highest-priority language
	 * signal.
	 *
	 * Attributes:
	 *   data-lang="xx"         - WPML/Polylang/WP locale (since 1.6.0)
	 *   data-no-minify="1"     - WP Rocket, LiteSpeed Cache
	 *   data-no-defer="1"      - WP Rocket
	 *   data-no-delay="1"      - WP Rocket (delay JS)
	 *   data-cfasync="false"   - Cloudflare Rocket Loader
	 *   data-pagespeed-no-defer - PageSpeed module (Apache/Nginx)
	 *   data-no-optimize="1"   - Autoptimize, SG Optimizer
	 */
	public function add_compatibility_attributes( string $tag, string $handle ): string {
		if ( $handle !== 'kukie-banner-script' ) {
			return $tag;
		}

		$attrs = 'data-no-minify="1" data-no-defer="1" data-no-delay="1" data-cfasync="false" data-pagespeed-no-defer data-no-optimize="1"';

		// Prepend data-lang when language detection yields a non-empty result.
		// When the detector returns '', we skip injection entirely so the
		// banner script's own fallback chain (<html lang> -> navigator.language
		// -> config default) takes over.
		$lang = Kukie_Language_Detector::detect();
		if ( ! empty( $lang ) ) {
			$attrs = 'data-lang="' . esc_attr( $lang ) . '" ' . $attrs;
		}

		$tag = str_replace( ' src=', ' ' . $attrs . ' src=', $tag );

		// Wrap with noptimize comments (respected by Autoptimize and others)
		$tag = "<!-- noptimize -->\n" . $tag . "<!-- /noptimize -->\n";

		return $tag;
	}

	public function admin_bar_status( \WP_Admin_Bar $wp_admin_bar ): void {
		if ( ! is_admin_bar_showing() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$banner_enabled = $this->plugin->get_option( 'banner_enabled', false );
		$dot_color      = $banner_enabled ? '#22c55e' : '#ef4444';
		// The dot's colour is never the only signal: screen readers hear the
		// state, and the tooltip below carries it for sighted users.
		$state_text = $banner_enabled
			? __( 'Consent banner on', 'kukie-cookie-consent' )
			: __( 'Consent banner off', 'kukie-cookie-consent' );

		$node = [
			'id'    => 'kukie-status',
			'title' => wp_kses(
				sprintf(
					'<span style="display:inline-flex;align-items:center;gap:4px;">'
					. '<span style="width:8px;height:8px;border-radius:50%%;background:%s;display:inline-block;" aria-hidden="true"></span>'
					. 'Kukie.io<span class="screen-reader-text">: %s</span></span>',
					esc_attr( $dot_color ),
					esc_html( $state_text )
				),
				[
					'span' => [ 'style' => [], 'class' => [], 'aria-hidden' => [] ],
				]
			),
			'href'  => admin_url( 'admin.php?page=kukie' ),
			'meta'  => [ 'title' => $state_text ],
		];

		// The dot still reports the site-wide banner state; the tooltip is
		// what tells an administrator why THEY do not see the banner.
		if ( $this->is_hidden_for_current_user() ) {
			$node['meta'] = [
				'title' => $state_text . '. ' . __( 'The banner is hidden for administrators. Visitors still see it.', 'kukie-cookie-consent' ),
			];
		}

		$wp_admin_bar->add_node( $node );
	}
}
