<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Kukie_Admin {

	private Kukie_Plugin $plugin;

	public function __construct( Kukie_Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	public function init(): void {
		add_action( 'admin_menu', [ $this, 'register_menus' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_init', [ $this, 'maybe_redirect' ] );
		add_action( 'admin_notices', [ $this, 'connection_notice' ] );
		add_action( 'admin_notices', [ $this, 'invalid_api_key_notice' ] );
		add_action( 'admin_notices', [ $this, 'maybe_show_wp_rocket_notice' ] );

		// AJAX handlers
		add_action( 'wp_ajax_kukie_dismiss_wp_rocket_notice', [ $this, 'ajax_dismiss_wp_rocket_notice' ] );
		add_action( 'wp_ajax_kukie_connect', [ $this, 'ajax_connect' ] );
		add_action( 'wp_ajax_kukie_disconnect', [ $this, 'ajax_disconnect' ] );
		add_action( 'wp_ajax_kukie_get_status', [ $this, 'ajax_get_status' ] );
		add_action( 'wp_ajax_kukie_get_settings', [ $this, 'ajax_get_settings' ] );
		add_action( 'wp_ajax_kukie_save_settings', [ $this, 'ajax_save_settings' ] );
		add_action( 'wp_ajax_kukie_save_gcm', [ $this, 'ajax_save_gcm' ] );
		add_action( 'wp_ajax_kukie_save_uet', [ $this, 'ajax_save_uet' ] );
		add_action( 'wp_ajax_kukie_save_banner_design', [ $this, 'ajax_save_banner_design' ] );
		add_action( 'wp_ajax_kukie_trigger_scan', [ $this, 'ajax_trigger_scan' ] );
		add_action( 'wp_ajax_kukie_verify', [ $this, 'ajax_verify' ] );
	}

	// ─────────────────────────────────────────
	// MENUS
	// ─────────────────────────────────────────

	public function register_menus(): void {
		// Cookie icon from cookie.svg - adapted for WP admin menu (fill=currentColor for theme compat)
		$icon = 'data:image/svg+xml;base64,' . base64_encode(
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 641.3 616.6"><path fill="currentColor" d="M310.9,0C301,0,291.2.5,281.4,1.5c-12.3,1.2-21.9,10.9-23.1,23.1-11.8,122.4-108.7,219.3-231.1,231.1-12.3,1.2-21.9,10.9-23.1,23.1-.9,9.7-1.5,19.5-1.5,29.4,0,170.3,138,308.3,308.3,308.3s308.3-138,308.3-308.3S481.2,0,310.9,0ZM239.8,470c-44.6,0-80.8-36.2-80.8-80.8s36.2-80.8,80.8-80.8,80.8,36.2,80.8,80.8-36.2,80.8-80.8,80.8ZM320.7,227.5c0-44.6,36.2-80.8,80.8-80.8s80.8,36.2,80.8,80.8-36.2,80.8-80.8,80.8-80.8-36.2-80.8-80.8Z"/></svg>'
		);

		if ( ! $this->plugin->is_connected() ) {
			add_menu_page(
				__( 'Kukie.io', 'kukie-cookie-consent' ),
				__( 'Kukie.io', 'kukie-cookie-consent' ),
				'manage_options',
				'kukie-connect',
				[ $this, 'render_connect_page' ],
				$icon,
				100
			);
			return;
		}

		// Main menu → Dashboard
		add_menu_page(
			__( 'Kukie.io', 'kukie-cookie-consent' ),
			__( 'Kukie.io', 'kukie-cookie-consent' ),
			'manage_options',
			'kukie',
			[ $this, 'render_dashboard_page' ],
			$icon,
			100
		);

		add_submenu_page(
			'kukie',
			__( 'Dashboard', 'kukie-cookie-consent' ),
			__( 'Dashboard', 'kukie-cookie-consent' ),
			'manage_options',
			'kukie',
			[ $this, 'render_dashboard_page' ]
		);

		add_submenu_page(
			'kukie',
			__( 'Banner Design', 'kukie-cookie-consent' ),
			__( 'Banner Design', 'kukie-cookie-consent' ),
			'manage_options',
			'kukie-design',
			[ $this, 'render_banner_design_page' ]
		);

		add_submenu_page(
			'kukie',
			__( 'Google Consent Mode v2', 'kukie-cookie-consent' ),
			__( 'Google Consent Mode v2', 'kukie-cookie-consent' ),
			'manage_options',
			'kukie-gcm',
			[ $this, 'render_gcm_page' ]
		);

		add_submenu_page(
			'kukie',
			__( 'Microsoft UET', 'kukie-cookie-consent' ),
			__( 'Microsoft UET', 'kukie-cookie-consent' ),
			'manage_options',
			'kukie-uet',
			[ $this, 'render_uet_page' ]
		);

		add_submenu_page(
			'kukie',
			__( 'Settings', 'kukie-cookie-consent' ),
			__( 'Settings', 'kukie-cookie-consent' ),
			'manage_options',
			'kukie-settings',
			[ $this, 'render_settings_page' ]
		);

		// Hidden connect page (for reconnecting)
		add_submenu_page(
			null,
			__( 'Connect', 'kukie-cookie-consent' ),
			__( 'Connect', 'kukie-cookie-consent' ),
			'manage_options',
			'kukie-connect',
			[ $this, 'render_connect_page' ]
		);
	}

	// ─────────────────────────────────────────
	// ASSETS
	// ─────────────────────────────────────────

	public function enqueue_assets( string $hook ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WordPress admin menu page parameter
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( ! in_array( $page, [ 'kukie', 'kukie-connect', 'kukie-design', 'kukie-gcm', 'kukie-uet', 'kukie-settings' ], true ) ) {
			return;
		}

		wp_enqueue_style(
			'kukie-admin',
			KUKIE_PLUGIN_URL . 'assets/css/admin.css',
			[],
			KUKIE_VERSION
		);

		wp_enqueue_script(
			'kukie-admin',
			KUKIE_PLUGIN_URL . 'assets/js/admin.js',
			[],
			KUKIE_VERSION,
			true
		);

		wp_localize_script( 'kukie-admin', 'kukieAdmin', [
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'kukie_admin' ),
			'dashboardUrl' => $this->plugin->get_option( 'dashboard_url', 'https://app.kukie.io' ),
			'siteId'       => $this->plugin->get_option( 'site_id', 0 ),
			'isConnected'  => $this->plugin->is_connected(),
		] );
	}

	// ─────────────────────────────────────────
	// REDIRECTS & NOTICES
	// ─────────────────────────────────────────

	public function maybe_redirect(): void {
		if ( get_transient( 'kukie_activation_redirect' ) ) {
			delete_transient( 'kukie_activation_redirect' );

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WordPress core sets activate-multi during bulk activation
			if ( ! wp_doing_ajax() && ! isset( $_GET['activate-multi'] ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=kukie-connect' ) );
				exit;
			}
		}
	}

	public function connection_notice(): void {
		if ( $this->plugin->is_connected() ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen && str_contains( $screen->id, 'kukie' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s <a href="%s">%s</a></p></div>',
			esc_html__( 'Kukie.io cookie consent is not connected.', 'kukie-cookie-consent' ),
			esc_url( admin_url( 'admin.php?page=kukie-connect' ) ),
			esc_html__( 'Connect now &rarr;', 'kukie-cookie-consent' )
		);
	}

	public function invalid_api_key_notice(): void {
		if ( ! $this->plugin->is_connected() || $this->plugin->is_api_key_valid() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Don't show on Kukie's own admin pages (the dashboard already has the detailed notice)
		$screen = get_current_screen();
		if ( $screen && str_contains( $screen->id, 'kukie' ) ) {
			return;
		}

		$settings = $this->plugin->get_settings();
		$site_id  = $settings['site_id'] ?? '';
		$key_url  = 'https://app.kukie.io/sites/' . rawurlencode( (string) $site_id );

		printf(
			'<div class="notice notice-error"><p><strong>%s</strong> %s <a href="%s" target="_blank" rel="noopener noreferrer">%s &rarr;</a></p></div>',
			esc_html__( 'Kukie:', 'kukie-cookie-consent' ),
			esc_html__( 'Invalid API key - the cookie consent banner is disabled.', 'kukie-cookie-consent' ),
			esc_url( $key_url ),
			esc_html__( 'Generate a new API key', 'kukie-cookie-consent' )
		);
	}

	// ─────────────────────────────────────────
	// WP ROCKET COMPATIBILITY
	// ─────────────────────────────────────────

	/**
	 * Check WP Rocket compatibility by inspecting runtime exclusion state.
	 *
	 * Unlike the previous implementation, this checks what WP Rocket will actually
	 * do at runtime (via apply_filters) rather than what is stored in the DB option.
	 * This means the notice only fires when there is a genuine configuration problem,
	 * not when our own filters are silently handling the exclusions.
	 *
	 * @since 1.5.0
	 * @return array List of WP Rocket setting labels missing kukie exclusion.
	 */
	public function check_wp_rocket_compatibility(): array {
		// Bail if WP Rocket is not active.
		if ( ! defined( 'WP_ROCKET_VERSION' ) ) {
			return [];
		}

		$rocket_settings = get_option( 'wp_rocket_settings', [] );
		if ( ! is_array( $rocket_settings ) ) {
			return [];
		}

		$issues = [];

		// Helper to test whether a runtime filter pipeline excludes cdn.kukie.io.
		$is_excluded_at_runtime = function ( $filter_name ) {
			$excluded = apply_filters( $filter_name, [] );
			if ( ! is_array( $excluded ) ) {
				return false;
			}
			foreach ( $excluded as $entry ) {
				if ( false !== strpos( (string) $entry, 'cdn.kukie.io' ) ) {
					return true;
				}
			}
			return false;
		};

		// Minify JS - check both filters WP Rocket consults.
		if ( ! empty( $rocket_settings['minify_js'] ) ) {
			$excluded_minify = $is_excluded_at_runtime( 'rocket_exclude_js' )
				|| $is_excluded_at_runtime( 'rocket_minify_excluded_external_js' );
			if ( ! $excluded_minify ) {
				$issues[] = __( 'Minify JavaScript files', 'kukie-cookie-consent' );
			}
		}

		// Defer JS.
		if ( ! empty( $rocket_settings['defer_all_js'] ) ) {
			if ( ! $is_excluded_at_runtime( 'rocket_exclude_defer_js' ) ) {
				$issues[] = __( 'Load JavaScript deferred', 'kukie-cookie-consent' );
			}
		}

		// Delay JS.
		if ( ! empty( $rocket_settings['delay_js'] ) ) {
			if ( ! $is_excluded_at_runtime( 'rocket_delay_js_exclusions' ) ) {
				$issues[] = __( 'Delay JavaScript execution', 'kukie-cookie-consent' );
			}
		}

		return $issues;
	}

	public function maybe_show_wp_rocket_notice(): void {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'kukie' ) === false ) {
			return;
		}

		if ( ! $this->plugin->is_connected() ) {
			return;
		}

		// Check if dismissed by this user
		if ( get_user_meta( get_current_user_id(), 'kukie_wp_rocket_notice_dismissed', true ) ) {
			return;
		}

		$issues = $this->check_wp_rocket_compatibility();

		if ( empty( $issues ) ) {
			return;
		}

		$issue_list = '';
		foreach ( $issues as $issue_label ) {
			$issue_list .= '<li><strong>' . esc_html( $issue_label ) . ' - ' . esc_html__( 'Excluded JavaScript Files', 'kukie-cookie-consent' ) . '</strong></li>';
		}

		$rocket_settings_url = admin_url( 'options-general.php?page=wprocket#file_optimization' );
		$help_url            = 'https://kukie.io/docs/wordpress-plugin/troubleshoot-wordpress-plugin';
		$dismiss_nonce       = wp_create_nonce( 'kukie_dismiss_wp_rocket_notice' );

		printf(
			'<div class="notice notice-warning kukie-notice" id="kukie-wp-rocket-notice"><p>'
			. '<strong>%s</strong> %s'
			. '</p><ul style="list-style:disc;margin-left:20px;">%s</ul>'
			. '<p><a href="%s" class="button button-small">%s</a> '
			. '<a href="%s" target="_blank" rel="noopener noreferrer" style="margin-left:8px;">%s</a>'
			. '<button type="button" class="kukie-dismiss-btn" style="margin-left:12px;background:none;border:none;color:#787c82;cursor:pointer;text-decoration:underline;padding:0;font-size:13px;">%s</button>'
			. '</p></div>'
			. '<script>document.querySelector("#kukie-wp-rocket-notice .kukie-dismiss-btn")?.addEventListener("click",function(){var n=this.closest(".notice");n&&(n.style.display="none");var x=new XMLHttpRequest();x.open("POST","%s");x.setRequestHeader("Content-Type","application/x-www-form-urlencoded");x.send("action=kukie_dismiss_wp_rocket_notice&nonce=%s");});</script>',
			esc_html__( 'Kukie.io - WP Rocket detected:', 'kukie-cookie-consent' ),
			esc_html__( 'Your cookie banner may not load correctly. Add cdn.kukie.io to the exclusion list in these WP Rocket settings:', 'kukie-cookie-consent' ),
			$issue_list,
			esc_url( $rocket_settings_url ),
			esc_html__( 'Open WP Rocket Settings', 'kukie-cookie-consent' ),
			esc_url( $help_url ),
			esc_html__( 'Learn more', 'kukie-cookie-consent' ),
			esc_html__( 'Dismiss', 'kukie-cookie-consent' ),
			esc_url( admin_url( 'admin-ajax.php' ) ),
			esc_attr( $dismiss_nonce )
		);
	}

	public function ajax_dismiss_wp_rocket_notice(): void {
		check_ajax_referer( 'kukie_dismiss_wp_rocket_notice', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [], 403 );
		}

		update_user_meta( get_current_user_id(), 'kukie_wp_rocket_notice_dismissed', '1' );
		wp_send_json_success();
	}

	// ─────────────────────────────────────────
	// PAGE RENDERERS
	// ─────────────────────────────────────────

	public function render_connect_page(): void {
		require KUKIE_PLUGIN_DIR . 'templates/admin-connect.php';
	}

	public function render_dashboard_page(): void {
		require KUKIE_PLUGIN_DIR . 'templates/admin-dashboard.php';
	}

	public function render_banner_design_page(): void {
		require KUKIE_PLUGIN_DIR . 'templates/admin-banner-design.php';
	}

	public function render_gcm_page(): void {
		require KUKIE_PLUGIN_DIR . 'templates/admin-gcm.php';
	}

	public function render_uet_page(): void {
		require KUKIE_PLUGIN_DIR . 'templates/admin-uet.php';
	}

	public function render_settings_page(): void {
		require KUKIE_PLUGIN_DIR . 'templates/admin-settings.php';
	}

	// ─────────────────────────────────────────
	// AJAX HANDLERS
	// ─────────────────────────────────────────

	public function ajax_connect(): void {
		check_ajax_referer( 'kukie_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorised.', 'kukie-cookie-consent' ) ], 403 );
		}

		$api_key = isset( $_POST['api_key'] )
			? preg_replace( '/[^a-zA-Z0-9]/', '', sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) )
			: '';

		if ( strlen( $api_key ) !== 64 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid API key format. The key should be 64 characters.', 'kukie-cookie-consent' ) ] );
		}

		$client   = new Kukie_Api_Client( $api_key );
		$response = $client->post( '/connect' );

		if ( ! $response['success'] ) {
			wp_send_json_error( [ 'message' => $response['error'] ?? __( 'Could not connect. Please check your API key.', 'kukie-cookie-consent' ) ] );
		}

		$data = $response['data'];

		$this->plugin->update_options( [
			'api_key_encrypted' => Kukie_Encryption::encrypt( $api_key ),
			'api_key_valid'     => true,
			'site_key'          => sanitize_text_field( $data['site_key'] ?? '' ),
			'site_id'           => absint( $data['site_id'] ?? 0 ),
			'domain'            => sanitize_text_field( $data['domain'] ?? '' ),
			'organisation'      => sanitize_text_field( $data['organisation'] ?? '' ),
			'plan_name'         => sanitize_text_field( $data['plan']['name'] ?? 'Free' ),
			'embed_url'         => esc_url_raw( $data['embed_url'] ?? '' ),
			'dashboard_url'     => esc_url_raw( $data['dashboard_url'] ?? '' ),
			'banner_enabled'    => (bool) ( $data['banner_enabled'] ?? true ),
			'connected_at'      => current_time( 'c' ),
			'script_position'   => 'head',
			'config_version'    => (string) time(),
		] );

		delete_transient( 'kukie_dashboard_data' );
		delete_transient( 'kukie_settings_cache' );

		wp_send_json_success( [
			'message'      => __( 'Connected successfully!', 'kukie-cookie-consent' ),
			'organisation' => sanitize_text_field( $data['organisation'] ?? '' ),
			'plan'         => sanitize_text_field( $data['plan']['name'] ?? '' ),
			'domain'       => sanitize_text_field( $data['domain'] ?? '' ),
			'redirect'     => admin_url( 'admin.php?page=kukie' ),
		] );
	}

	public function ajax_disconnect(): void {
		check_ajax_referer( 'kukie_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorised.', 'kukie-cookie-consent' ) ], 403 );
		}

		$client = $this->plugin->get_api_client();
		if ( $client ) {
			$client->post( '/disconnect' );
		}

		delete_option( 'kukie_settings' );
		delete_transient( 'kukie_dashboard_data' );
		delete_transient( 'kukie_settings_cache' );

		wp_send_json_success( [
			'message'  => __( 'Disconnected from Kukie.io.', 'kukie-cookie-consent' ),
			'redirect' => admin_url( 'admin.php?page=kukie-connect' ),
		] );
	}

	public function ajax_get_status(): void {
		check_ajax_referer( 'kukie_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorised.', 'kukie-cookie-consent' ) ], 403 );
		}

		$cached = get_transient( 'kukie_dashboard_data' );
		if ( $cached !== false ) {
			wp_send_json_success( $cached );
		}

		$client = $this->plugin->get_api_client();
		if ( ! $client ) {
			wp_send_json_error( [ 'message' => __( 'Not connected.', 'kukie-cookie-consent' ) ] );
		}

		$response = $client->get( '/status' );

		if ( ! $response['success'] ) {
			wp_send_json_error( [ 'message' => $response['error'] ] );
		}

		set_transient( 'kukie_dashboard_data', $response['data'], 5 * MINUTE_IN_SECONDS );

		wp_send_json_success( $response['data'] );
	}

	public function ajax_get_settings(): void {
		check_ajax_referer( 'kukie_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorised.', 'kukie-cookie-consent' ) ], 403 );
		}

		$cached = get_transient( 'kukie_settings_cache' );
		if ( $cached !== false ) {
			$cached['script_position'] = $this->plugin->get_option( 'script_position', 'head' );
			$cached['force_language']  = $this->plugin->get_option( 'force_language', 'auto' );
			wp_send_json_success( $cached );
		}

		$client = $this->plugin->get_api_client();
		if ( ! $client ) {
			wp_send_json_error( [ 'message' => __( 'Not connected.', 'kukie-cookie-consent' ) ] );
		}

		$response = $client->get( '/settings' );

		if ( ! $response['success'] ) {
			wp_send_json_error( [ 'message' => $response['error'] ] );
		}

		set_transient( 'kukie_settings_cache', $response['data'], 10 * MINUTE_IN_SECONDS );

		$data                    = $response['data'];
		$data['script_position'] = $this->plugin->get_option( 'script_position', 'head' );
		$data['force_language']  = $this->plugin->get_option( 'force_language', 'auto' );

		wp_send_json_success( $data );
	}

	/**
	 * Whitelist of language codes accepted by the "Banner language" override
	 * dropdown. `auto` disables the override (detector falls through to
	 * WPML / Polylang / WP core). All other entries are Kukie-format short
	 * codes matching the banner script's translations map.
	 *
	 * This list is an escape hatch for manual override, not the canonical
	 * language catalogue — the full 71-language Kukie set is still honored
	 * via auto-detect. Values must already be Kukie-normalized (lowercase,
	 * hyphen-separated).
	 *
	 * @since 1.6.0
	 * @return string[]
	 */
	private function allowed_force_languages(): array {
		return [
			'auto',
			'en', 'de', 'fr', 'es', 'it', 'pt', 'pt-br', 'nl',
			'pl', 'ru', 'tr', 'ja', 'zh-cn', 'zh-tw', 'ar', 'bg',
			'cs', 'da', 'el', 'fi', 'he', 'hu', 'id', 'ko',
			'no', 'ro', 'sk', 'sv', 'th', 'uk', 'vi',
		];
	}

	public function ajax_save_settings(): void {
		check_ajax_referer( 'kukie_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorised.', 'kukie-cookie-consent' ) ], 403 );
		}

		// Local-only: script_position
		$script_position = sanitize_text_field( wp_unslash( $_POST['script_position'] ?? 'head' ) );
		if ( ! in_array( $script_position, [ 'head', 'body', 'manual' ], true ) ) {
			$script_position = 'head';
		}
		$this->plugin->update_option( 'script_position', $script_position );

		// Local-only: force_language (WPML/Polylang override dropdown).
		// Invalid values silently fall back to 'auto' so the detector
		// takes over normally.
		$force_language = sanitize_text_field( wp_unslash( $_POST['force_language'] ?? 'auto' ) );
		if ( ! in_array( $force_language, $this->allowed_force_languages(), true ) ) {
			$force_language = 'auto';
		}
		$this->plugin->update_option( 'force_language', $force_language );

		// API-synced settings
		$api_data = [
			'banner_enabled'    => rest_sanitize_boolean( $_POST['banner_enabled'] ?? true ),
			'auto_translate'    => rest_sanitize_boolean( $_POST['auto_translate'] ?? true ),
			'default_language'  => sanitize_text_field( wp_unslash( $_POST['default_language'] ?? 'en' ) ),
			'enabled_languages' => isset( $_POST['enabled_languages'] ) && is_array( $_POST['enabled_languages'] )
				? array_map( 'sanitize_text_field', wp_unslash( $_POST['enabled_languages'] ) )
				: [],
		];

		$this->plugin->update_option( 'banner_enabled', $api_data['banner_enabled'] );

		$client = $this->plugin->get_api_client();
		if ( ! $client ) {
			wp_send_json_error( [ 'message' => __( 'Not connected.', 'kukie-cookie-consent' ) ] );
		}

		$response = $client->put( '/settings', $api_data );

		if ( ! $response['success'] ) {
			wp_send_json_error( [ 'message' => $response['error'] ] );
		}

		// Update config version for cache-busting (forces browser to fetch fresh CDN bundle)
		$this->plugin->update_options( [ 'config_version' => (string) time() ] );

		delete_transient( 'kukie_settings_cache' );

		wp_send_json_success( [ 'message' => __( 'Settings saved.', 'kukie-cookie-consent' ) ] );
	}

	public function ajax_save_gcm(): void {
		check_ajax_referer( 'kukie_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorised.', 'kukie-cookie-consent' ) ], 403 );
		}

		$api_data = [
			'gcm_v2_enabled'     => rest_sanitize_boolean( $_POST['gcm_v2_enabled'] ?? false ),
			'auto_block_scripts' => rest_sanitize_boolean( $_POST['auto_block_scripts'] ?? false ),
		];

		$client = $this->plugin->get_api_client();
		if ( ! $client ) {
			wp_send_json_error( [ 'message' => __( 'Not connected.', 'kukie-cookie-consent' ) ] );
		}

		$response = $client->put( '/settings', $api_data );

		if ( ! $response['success'] ) {
			wp_send_json_error( [ 'message' => $response['error'] ] );
		}

		// Update config version for cache-busting (forces browser to fetch fresh CDN bundle)
		$this->plugin->update_options( [ 'config_version' => (string) time() ] );

		delete_transient( 'kukie_settings_cache' );

		wp_send_json_success( [ 'message' => __( 'Google Consent Mode settings saved.', 'kukie-cookie-consent' ) ] );
	}

	public function ajax_save_uet(): void {
		check_ajax_referer( 'kukie_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorised.', 'kukie-cookie-consent' ) ], 403 );
		}

		$api_data = [
			'ms_uet_enabled' => rest_sanitize_boolean( $_POST['ms_uet_enabled'] ?? false ),
		];

		$client = $this->plugin->get_api_client();
		if ( ! $client ) {
			wp_send_json_error( [ 'message' => __( 'Not connected.', 'kukie-cookie-consent' ) ] );
		}

		$response = $client->put( '/settings', $api_data );

		if ( ! $response['success'] ) {
			wp_send_json_error( [ 'message' => $response['error'] ] );
		}

		// Update config version for cache-busting (forces browser to fetch fresh CDN bundle)
		$this->plugin->update_options( [ 'config_version' => (string) time() ] );

		delete_transient( 'kukie_settings_cache' );

		wp_send_json_success( [ 'message' => __( 'Microsoft UET settings saved.', 'kukie-cookie-consent' ) ] );
	}

	public function ajax_save_banner_design(): void {
		check_ajax_referer( 'kukie_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorised.', 'kukie-cookie-consent' ) ], 403 );
		}

		$layout = sanitize_text_field( wp_unslash( $_POST['layout'] ?? 'popup' ) );
		if ( ! in_array( $layout, [ 'popup', 'bar-bottom', 'bar-top', 'floating' ], true ) ) {
			$layout = 'popup';
		}

		$position = sanitize_text_field( wp_unslash( $_POST['position'] ?? 'bottom-left' ) );
		if ( ! in_array( $position, [ 'center', 'bottom-left', 'bottom-center', 'bottom-right' ], true ) ) {
			$position = 'bottom-left';
		}

		// Revisit button - each sub-field is individually sanitized below
		$rb_raw = isset( $_POST['revisit_button'] ) && is_array( $_POST['revisit_button'] )
			? map_deep( wp_unslash( $_POST['revisit_button'] ), 'sanitize_text_field' )
			: [];

		$rb_position = sanitize_text_field( $rb_raw['position'] ?? 'bottom_left' );
		if ( ! in_array( $rb_position, [ 'bottom_left', 'bottom_right', 'top_left', 'top_right' ], true ) ) {
			$rb_position = 'bottom_left';
		}

		$rb_style = sanitize_text_field( $rb_raw['style'] ?? 'icon' );
		if ( ! in_array( $rb_style, [ 'icon', 'pill', 'tab' ], true ) ) {
			$rb_style = 'icon';
		}

		$rb_icon = sanitize_text_field( $rb_raw['icon'] ?? 'cookie' );
		if ( ! in_array( $rb_icon, [ 'cookie', 'shield', 'settings', 'fingerprint' ], true ) ) {
			$rb_icon = 'cookie';
		}

		$revisit_button = [
			'enabled'    => ! empty( $rb_raw['enabled'] ) && $rb_raw['enabled'] !== '0',
			'position'   => $rb_position,
			'style'      => $rb_style,
			'icon'       => $rb_icon,
			'text'       => sanitize_text_field( $rb_raw['text'] ?? 'Cookie Settings' ),
			'color'      => sanitize_text_field( $rb_raw['color'] ?? '' ),
			'icon_color' => sanitize_hex_color( $rb_raw['icon_color'] ?? '' ) ?: '',
			'offset_x'   => max( 0, min( 200, absint( $rb_raw['offset_x'] ?? 20 ) ) ),
			'offset_y'   => max( 0, min( 200, absint( $rb_raw['offset_y'] ?? 20 ) ) ),
		];

		$api_data = [
			'layout'         => $layout,
			'position'       => $position,
			'revisit_button' => $revisit_button,
		];

		$client = $this->plugin->get_api_client();
		if ( ! $client ) {
			wp_send_json_error( [ 'message' => __( 'Not connected.', 'kukie-cookie-consent' ) ] );
		}

		$response = $client->put( '/settings', $api_data );

		if ( ! $response['success'] ) {
			wp_send_json_error( [ 'message' => $response['error'] ] );
		}

		// Update config version for cache-busting (forces browser to fetch fresh CDN bundle)
		$this->plugin->update_options( [ 'config_version' => (string) time() ] );

		delete_transient( 'kukie_settings_cache' );

		wp_send_json_success( [ 'message' => __( 'Banner design saved.', 'kukie-cookie-consent' ) ] );
	}

	public function ajax_trigger_scan(): void {
		check_ajax_referer( 'kukie_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorised.', 'kukie-cookie-consent' ) ], 403 );
		}

		$client = $this->plugin->get_api_client();
		if ( ! $client ) {
			wp_send_json_error( [ 'message' => __( 'Not connected.', 'kukie-cookie-consent' ) ] );
		}

		$response = $client->post( '/scan' );

		if ( ! $response['success'] ) {
			$message = $response['status'] === 429
				? __( 'A scan is already running. Please wait for it to complete.', 'kukie-cookie-consent' )
				: ( $response['error'] ?? __( 'Could not start scan.', 'kukie-cookie-consent' ) );

			wp_send_json_error( [ 'message' => $message ] );
		}

		delete_transient( 'kukie_dashboard_data' );

		wp_send_json_success( [ 'message' => __( 'Cookie scan started!', 'kukie-cookie-consent' ) ] );
	}

	public function ajax_verify(): void {
		check_ajax_referer( 'kukie_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorised.', 'kukie-cookie-consent' ) ], 403 );
		}

		$client = $this->plugin->get_api_client();
		if ( ! $client ) {
			wp_send_json_error( [ 'message' => __( 'Not connected.', 'kukie-cookie-consent' ) ] );
		}

		$response = $client->post( '/verify' );

		if ( ! $response['success'] ) {
			wp_send_json_error( [ 'message' => $response['error'] ?? __( 'Verification failed.', 'kukie-cookie-consent' ) ] );
		}

		wp_send_json_success( $response['data'] );
	}
}
