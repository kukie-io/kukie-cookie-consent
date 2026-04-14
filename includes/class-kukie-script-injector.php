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
		if ( ! $this->plugin->is_connected() ) {
			return;
		}

		if ( ! $this->plugin->is_api_key_valid() ) {
			return;
		}

		$position = $this->plugin->get_option( 'script_position', 'head' );

		if ( $position === 'manual' ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_banner_script' ] );
		add_filter( 'script_loader_tag', [ $this, 'add_compatibility_attributes' ], 10, 2 );

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
		// --- Autoptimize ---
		add_filter( 'autoptimize_filter_js_exclude', function ( $exclude ) {
			$kukie = 'kukie.io,c.js,kukie-cookie';
			return $exclude ? $exclude . ',' . $kukie : $kukie;
		} );

		// --- WP Rocket ---
		add_filter( 'rocket_exclude_js', function ( $excluded_js ) {
			if ( ! kukie_array_contains_domain( $excluded_js, 'kukie.io' ) ) {
				$excluded_js[] = 'cdn.kukie.io(.*)';
				$excluded_js[] = 'app.kukie.io(.*)';
			}
			return $excluded_js;
		} );
		add_filter( 'rocket_delay_js_exclusions', function ( $excluded ) {
			if ( ! kukie_array_contains_domain( $excluded, 'kukie.io' ) ) {
				$excluded[] = 'cdn.kukie.io';
				$excluded[] = 'app.kukie.io';
			}
			return $excluded;
		} );
		add_filter( 'rocket_minify_excluded_external_js', function ( $excluded ) {
			if ( ! kukie_array_contains_domain( $excluded, 'kukie.io' ) ) {
				$excluded[] = 'cdn.kukie.io';
				$excluded[] = 'app.kukie.io';
			}
			return $excluded;
		} );
		// WP Rocket: exclude from Load JavaScript deferred (Defer JS)
		add_filter( 'rocket_exclude_defer_js', function ( $excluded ) {
			if ( ! kukie_array_contains_domain( $excluded, 'kukie.io' ) ) {
				$excluded[] = 'cdn.kukie.io';
				$excluded[] = 'app.kukie.io';
			}
			return $excluded;
		} );

		// --- WP Fastest Cache ---
		add_filter( 'wpfc_minify_js_exclude', function ( $exclude ) {
			if ( ! is_array( $exclude ) ) {
				$exclude = [];
			}
			$exclude[] = 'kukie.io';
			return $exclude;
		} );

		// --- LiteSpeed Cache ---
		add_filter( 'litespeed_optimize_js_excludes', function ( $excludes ) {
			if ( ! kukie_array_contains_domain( $excludes, 'kukie.io' ) ) {
				$excludes[] = 'cdn.kukie.io';
				$excludes[] = 'app.kukie.io';
			}
			return $excludes;
		} );

		// --- W3 Total Cache ---
		add_filter( 'w3tc_minify_js_do_tag_minification', function ( $do_minify, $script_tag, $file ) {
			if ( str_contains( $file, 'kukie.io' ) || str_contains( $file, 'kukie-cookie' ) ) {
				return false;
			}
			return $do_minify;
		}, 10, 3 );

		// --- SG Optimizer ---
		add_filter( 'sgo_js_minify_exclude', function ( $exclude ) {
			$exclude[] = 'kukie.io';
			return $exclude;
		} );
		add_filter( 'sgo_javascript_combine_exclude', function ( $exclude ) {
			$exclude[] = 'kukie.io';
			return $exclude;
		} );
	}

	public function enqueue_banner_script(): void {
		// Don't inject in admin area or for preview/customizer
		if ( is_admin() || is_customize_preview() ) {
			return;
		}

		$embed_url = $this->plugin->get_option( 'embed_url', '' );
		$site_key  = $this->plugin->get_option( 'site_key', '' );

		if ( empty( $embed_url ) || empty( $site_key ) ) {
			return;
		}

		// CDN bundles have config embedded -- no data-site-key needed
		// App URL bundles need data-site-key
		$is_cdn = str_contains( $embed_url, '/s/' . $site_key . '/c.js' );

		$position = $this->plugin->get_option( 'script_position', 'head' );

		// Use config_version for cache-busting (changes on each settings save), fall back to plugin version
		$config_version = $this->plugin->get_option( 'config_version', '' );
		$version        = ! empty( $config_version ) ? $config_version : KUKIE_VERSION;

		wp_enqueue_script(
			'kukie-banner-script',
			esc_url( $embed_url ),
			[],
			$version,
			[
				'strategy'  => 'async',
				'in_footer' => ( $position === 'body' ),
			]
		);

		// For non-CDN URLs, pass the site key via inline script
		if ( ! $is_cdn ) {
			wp_add_inline_script(
				'kukie-banner-script',
				'window.__KUKIE_SITE_KEY__ = ' . wp_json_encode( sanitize_text_field( $site_key ) ) . ';',
				'before'
			);
		}
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

		$wp_admin_bar->add_node( [
			'id'    => 'kukie-status',
			'title' => wp_kses(
				sprintf(
					'<span style="display:inline-flex;align-items:center;gap:4px;">'
					. '<span style="width:8px;height:8px;border-radius:50%%;background:%s;display:inline-block;"></span>'
					. 'Kukie</span>',
					esc_attr( $dot_color )
				),
				[
					'span' => [ 'style' => [] ],
				]
			),
			'href'  => admin_url( 'admin.php?page=kukie' ),
		] );
	}
}
