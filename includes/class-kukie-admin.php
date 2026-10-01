<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Kukie_Admin {

	/**
	 * Admin page slugs. The five visible entries are Dashboard, Consent banner
	 * (one page, seven tabs), Accessibility widget, Uptime monitoring (since
	 * 1.9.0) and Settings;
	 * the connect page is hidden and the three LEGACY slugs stay registered as
	 * hidden pages whose only job is to redirect old bookmarks and inter-page
	 * links to the matching Consent banner tab (see redirect_legacy_page()).
	 *
	 * @since 1.8.0
	 */
	public const PAGE_DASHBOARD     = 'kukie';
	public const PAGE_BANNER        = 'kukie-banner';
	public const PAGE_ACCESSIBILITY = 'kukie-accessibility';
	public const PAGE_UPTIME        = 'kukie-uptime';
	public const PAGE_SETTINGS      = 'kukie-settings';
	public const PAGE_CONNECT       = 'kukie-connect';

	/** Legacy slug => Consent banner tab it now lives on. */
	public const LEGACY_PAGES = [
		'kukie-design' => 'design',
		'kukie-gcm'    => 'gcm',
		'kukie-uet'    => 'uet',
	];

	/** Tabs of the Consent banner page, in display order. */
	public const BANNER_TABS = [ 'design', 'modal', 'behaviour', 'iframes', 'language', 'gcm', 'uet', 'regions' ];

	/**
	 * Preferences modal buttons (the server's PREFERENCES_MODAL_BUTTONS); an
	 * order is a permutation of these three.
	 *
	 * @since 1.9.0
	 */
	public const MODAL_BUTTONS = [ 'reject', 'save', 'accept' ];

	/** Accessibility widget whitelists - mirrors the server's, which is authoritative. */
	public const A11Y_POSITIONS = [ 'bottom-right', 'bottom-left' ];
	public const A11Y_SIZES     = [ 44, 52, 60 ];

	/**
	 * Uptime check cadences (seconds) the save handler forwards; the server
	 * validates them against its own list and the plan, which is
	 * authoritative. Anything else is dropped rather than sent.
	 *
	 * @since 1.9.0
	 */
	public const UPTIME_INTERVALS = [ 60, 180, 300, 900, 1800, 3600 ];

	private Kukie_Plugin $plugin;

	public function __construct( Kukie_Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	public function init(): void {
		add_action( 'admin_menu', [ $this, 'register_menus' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_init', [ $this, 'maybe_redirect' ] );
		add_action( 'admin_notices', [ $this, 'connection_notice' ] );
		add_action( 'admin_notices', [ $this, 'reconnect_notice' ] );
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
		add_action( 'wp_ajax_kukie_save_a11y', [ $this, 'ajax_save_a11y' ] );
		add_action( 'wp_ajax_kukie_save_behaviour', [ $this, 'ajax_save_behaviour' ] );
		add_action( 'wp_ajax_kukie_save_iframes', [ $this, 'ajax_save_iframes' ] );
		add_action( 'wp_ajax_kukie_save_preferences_modal', [ $this, 'ajax_save_preferences_modal' ] );
		add_action( 'wp_ajax_kukie_trigger_scan', [ $this, 'ajax_trigger_scan' ] );
		add_action( 'wp_ajax_kukie_scan_status', [ $this, 'ajax_scan_status' ] );
		add_action( 'wp_ajax_kukie_verify', [ $this, 'ajax_verify' ] );
		add_action( 'wp_ajax_kukie_get_uptime', [ $this, 'ajax_get_uptime' ] );
		add_action( 'wp_ajax_kukie_save_uptime', [ $this, 'ajax_save_uptime' ] );
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
				self::PAGE_CONNECT,
				[ $this, 'render_connect_page' ],
				$icon,
				100
			);
			return;
		}

		// Main menu -> Dashboard
		add_menu_page(
			__( 'Kukie.io', 'kukie-cookie-consent' ),
			__( 'Kukie.io', 'kukie-cookie-consent' ),
			'manage_options',
			self::PAGE_DASHBOARD,
			[ $this, 'render_dashboard_page' ],
			$icon,
			100
		);

		add_submenu_page(
			self::PAGE_DASHBOARD,
			__( 'Dashboard', 'kukie-cookie-consent' ),
			__( 'Dashboard', 'kukie-cookie-consent' ),
			'manage_options',
			self::PAGE_DASHBOARD,
			[ $this, 'render_dashboard_page' ]
		);

		add_submenu_page(
			self::PAGE_DASHBOARD,
			__( 'Consent banner', 'kukie-cookie-consent' ),
			__( 'Consent banner', 'kukie-cookie-consent' ),
			'manage_options',
			self::PAGE_BANNER,
			[ $this, 'render_banner_page' ]
		);

		add_submenu_page(
			self::PAGE_DASHBOARD,
			__( 'Accessibility widget', 'kukie-cookie-consent' ),
			__( 'Accessibility widget', 'kukie-cookie-consent' ),
			'manage_options',
			self::PAGE_ACCESSIBILITY,
			[ $this, 'render_accessibility_page' ]
		);

		// Visible on every plan: an unentitled plan sees what the feature
		// does and which plan includes it (the page reads the gate from
		// Kukie.io on every load, so an upgrade unlocks it at once).
		add_submenu_page(
			self::PAGE_DASHBOARD,
			__( 'Uptime monitoring', 'kukie-cookie-consent' ),
			__( 'Uptime monitoring', 'kukie-cookie-consent' ),
			'manage_options',
			self::PAGE_UPTIME,
			[ $this, 'render_uptime_page' ]
		);

		add_submenu_page(
			self::PAGE_DASHBOARD,
			__( 'Settings', 'kukie-cookie-consent' ),
			__( 'Settings', 'kukie-cookie-consent' ),
			'manage_options',
			self::PAGE_SETTINGS,
			[ $this, 'render_settings_page' ]
		);

		// Hidden connect page (for reconnecting). An EMPTY parent slug
		// registers the page without a menu entry; the historical null
		// parent reaches plugin_basename()'s string functions and raises
		// PHP 8.1+ deprecation notices on every admin request.
		add_submenu_page(
			'',
			__( 'Connect', 'kukie-cookie-consent' ),
			__( 'Connect', 'kukie-cookie-consent' ),
			'manage_options',
			self::PAGE_CONNECT,
			[ $this, 'render_connect_page' ]
		);

		// Legacy slugs (pre-1.8.0 Banner Design / GCM / UET pages): still
		// registered so bookmarks pass WordPress's page-access check, then
		// redirected to the matching Consent banner tab before any output.
		// The render callback is only a fallback for a redirect that could
		// not fire; load-{hook} runs first on every normal request.
		foreach ( array_keys( self::LEGACY_PAGES ) as $legacy_slug ) {
			$hook = add_submenu_page(
				'',
				__( 'Consent banner', 'kukie-cookie-consent' ),
				__( 'Consent banner', 'kukie-cookie-consent' ),
				'manage_options',
				$legacy_slug,
				[ $this, 'render_banner_page' ]
			);
			if ( is_string( $hook ) && $hook !== '' ) {
				add_action( 'load-' . $hook, [ $this, 'redirect_legacy_page' ] );
			}
		}
	}

	/**
	 * Requested tab of the Consent banner page, whitelisted to BANNER_TABS
	 * (default: design). Read by the page template and by the legacy redirect.
	 *
	 * @since 1.8.0
	 */
	public static function current_banner_tab(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab selector on an admin page
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';

		return in_array( $tab, self::BANNER_TABS, true ) ? $tab : 'design';
	}

	/**
	 * URL of one Consent banner tab (or of the page itself for the default tab).
	 *
	 * @since 1.8.0
	 */
	public static function banner_tab_url( string $tab ): string {
		$args = [ 'page' => self::PAGE_BANNER ];
		if ( $tab !== 'design' && in_array( $tab, self::BANNER_TABS, true ) ) {
			$args['tab'] = $tab;
		}

		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/**
	 * Redirect a pre-1.8.0 page slug to its Consent banner tab. Runs on the
	 * legacy page's load-{hook} action - after WordPress's page-access check
	 * and before any output - so an old bookmark or inter-plugin link lands
	 * on the right tab instead of a "not allowed" screen.
	 *
	 * @since 1.8.0
	 */
	public function redirect_legacy_page(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WordPress admin menu page parameter
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$tab  = self::LEGACY_PAGES[ $page ] ?? 'design';

		wp_safe_redirect( self::banner_tab_url( $tab ) );
		exit;
	}

	// ─────────────────────────────────────────
	// ASSETS
	// ─────────────────────────────────────────

	public function enqueue_assets( string $hook ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WordPress admin menu page parameter
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( ! in_array( $page, [ self::PAGE_DASHBOARD, self::PAGE_CONNECT, self::PAGE_BANNER, self::PAGE_ACCESSIBILITY, self::PAGE_UPTIME, self::PAGE_SETTINGS ], true ) ) {
			return;
		}

		wp_enqueue_style(
			'kukie-admin',
			KUKIE_PLUGIN_URL . 'assets/css/admin.css',
			[],
			self::asset_version( 'assets/css/admin.css' )
		);

		wp_enqueue_script(
			'kukie-admin',
			KUKIE_PLUGIN_URL . 'assets/js/admin.js',
			[],
			self::asset_version( 'assets/js/admin.js' ),
			true
		);

		wp_localize_script( 'kukie-admin', 'kukieAdmin', [
			'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
			'nonce'            => wp_create_nonce( 'kukie_admin' ),
			'dashboardUrl'     => $this->plugin->get_option( 'dashboard_url', 'https://app.kukie.io' ),
			'billingUrl'       => 'https://app.kukie.io/billing',
			'embedUrl'         => $this->plugin->get_option( 'embed_url', '' ),
			'siteId'           => $this->plugin->get_option( 'site_id', 0 ),
			'isConnected'      => $this->plugin->is_connected(),
			'a11yPageUrl'      => admin_url( 'admin.php?page=' . self::PAGE_ACCESSIBILITY ),
			'uptimePageUrl'    => admin_url( 'admin.php?page=' . self::PAGE_UPTIME ),
			// BCP 47 tag of the admin user's language, so dates, numbers and
			// relative times follow WordPress rather than the browser.
			'locale'           => str_replace( '_', '-', get_user_locale() ),
			// The WP Rocket notice's dismiss handler lives in admin.js since
			// 1.8.0 (no inline <script> in PHP output); it needs its own nonce.
			'rocketDismissNonce' => wp_create_nonce( 'kukie_dismiss_wp_rocket_notice' ),
			// Strings admin.js renders itself (it reads these with inline
			// English fallbacks, so a stale cached script degrades safely).
			'i18n'             => $this->js_strings(),
		] );
	}

	/**
	 * Every string admin.js renders, translated here so xgettext finds them.
	 * Interval and history labels are whole phrases per value, never a number
	 * glued to a word, so no language has to fit an English plural.
	 *
	 * @since 1.9.0 (split out of enqueue_assets())
	 * @return array<string, string>
	 */
	private function js_strings(): array {
		return [
			'networkError'      => __( "Couldn't reach the server. Check your internet connection and try again.", 'kukie-cookie-consent' ),
			'saveDisabled'      => __( "Couldn't load the settings, so saving is turned off. Reload the page to try again.", 'kukie-cookie-consent' ),
			'conflictTitle'     => __( 'Replace changes made elsewhere?', 'kukie-cookie-consent' ),
			'conflictBody'      => __( 'These settings were changed in the Kukie.io dashboard or another window after you opened this page. Saving replaces those changes with yours. To see their changes instead, reload the page.', 'kukie-cookie-consent' ),
			'conflictReload'    => __( 'Reload page', 'kukie-cookie-consent' ),
			'conflictOverwrite' => __( 'Save my changes', 'kukie-cookie-consent' ),
			'cancel'            => __( 'Cancel', 'kukie-cookie-consent' ),
			'couldNotLoad'      => __( "Couldn't load the settings. Reload the page to try again.", 'kukie-cookie-consent' ),
			'failedToSave'      => __( "Couldn't save the settings. Try again.", 'kukie-cookie-consent' ),
			'on'                => __( 'On', 'kukie-cookie-consent' ),
			'off'               => __( 'Off', 'kukie-cookie-consent' ),
			'notInPlan'         => __( 'Not in plan', 'kukie-cookie-consent' ),
			'verified'          => __( 'Verified', 'kukie-cookie-consent' ),
			'notVerified'       => __( 'Not verified', 'kukie-cookie-consent' ),
			/* translators: %s: date and time */
			'verifiedOn'        => __( 'Verified on %s', 'kukie-cookie-consent' ),
			'verifiedFound'     => __( 'Banner script found on your site.', 'kukie-cookie-consent' ),
			'notFound'          => __( "Couldn't find the banner script on your site.", 'kukie-cookie-consent' ),
			'noDataYet'         => __( 'No consents yet', 'kukie-cookie-consent' ),
			'noScansYet'        => __( 'No scans yet', 'kukie-cookie-consent' ),
			'accepted'          => __( 'Accepted', 'kukie-cookie-consent' ),
			'rejected'          => __( 'Rejected', 'kukie-cookie-consent' ),
			'custom'            => __( 'Custom', 'kukie-cookie-consent' ),
			'trial'             => __( 'Trial', 'kukie-cookie-consent' ),
			/* translators: %s: the date the free trial ends */
			'trialEnds'         => __( 'Trial ends %s', 'kukie-cookie-consent' ),
			'disconnectTitle'   => __( 'Disconnect from Kukie.io?', 'kukie-cookie-consent' ),
			'disconnectBody'    => __( 'The consent banner disappears from this site straight away, and nothing is blocked until you connect again. Your settings, scans and consent records stay in your Kukie.io account.', 'kukie-cookie-consent' ),
			'disconnectConfirm' => __( 'Disconnect', 'kukie-cookie-consent' ),
			'disconnecting'     => __( 'Disconnecting…', 'kukie-cookie-consent' ),
			'disconnectLabel'   => __( 'Disconnect from Kukie.io', 'kukie-cookie-consent' ),
			'failedDisconnect'  => __( "Couldn't disconnect. Try again.", 'kukie-cookie-consent' ),
			'a11yNoBlock'       => __( "Couldn't load the accessibility widget settings from Kukie.io. Try again in a few minutes.", 'kukie-cookie-consent' ),
			/* translators: %s: plan name */
			'a11yRequiredPlan'  => __( 'The accessibility widget is available on the %s plan and above. Upgrade to turn it on.', 'kukie-cookie-consent' ),
			'a11yNotIncluded'   => __( 'The accessibility widget is not included in your plan.', 'kukie-cookie-consent' ),
			'a11yStillOn'       => __( 'This site still has the widget turned on from an earlier plan. Visitors do not see it until your plan includes it again. The setting is kept, so there is nothing to set up again after you upgrade.', 'kukie-cookie-consent' ),
			/* translators: %s: plan name */
			'upgradeTo'         => __( 'Upgrade to %s', 'kukie-cookie-consent' ),
			'seePlans'          => __( 'See plans on Kukie.io', 'kukie-cookie-consent' ),
			'autoDetect'        => __( 'Auto-detect (recommended)', 'kukie-cookie-consent' ),
			'checkingAgain'     => __( 'Checking…', 'kukie-cookie-consent' ),
			/* translators: 1: number of selected items, 2: number of items */
			'gridCount'         => __( '%1$s of %2$s selected', 'kukie-cookie-consent' ),
			'invalidKey'        => __( 'Paste the 64-character API key from your site on Kukie.io.', 'kukie-cookie-consent' ),
			'connectFailed'     => __( "Couldn't connect to Kukie.io. Check the API key and try again.", 'kukie-cookie-consent' ),
			'showKey'           => __( 'Show API key', 'kukie-cookie-consent' ),
			'hideKey'           => __( 'Hide API key', 'kukie-cookie-consent' ),
			'dashboardError'    => __( "Couldn't load the dashboard. Try again in a minute.", 'kukie-cookie-consent' ),
			'scanStartFailed'   => __( "Couldn't start the scan. Try again.", 'kukie-cookie-consent' ),
			'scanPending'       => __( 'Waiting to start', 'kukie-cookie-consent' ),
			'scanRunning'       => __( 'Scanning', 'kukie-cookie-consent' ),
			'scanCompleted'     => __( 'Completed', 'kukie-cookie-consent' ),
			'scanFailed'        => __( 'Failed', 'kukie-cookie-consent' ),
			'scanCancelled'     => __( 'Cancelled', 'kukie-cookie-consent' ),
			/* translators: 1: pages scanned so far, 2: pages in the scan */
			'scanProgress'      => __( 'Scanning page %1$s of %2$s', 'kukie-cookie-consent' ),
			'copy'              => __( 'Copy', 'kukie-cookie-consent' ),
			'copied'            => __( 'Copied', 'kukie-cookie-consent' ),
			'copyFailed'        => __( "Couldn't copy. Select the text and copy it yourself.", 'kukie-cookie-consent' ),
			// Uptime monitoring (1.9.0)
			'uptimeUp'          => __( 'Up', 'kukie-cookie-consent' ),
			'uptimeDown'        => __( 'Down', 'kukie-cookie-consent' ),
			'uptimePending'     => __( 'Waiting for the first check', 'kukie-cookie-consent' ),
			'uptimeBlocked'     => __( 'Blocked by bot protection', 'kukie-cookie-consent' ),
			'uptimePaused'      => __( 'Paused', 'kukie-cookie-consent' ),
			'uptimePausedTitle' => __( 'Uptime monitoring is paused', 'kukie-cookie-consent' ),
			'none'              => __( 'None', 'kukie-cookie-consent' ),
			'uptimeUpTitle'     => __( 'Your site is up', 'kukie-cookie-consent' ),
			'uptimeDownTitle'   => __( 'Your site is down', 'kukie-cookie-consent' ),
			'uptimePendingTitle' => __( 'Waiting for the first check', 'kukie-cookie-consent' ),
			'uptimePendingBody' => __( 'The first check runs within a minute. Reload this page to see the result.', 'kukie-cookie-consent' ),
			'uptimeBlockedTitle' => __( "Your site's bot protection is blocking the check", 'kukie-cookie-consent' ),
			'uptimeBlockedBody' => __( 'Visitors may be fine, but Kukie.io cannot see the page. Add the checker below to the allow list of your firewall or security plugin.', 'kukie-cookie-consent' ),
			'uptimeOffTitle'    => __( 'Uptime monitoring is off', 'kukie-cookie-consent' ),
			'uptimeOffBody'     => __( 'Turn on Monitor this site below to start checking it.', 'kukie-cookie-consent' ),
			'uptimeFrozenBody'  => __( 'Checks are paused because this site is frozen on Kukie.io.', 'kukie-cookie-consent' ),
			'uptimeBillingBody' => __( 'Checks are paused until the subscription is active again.', 'kukie-cookie-consent' ),
			/* translators: %s: relative time, for example "2 minutes ago" */
			'uptimeLastCheck'   => __( 'Last checked %s', 'kukie-cookie-consent' ),
			/* translators: %s: relative time, for example "3 hours ago" */
			'uptimeDownSince'   => __( 'Went down %s', 'kukie-cookie-consent' ),
			/* translators: %s: response time in milliseconds */
			'ms'                => __( '%s ms', 'kukie-cookie-consent' ),
			/* translators: %s: date */
			'sslExpires'        => __( 'Expires %s', 'kukie-cookie-consent' ),
			'notAvailable'      => __( 'Not available', 'kukie-cookie-consent' ),
			'noDataShort'       => __( 'No data yet', 'kukie-cookie-consent' ),
			'ongoing'           => __( 'Ongoing', 'kukie-cookie-consent' ),
			/* translators: %s: plan name */
			'planAndAbove'      => __( '%s plan and above', 'kukie-cookie-consent' ),
			/* translators: %s: plan name */
			'uptimeRequiredPlan' => __( 'Uptime monitoring is available on the %s plan and above. Upgrade to turn it on.', 'kukie-cookie-consent' ),
			'uptimeNotIncluded' => __( 'Uptime monitoring is not included in your plan.', 'kukie-cookie-consent' ),
			'uptimeNoService'   => __( "Couldn't load uptime monitoring from Kukie.io. Try again in a few minutes.", 'kukie-cookie-consent' ),
			/* translators: 1: the site's domain, 2: the same domain with www */
			'uptimeUrlHint'     => __( 'Must be on %1$s or %2$s. Usually your homepage.', 'kukie-cookie-consent' ),
			'uptimeUrlInvalid'  => __( 'Enter a full web address that starts with https:// or http://.', 'kukie-cookie-consent' ),
			'interval60'        => __( 'Every minute', 'kukie-cookie-consent' ),
			'interval180'       => __( 'Every 3 minutes', 'kukie-cookie-consent' ),
			'interval300'       => __( 'Every 5 minutes', 'kukie-cookie-consent' ),
			'interval900'       => __( 'Every 15 minutes', 'kukie-cookie-consent' ),
			'interval1800'      => __( 'Every 30 minutes', 'kukie-cookie-consent' ),
			'interval3600'      => __( 'Every hour', 'kukie-cookie-consent' ),
			/* translators: %s: number of minutes (used only for a cadence added after this release) */
			'intervalMinutes'   => __( 'Every %s minutes', 'kukie-cookie-consent' ),
			'history30'         => __( '30 days', 'kukie-cookie-consent' ),
			'history90'         => __( '90 days', 'kukie-cookie-consent' ),
			'history365'        => __( '1 year', 'kukie-cookie-consent' ),
			/* translators: %s: number of days (used only for a history length added after this release) */
			'historyDays'       => __( '%s days', 'kukie-cookie-consent' ),
			/* translators: %s: number of seconds */
			'durSeconds'        => __( '%s s', 'kukie-cookie-consent' ),
			/* translators: %s: number of minutes */
			'durMinutes'        => __( '%s min', 'kukie-cookie-consent' ),
			/* translators: 1: hours, 2: minutes */
			'durHours'          => __( '%1$s h %2$s min', 'kukie-cookie-consent' ),
			/* translators: 1: days, 2: hours */
			'durDays'           => __( '%1$s d %2$s h', 'kukie-cookie-consent' ),
			'causeTimeout'      => __( 'The site did not respond in time.', 'kukie-cookie-consent' ),
			'causeConnect'      => __( 'The connection could not be made (refused or dropped).', 'kukie-cookie-consent' ),
			'causeDns'          => __( 'The domain name could not be found (DNS).', 'kukie-cookie-consent' ),
			'causeTls'          => __( 'The TLS (SSL) handshake failed. The certificate may be expired or invalid.', 'kukie-cookie-consent' ),
			/* translators: %s: HTTP status code, for example 503 */
			'causeHttp'         => __( 'The site answered with HTTP %s.', 'kukie-cookie-consent' ),
			'causeHttpGeneric'  => __( 'The site answered with an error status.', 'kukie-cookie-consent' ),
			'causeRedirect'     => __( 'The site redirected too many times.', 'kukie-cookie-consent' ),
			'causeOversized'    => __( 'The page was larger than the 5 MB limit.', 'kukie-cookie-consent' ),
			'causeBlocked'      => __( 'The address points to a private network and cannot be checked from outside.', 'kukie-cookie-consent' ),
			'causeChallenge'    => __( 'The site answered with a bot-protection page instead of its content.', 'kukie-cookie-consent' ),
			'causeTransport'    => __( 'A network error interrupted the check.', 'kukie-cookie-consent' ),
			'causeUnknown'      => __( 'The site could not be reached.', 'kukie-cookie-consent' ),
			'uptimeSaved'       => __( 'Uptime monitoring settings saved', 'kukie-cookie-consent' ),
			// Preferences modal tab (1.9.0)
			'modalNoBlock'      => __( "Couldn't load the preferences modal settings from Kukie.io. Try again in a few minutes.", 'kukie-cookie-consent' ),
			'modalWidthInvalid' => __( 'Enter a width from 320 to 1200 pixels, or leave it empty for 600 pixels.', 'kukie-cookie-consent' ),
			'btnReject'         => __( 'Reject all', 'kukie-cookie-consent' ),
			'btnSave'           => __( 'Save preferences', 'kukie-cookie-consent' ),
			'btnAccept'         => __( 'Accept all', 'kukie-cookie-consent' ),
			'slotLeft'          => __( 'Left', 'kukie-cookie-consent' ),
			'slotMiddle'        => __( 'Middle', 'kukie-cookie-consent' ),
			'slotRight'         => __( 'Right', 'kukie-cookie-consent' ),
			'slotTopLeft'       => __( 'Top left', 'kukie-cookie-consent' ),
			'slotTopRight'      => __( 'Top right', 'kukie-cookie-consent' ),
			'slotBelow'         => __( 'Full width below', 'kukie-cookie-consent' ),
			/* translators: %s: button name, for example "Reject all" */
			'moveEarlier'       => __( 'Move %s earlier', 'kukie-cookie-consent' ),
			/* translators: %s: button name, for example "Reject all" */
			'moveLater'         => __( 'Move %s later', 'kukie-cookie-consent' ),
			/* translators: 1: button name, 2: position name such as "Left" */
			'movedTo'           => __( '%1$s moved to %2$s', 'kukie-cookie-consent' ),
			/* translators: %s: "on" or "off", the banner's own overlay setting */
			'overlayFollow'     => __( "Same as the banner (currently %s)", 'kukie-cookie-consent' ),
			'onLower'           => __( 'on', 'kukie-cookie-consent' ),
			'offLower'          => __( 'off', 'kukie-cookie-consent' ),
		];
	}

	/**
	 * Marker appended to every link that opens a new tab: the external-link
	 * dashicon plus screen-reader text, so sighted and non-sighted users both
	 * know the link leaves the current page. Sanitised here so templates can
	 * echo it directly.
	 *
	 * @since 1.8.0
	 */
	public static function new_tab_marker(): string {
		return wp_kses(
			'<span class="dashicons dashicons-external kukie-ext" aria-hidden="true"></span>'
			. '<span class="screen-reader-text">' . esc_html__( '(opens in a new tab)', 'kukie-cookie-consent' ) . '</span>',
			[
				'span' => [ 'class' => [], 'aria-hidden' => [] ],
			]
		);
	}

	/**
	 * Cache-busting version for an admin asset: the plugin version plus the
	 * file's modification time. A bare KUKIE_VERSION only changes on a
	 * release, so a re-uploaded build of the SAME version kept serving the
	 * browser's (or a CDN's) cached copy of admin.css/admin.js - the 1.8.0
	 * review round showed a stale stylesheet for a whole session.
	 *
	 * @since 1.8.0
	 */
	private static function asset_version( string $relative_path ): string {
		$path  = KUKIE_PLUGIN_DIR . $relative_path;
		$mtime = file_exists( $path ) ? filemtime( $path ) : false;

		return $mtime ? KUKIE_VERSION . '.' . $mtime : KUKIE_VERSION;
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
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( $this->plugin->is_connected() ) {
			return;
		}

		// A stored-but-undecryptable key gets the more specific reconnect
		// notice instead of the generic "not connected" one.
		if ( $this->plugin->api_key_decrypt_failed() ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen && str_contains( $screen->id, 'kukie' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s <a href="%s">%s</a></p></div>',
			esc_html__( 'Kukie.io is not connected, so this site shows no consent banner.', 'kukie-cookie-consent' ),
			esc_url( admin_url( 'admin.php?page=' . self::PAGE_CONNECT ) ),
			esc_html__( 'Connect to Kukie.io', 'kukie-cookie-consent' )
		);
	}

	/**
	 * Shown when an API key is stored but can no longer be decrypted -
	 * typically after the site's security keys/salts were rotated
	 * (wp config shuffle-salts) or the database was cloned from another
	 * environment. Reconnecting re-encrypts the key with the current salts.
	 *
	 * @since 1.7.0
	 */
	public function reconnect_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! $this->plugin->api_key_decrypt_failed() ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p><strong>%s</strong> %s <a href="%s">%s</a></p></div>',
			esc_html__( 'Kukie.io:', 'kukie-cookie-consent' ),
			esc_html__( "The stored API key can no longer be read. This happens after the site's security keys (salts) change or the database is copied from another site. Your consent banner keeps working. To restore stats, scans and settings, reconnect with your API key.", 'kukie-cookie-consent' ),
			esc_url( admin_url( 'admin.php?page=' . self::PAGE_CONNECT ) ),
			esc_html__( 'Reconnect', 'kukie-cookie-consent' )
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
			'<div class="notice notice-error"><p><strong>%s</strong> %s <a href="%s" target="_blank" rel="noopener noreferrer">%s%s</a></p></div>',
			esc_html__( 'Kukie.io:', 'kukie-cookie-consent' ),
			esc_html__( 'The API key is no longer valid, so stats, scans and settings sync are paused. Your consent banner keeps working.', 'kukie-cookie-consent' ),
			esc_url( $key_url ),
			esc_html__( 'Generate a new API key', 'kukie-cookie-consent' ),
			self::new_tab_marker() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses-sanitised in the helper
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

		// The dismiss click is handled by admin.js (enqueued on every Kukie
		// page, which is the only place this notice renders) - no inline
		// <script> in PHP output since 1.8.0.
		printf(
			'<div class="notice notice-warning kukie-notice" id="kukie-wp-rocket-notice"><p>'
			. '<strong>%s</strong> %s'
			. '</p><ul class="kukie-notice-list">%s</ul>'
			. '<p><a href="%s" class="button button-small">%s</a> '
			. '<a href="%s" target="_blank" rel="noopener noreferrer" class="kukie-notice-link">%s%s</a>'
			. '<button type="button" class="button-link kukie-dismiss-btn">%s</button>'
			. '</p></div>',
			esc_html__( 'Kukie.io and WP Rocket:', 'kukie-cookie-consent' ),
			esc_html__( 'WP Rocket might delay or change the banner script. To keep the banner loading correctly, add cdn.kukie.io to these WP Rocket settings:', 'kukie-cookie-consent' ),
			$issue_list, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every item escaped above
			esc_url( $rocket_settings_url ),
			esc_html__( 'Open WP Rocket settings', 'kukie-cookie-consent' ),
			esc_url( $help_url ),
			esc_html__( 'Learn more about WP Rocket and Kukie.io', 'kukie-cookie-consent' ),
			self::new_tab_marker(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses-sanitised in the helper
			esc_html__( 'Dismiss', 'kukie-cookie-consent' )
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

	/**
	 * The Consent banner page: one page, three tabs (design / gcm / uet). The
	 * former per-page templates render as tab partials - only the active
	 * tab's partial is included, so each partial's page-init hook in admin.js
	 * (keyed on its root element id) fires for exactly one tab.
	 *
	 * @since 1.8.0
	 */
	public function render_banner_page(): void {
		require KUKIE_PLUGIN_DIR . 'templates/admin-banner.php';
	}

	/**
	 * @since 1.8.0
	 */
	public function render_accessibility_page(): void {
		require KUKIE_PLUGIN_DIR . 'templates/admin-accessibility.php';
	}

	/**
	 * @since 1.9.0
	 */
	public function render_uptime_page(): void {
		require KUKIE_PLUGIN_DIR . 'templates/admin-uptime.php';
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
			wp_send_json_error( [ 'message' => __( 'You do not have permission to change Kukie.io settings.', 'kukie-cookie-consent' ) ], 403 );
		}

		$api_key = isset( $_POST['api_key'] )
			? preg_replace( '/[^a-zA-Z0-9]/', '', sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) )
			: '';

		if ( strlen( $api_key ) !== 64 ) {
			wp_send_json_error( [ 'message' => __( 'Paste the 64-character API key from your site on Kukie.io.', 'kukie-cookie-consent' ) ] );
		}

		$client   = new Kukie_Api_Client( $api_key );
		$response = $client->post( '/connect' );

		if ( ! $response['success'] ) {
			wp_send_json_error( [ 'message' => $response['error'] ?? __( "Couldn't connect to Kukie.io. Check the API key and try again.", 'kukie-cookie-consent' ) ] );
		}

		$data = $response['data'];

		$encrypted_key = Kukie_Encryption::encrypt( $api_key );
		if ( $encrypted_key === '' ) {
			wp_send_json_error( [ 'message' => __( "Couldn't store the API key securely on this server. Ask your host to turn on OpenSSL for PHP.", 'kukie-cookie-consent' ) ] );
		}

		// Preserve a deliberate manual/body placement across a SAME-SITE
		// reconnect (the 1.7.0 reconnect notices funnel connected installs
		// through here). A fresh connect, or a connect to a DIFFERENT Kukie
		// site, defaults to 'head': keeping 'manual' there would leave the
		// theme's hard-coded snippet serving the old site's bundle with
		// nothing injected for the new one. Re-validate so a corrupted
		// stored value cannot survive.
		$stored_site_key = (string) $this->plugin->get_option( 'site_key', '' );
		$new_site_key    = sanitize_text_field( $data['site_key'] ?? '' );
		$script_position = ( $stored_site_key !== '' && $stored_site_key === $new_site_key )
			? $this->sanitize_script_position( $this->plugin->get_option( 'script_position', 'head' ) )
			: 'head';

		$this->plugin->update_options( [
			'api_key_encrypted' => $encrypted_key,
			'api_key_valid'     => true,
			'site_key'          => $new_site_key,
			'site_id'           => absint( $data['site_id'] ?? 0 ),
			'domain'            => sanitize_text_field( $data['domain'] ?? '' ),
			'organisation'      => sanitize_text_field( $data['organisation'] ?? '' ),
			'plan_name'         => sanitize_text_field( $data['plan']['name'] ?? 'Free' ),
			'embed_url'         => esc_url_raw( $data['embed_url'] ?? '' ),
			// Never store '' here: a present-but-empty key defeats the
			// 'https://app.kukie.io' default in every get_option() reader
			// and renders dashboard links as href="".
			'dashboard_url'     => esc_url_raw( $data['dashboard_url'] ?? '' ) ?: 'https://app.kukie.io',
			'banner_enabled'    => (bool) ( $data['banner_enabled'] ?? true ),
			'connected_at'      => current_time( 'c' ),
			'script_position'   => $script_position,
			'config_version'    => (string) time(),
			// A connected install has no use for the one-time activation
			// redirect; clear the marker so a post-disconnect page load
			// cannot leave it armed for a much later reactivation.
			'first_activation'  => false,
		] );

		delete_transient( 'kukie_dashboard_data' );
		delete_transient( 'kukie_settings_cache' );

		wp_send_json_success( [
			'message'      => __( 'Connected to Kukie.io', 'kukie-cookie-consent' ),
			'organisation' => sanitize_text_field( $data['organisation'] ?? '' ),
			'plan'         => sanitize_text_field( $data['plan']['name'] ?? '' ),
			'domain'       => sanitize_text_field( $data['domain'] ?? '' ),
			'redirect'     => admin_url( 'admin.php?page=kukie' ),
		] );
	}

	public function ajax_disconnect(): void {
		check_ajax_referer( 'kukie_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to change Kukie.io settings.', 'kukie-cookie-consent' ) ], 403 );
		}

		$client = $this->plugin->get_api_client();
		if ( $client ) {
			$client->post( '/disconnect' );
		}

		delete_option( 'kukie_settings' );
		delete_transient( 'kukie_dashboard_data' );
		delete_transient( 'kukie_settings_cache' );

		wp_send_json_success( [
			'message'  => __( 'Disconnected from Kukie.io', 'kukie-cookie-consent' ),
			'redirect' => admin_url( 'admin.php?page=kukie-connect' ),
		] );
	}

	public function ajax_get_status(): void {
		check_ajax_referer( 'kukie_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to change Kukie.io settings.', 'kukie-cookie-consent' ) ], 403 );
		}

		// is_array, not !== false: a corrupted/scalar payload in the transient
		// must fall through to a fresh fetch, never be served as status data.
		$cached = get_transient( 'kukie_dashboard_data' );
		if ( is_array( $cached ) ) {
			wp_send_json_success( $cached );
		}

		$client = $this->plugin->get_api_client();
		if ( ! $client ) {
			wp_send_json_error( [ 'message' => __( 'This site is not connected to Kukie.io.', 'kukie-cookie-consent' ) ] );
		}

		$response = $client->get( '/status' );

		if ( ! $response['success'] ) {
			wp_send_json_error( [ 'message' => $response['error'] ] );
		}

		// Refresh the admin-bar dot from the fresh /status payload: it is the
		// only path that sees dashboard-side (app.kukie.io) toggles. The
		// transient short-circuit above means this runs at most every 5
		// minutes - acceptable staleness for the dot; do not shorten or
		// bypass the kukie_dashboard_data cache for it.
		$this->mirror_banner_enabled( $response['data'] );
		$this->mirror_connection_info( $response['data'] );

		// The /status round trip is long enough for a concurrent disconnect
		// to land, and the cached branch above serves this transient before
		// any connection check - never re-create it for a disconnected
		// install, and never cache a non-array body.
		if ( is_array( $response['data'] ) && $this->plugin->settings_still_connected() ) {
			set_transient( 'kukie_dashboard_data', $response['data'], 5 * MINUTE_IN_SECONDS );
		}

		wp_send_json_success( $response['data'] );
	}

	public function ajax_get_settings(): void {
		check_ajax_referer( 'kukie_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to change Kukie.io settings.', 'kukie-cookie-consent' ) ], 403 );
		}

		// fresh=1 bypasses the 10-minute settings cache. The Accessibility
		// widget page sends it on load: its locked/unlocked state follows the
		// PLAN flag, which changes on the Kukie.io side (an upgrade), and a
		// stale cached "not available" after an upgrade is the one case a
		// user cannot reason about. One uncached GET per page load.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- check_ajax_referer() ran above
		$fresh = ! empty( $_POST['fresh'] );

		// is_array, not !== false: a non-array payload would fatal on the
		// string-offset assignments below and 500 this endpoint until the
		// transient expired.
		$cached = $fresh ? false : get_transient( 'kukie_settings_cache' );
		if ( is_array( $cached ) ) {
			$cached['script_position']    = $this->plugin->get_option( 'script_position', 'head' );
			$cached['force_language']     = $this->plugin->get_option( 'force_language', 'auto' );
			$cached['disable_for_admins'] = (bool) $this->plugin->get_option( 'disable_for_admins', false );
			wp_send_json_success( $cached );
		}

		$client = $this->plugin->get_api_client();
		if ( ! $client ) {
			wp_send_json_error( [ 'message' => __( 'This site is not connected to Kukie.io.', 'kukie-cookie-consent' ) ] );
		}

		$response = $client->get( '/settings' );

		if ( ! $response['success'] ) {
			wp_send_json_error( [ 'message' => $response['error'] ] );
		}

		// Same discipline as send_settings_saved()'s transient write: the GET
		// slept long enough for a concurrent disconnect to land, and the
		// cached branch above serves this transient before any connection
		// check - never re-create it for a disconnected install, and never
		// cache a non-array body (the cached branch would fatal on it).
		if ( is_array( $response['data'] ) && $this->plugin->settings_still_connected() ) {
			set_transient( 'kukie_settings_cache', $response['data'], 10 * MINUTE_IN_SECONDS );
		}

		$data                       = is_array( $response['data'] ) ? $response['data'] : [];
		$data['script_position']    = $this->plugin->get_option( 'script_position', 'head' );
		$data['force_language']     = $this->plugin->get_option( 'force_language', 'auto' );
		$data['disable_for_admins'] = (bool) $this->plugin->get_option( 'disable_for_admins', false );

		wp_send_json_success( $data );
	}

	/**
	 * The server-side config_version the admin JS loaded via GET /settings
	 * and posted back with this save. Distinct from the LOCAL 'config_version'
	 * option, which is a (string) time() CDN cache-buster. Null means the JS
	 * did not send one (the PUT then takes the server's last-write-wins
	 * path); 0 is a real, lockable version - the server compares with
	 * array_key_exists + strict equality, so it must not be dropped.
	 *
	 * Callers have already passed check_ajax_referer(), hence the phpcs ignore.
	 *
	 * @since 1.7.0
	 */
	private function posted_config_version(): ?int {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		return isset( $_POST['config_version'] ) ? absint( wp_unslash( $_POST['config_version'] ) ) : null;
	}

	/**
	 * Shared pipeline for the four settings save handlers: attach the posted
	 * config_version (optimistic lock), require a connected client, PUT to
	 * the API, and send the JSON error on failure. Returns only on success;
	 * callers do any local mirroring, then finish with send_settings_saved().
	 *
	 * @since 1.7.0
	 * @return array{0: Kukie_Api_Client, 1: ?int, 2: ?array} The client, the config_version that was sent and the PUT's response body.
	 */
	private function put_settings_or_die( array $api_data ): array {
		$config_version = $this->posted_config_version();
		if ( $config_version !== null ) {
			$api_data['config_version'] = $config_version;
		}

		$client = $this->plugin->get_api_client();
		if ( ! $client ) {
			wp_send_json_error( [ 'message' => __( 'This site is not connected to Kukie.io.', 'kukie-cookie-consent' ) ] );
		}

		$response = $client->put( '/settings', $api_data );

		if ( ! $response['success'] ) {
			$this->send_put_settings_error( $response );
		}

		return [ $client, $config_version, is_array( $response['data'] ) ? $response['data'] : null ];
	}

	/**
	 * Send the JSON error for a failed PUT /settings. A 409 (optimistic-lock
	 * conflict: the config changed elsewhere since the page loaded) is marked
	 * with code 'version_conflict' and carries the server's current version,
	 * so the admin JS can inform the user and offer a retry.
	 *
	 * @since 1.7.0
	 */
	private function send_put_settings_error( array $response ): void {
		// A plan-gated write (accessibility widget on a plan without it) is a
		// structured 403 the page turns into an upgrade CTA instead of a
		// bare error. Nothing was persisted server-side.
		$this->send_plan_gate_error( $response );

		if ( $response['status'] === 409 ) {
			delete_transient( 'kukie_settings_cache' );

			wp_send_json_error( [
				'message'         => $response['error'] ?? __( 'These settings were changed elsewhere after this page was loaded.', 'kukie-cookie-consent' ),
				'code'            => 'version_conflict',
				'current_version' => absint( $response['data']['current_version'] ?? 0 ),
			] );
		}

		wp_send_json_error( [ 'message' => $response['error'] ] );
	}

	/**
	 * Forward a plan-gated 403 (`code: plan_upgrade_required`) as the
	 * structured error the Accessibility widget and Uptime monitoring pages
	 * render as an upgrade call to action. Returns without sending for any
	 * other response, so callers fall through to their own error handling.
	 *
	 * @since 1.9.0 (extracted from send_put_settings_error())
	 */
	private function send_plan_gate_error( array $response ): void {
		if ( $response['status'] !== 403 || ! is_array( $response['data'] ) || ( $response['data']['code'] ?? '' ) !== 'plan_upgrade_required' ) {
			return;
		}

		delete_transient( 'kukie_settings_cache' );

		$upgrade_url = esc_url_raw( (string) ( $response['data']['upgrade_url'] ?? '' ) );

		wp_send_json_error( [
			'message'       => $response['error'] ?? __( 'This feature is not included in your plan.', 'kukie-cookie-consent' ),
			'code'          => 'plan_upgrade_required',
			'feature'       => sanitize_key( (string) ( $response['data']['feature'] ?? '' ) ),
			'required_plan' => sanitize_text_field( (string) ( $response['data']['required_plan'] ?? '' ) ),
			'upgrade_url'   => $upgrade_url !== '' && str_starts_with( $upgrade_url, 'https://' ) ? $upgrade_url : 'https://app.kukie.io/billing',
		] );
	}

	/**
	 * Finalise a successful PUT /settings: bump the local CDN cache-buster,
	 * refresh the settings cache from the server (the PUT bumped the server's
	 * config_version), and return the new version so the admin JS can save
	 * again from the same page without a spurious conflict.
	 *
	 * @since 1.7.0
	 */
	private function send_settings_saved( Kukie_Api_Client $client, string $message, ?int $sent_version = null, ?array $put_data = null ): void {
		// Update config version for cache-busting (forces browser to fetch a
		// fresh CDN bundle) - but only into an install still connected after
		// the PUT round trip (a concurrent disconnect must stay
		// disconnected; see Kukie_Plugin::settings_still_connected()).
		// Guarding the WRITE never guards the RESPONSE: the JS still
		// receives a usable config_version below whatever happened locally,
		// or the very next save would produce a spurious conflict.
		if ( $this->plugin->settings_still_connected() ) {
			$this->plugin->update_options( [ 'config_version' => (string) time() ] );
		}

		delete_transient( 'kukie_settings_cache' );

		$payload = [ 'message' => $message ];

		// Since the 1 October 2026 service update the PUT itself answers with
		// the version it produced, which saves the follow-up GET round trip
		// on every save. The settings cache stays dropped (above), so the
		// next page load reads fresh values; the admin-bar mirror follows
		// the local commit the caller already made and the next /status.
		if ( is_array( $put_data ) && isset( $put_data['config_version'] ) && is_numeric( $put_data['config_version'] ) ) {
			$payload['config_version'] = (int) $put_data['config_version'];
			wp_send_json_success( $payload );
		}

		$refresh = $client->get( '/settings' );
		if ( $refresh['success'] && isset( $refresh['data']['config_version'] ) ) {
			$payload['config_version'] = (int) $refresh['data']['config_version'];

			// The refresh is authoritative for the admin-bar banner state:
			// it reflects dashboard-side toggles too, not just this save.
			$this->mirror_banner_enabled( $refresh['data'] );

			// Re-creating the settings cache must not outlive a concurrent
			// disconnect either (ajax_get_settings serves this transient
			// before any connection check). The helper's verdict is fresh
			// as of the GET round trip above, whichever intermediate call
			// consumed the stale mark.
			if ( $this->plugin->settings_still_connected() ) {
				set_transient( 'kukie_settings_cache', $refresh['data'], 10 * MINUTE_IN_SECONDS );
			}
		} elseif ( $sent_version !== null ) {
			// The refresh failed, but the optimistic lock passed, so the
			// server deterministically bumped the version we sent by one
			// (PluginController::updateSettings). Returning that instead of
			// nothing keeps the JS off the stale version, which would
			// produce a spurious conflict on the very next save.
			$payload['config_version'] = $sent_version + 1;
		}

		wp_send_json_success( $payload );
	}

	/**
	 * Coerce a script_position value to the placement whitelist, defaulting
	 * to 'head'. Single source of truth for the whitelist - used by both the
	 * settings save and the (re)connect path so the two cannot drift.
	 *
	 * @since 1.7.1
	 */
	private function sanitize_script_position( mixed $value ): string {
		return in_array( $value, [ 'head', 'body', 'manual' ], true ) ? $value : 'head';
	}

	/**
	 * Mirror the server's banner_enabled into the local option that drives
	 * the admin-bar status dot. Only ever called with data from a SUCCESSFUL
	 * server response - a failed response must leave the mirror untouched
	 * (the 1.7.0 discipline from the 343 fix).
	 *
	 * @since 1.7.1
	 */
	private function mirror_banner_enabled( mixed $data ): void {
		if ( ! is_array( $data ) || ! array_key_exists( 'banner_enabled', $data ) ) {
			return;
		}

		$value = (bool) $data['banner_enabled'];

		// $data came from an HTTP round trip, so the connected verdict below
		// is a fresh post-round-trip read - never write into a
		// just-disconnected (deleted/empty) install, nor when the value is
		// unchanged (the common polling case).
		if ( ! $this->plugin->settings_still_connected() ) {
			return;
		}
		if ( $this->plugin->get_option( 'banner_enabled' ) === $value ) {
			return;
		}

		$this->plugin->update_option( 'banner_enabled', $value );
	}

	/**
	 * Mirror the connection details /status carries (plan name, organisation,
	 * domain) into the stored option that renders the Settings page's
	 * Connection card. Those values were written once at connect time, so a
	 * plan change or an organisation rename on Kukie.io left the card stale
	 * until a reconnect (operator report, 2026-09-02). Same discipline as
	 * mirror_banner_enabled(): only from a SUCCESSFUL response, only into an
	 * install still connected after the round trip, only when changed.
	 *
	 * @since 1.8.0
	 */
	private function mirror_connection_info( mixed $data ): void {
		if ( ! is_array( $data ) || ! $this->plugin->settings_still_connected() ) {
			return;
		}

		$incoming = [];
		if ( isset( $data['plan']['name'] ) && is_string( $data['plan']['name'] ) && $data['plan']['name'] !== '' ) {
			$incoming['plan_name'] = sanitize_text_field( $data['plan']['name'] );
		}
		if ( isset( $data['organisation'] ) && is_string( $data['organisation'] ) && $data['organisation'] !== '' ) {
			$incoming['organisation'] = sanitize_text_field( $data['organisation'] );
		}
		if ( isset( $data['domain'] ) && is_string( $data['domain'] ) && $data['domain'] !== '' ) {
			$incoming['domain'] = sanitize_text_field( $data['domain'] );
		}

		$changed = [];
		foreach ( $incoming as $key => $value ) {
			if ( $this->plugin->get_option( $key ) !== $value ) {
				$changed[ $key ] = $value;
			}
		}

		if ( $changed !== [] ) {
			$this->plugin->update_options( $changed );
		}
	}

	/**
	 * Whitelist of language codes accepted by the "Banner language" override
	 * dropdown. `auto` disables the override (detector falls through to
	 * WPML / Polylang / WP core). All other entries are Kukie-format short
	 * codes matching the banner script's translations map.
	 *
	 * This list is an escape hatch for manual override, not the canonical
	 * language catalogue - the full 71-language Kukie set is still honored
	 * via auto-detect. Values must already be Kukie-normalized (lowercase,
	 * hyphen-separated).
	 *
	 * @since 1.6.0
	 * @return string[]
	 */
	private function allowed_force_languages(): array {
		return [
			'auto',
			'en', 'de', 'fr', 'es', 'es-mx', 'es-ar', 'it', 'pt', 'pt-br', 'nl',
			'pl', 'ru', 'tr', 'ja', 'zh-cn', 'zh-tw', 'ar', 'bg',
			'cs', 'da', 'el', 'fi', 'he', 'hu', 'id', 'ko',
			'no', 'ro', 'sk', 'sv', 'th', 'uk', 'vi',
		];
	}

	/**
	 * Shared by the Settings page (banner on/off + script position) and the
	 * Consent banner > Language tab (banner language override, auto-translate,
	 * default + enabled languages) since 1.8.0. PRESENCE-BASED on purpose:
	 * only fields the posting form actually carries are sent to the API or
	 * committed locally, so a save from one page can never reset a field the
	 * other page owns (the pre-1.8.0 handler defaulted every absent field -
	 * banner_enabled to true, enabled_languages to [] - which would have
	 * re-enabled a disabled banner or wiped the language list from the
	 * Settings page once the language fields moved to their own tab).
	 *
	 * enabled_languages is an array, and an EMPTY checkbox list posts no key
	 * at all; the Language tab therefore also posts has_enabled_languages=1,
	 * which is what turns an absent array into "send []" instead of "leave
	 * untouched".
	 */
	public function ajax_save_settings(): void {
		check_ajax_referer( 'kukie_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to change Kukie.io settings.', 'kukie-cookie-consent' ) ], 403 );
		}

		$api_data = [];
		$local    = [];

		// Local-only: script_position (validated here, committed only after
		// the PUT below succeeds).
		if ( isset( $_POST['script_position'] ) ) {
			$local['script_position'] = $this->sanitize_script_position( sanitize_text_field( wp_unslash( $_POST['script_position'] ) ) );
		}

		// Local-only: force_language (WPML/Polylang override dropdown).
		// Invalid values silently fall back to 'auto' so the detector
		// takes over normally.
		if ( isset( $_POST['force_language'] ) ) {
			$force_language = sanitize_text_field( wp_unslash( $_POST['force_language'] ) );
			if ( ! in_array( $force_language, $this->allowed_force_languages(), true ) ) {
				$force_language = 'auto';
			}
			$local['force_language'] = $force_language;
		}

		// Local-only: hide the banner for administrators (page-builder
		// safety, read per request by Kukie_Script_Injector). Never synced -
		// it describes WordPress users, which Kukie.io knows nothing about.
		if ( isset( $_POST['disable_for_admins'] ) ) {
			$local['disable_for_admins'] = rest_sanitize_boolean( $_POST['disable_for_admins'] );
		}

		// API-synced settings
		if ( isset( $_POST['banner_enabled'] ) ) {
			$api_data['banner_enabled'] = rest_sanitize_boolean( $_POST['banner_enabled'] );
		}
		if ( isset( $_POST['auto_translate'] ) ) {
			$api_data['auto_translate'] = rest_sanitize_boolean( $_POST['auto_translate'] );
		}
		if ( isset( $_POST['default_language'] ) ) {
			$api_data['default_language'] = sanitize_text_field( wp_unslash( $_POST['default_language'] ) );
		}
		if ( isset( $_POST['enabled_languages'] ) || ! empty( $_POST['has_enabled_languages'] ) ) {
			$api_data['enabled_languages'] = isset( $_POST['enabled_languages'] ) && is_array( $_POST['enabled_languages'] )
				? array_map( 'sanitize_text_field', wp_unslash( $_POST['enabled_languages'] ) )
				: [];
		}

		[ $client, $config_version, $put_data ] = $this->put_settings_or_die( $api_data );

		// Commit local-only fields and mirror the server state only AFTER the
		// PUT succeeded, so a failed or 409-cancelled save leaves local state
		// (and the admin-bar status dot) unchanged - and only into an install
		// that is still connected, so the commit cannot resurrect a
		// concurrent disconnect that landed during the PUT round trip.
		if ( array_key_exists( 'banner_enabled', $api_data ) ) {
			$local['banner_enabled'] = $api_data['banner_enabled'];
		}
		if ( $local !== [] && $this->plugin->settings_still_connected() ) {
			$this->plugin->update_options( $local );
		}

		$this->send_settings_saved( $client, __( 'Settings saved', 'kukie-cookie-consent' ), $config_version, $put_data );
	}

	public function ajax_save_gcm(): void {
		check_ajax_referer( 'kukie_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to change Kukie.io settings.', 'kukie-cookie-consent' ) ], 403 );
		}

		$api_data = [
			'gcm_v2_enabled' => rest_sanitize_boolean( $_POST['gcm_v2_enabled'] ?? false ),
		];

		// Script blocking lives on the Behaviour tab since 1.8.0; still
		// honoured here when an older form posts it, never defaulted.
		if ( isset( $_POST['auto_block_scripts'] ) ) {
			$api_data['auto_block_scripts'] = rest_sanitize_boolean( $_POST['auto_block_scripts'] );
		}

		[ $client, $config_version, $put_data ] = $this->put_settings_or_die( $api_data );

		$this->send_settings_saved( $client, __( 'Google Consent Mode settings saved', 'kukie-cookie-consent' ), $config_version, $put_data );
	}

	public function ajax_save_uet(): void {
		check_ajax_referer( 'kukie_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to change Kukie.io settings.', 'kukie-cookie-consent' ) ], 403 );
		}

		$api_data = [
			'ms_uet_enabled' => rest_sanitize_boolean( $_POST['ms_uet_enabled'] ?? false ),
		];

		[ $client, $config_version, $put_data ] = $this->put_settings_or_die( $api_data );

		$this->send_settings_saved( $client, __( 'Microsoft UET settings saved', 'kukie-cookie-consent' ), $config_version, $put_data );
	}

	public function ajax_save_banner_design(): void {
		check_ajax_referer( 'kukie_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to change Kukie.io settings.', 'kukie-cookie-consent' ) ], 403 );
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
		if ( ! in_array( $rb_icon, [ 'cookie', 'shield', 'settings', 'fingerprint', 'lock', 'sliders' ], true ) ) {
			$rb_icon = 'cookie';
		}

		$revisit_button = [
			'enabled'    => ! empty( $rb_raw['enabled'] ) && $rb_raw['enabled'] !== '0',
			'position'   => $rb_position,
			'style'      => $rb_style,
			'icon'       => $rb_icon,
			'text'       => sanitize_text_field( $rb_raw['text'] ?? 'Cookie settings' ),
			'color'      => $this->sanitize_banner_color( $rb_raw['color'] ?? '' ),
			'icon_color' => $this->sanitize_banner_color( $rb_raw['icon_color'] ?? '' ),
			'offset_x'   => max( 0, min( 200, absint( $rb_raw['offset_x'] ?? 20 ) ) ),
			'offset_y'   => max( 0, min( 200, absint( $rb_raw['offset_y'] ?? 20 ) ) ),
		];

		$api_data = [
			'layout'         => $layout,
			'position'       => $position,
			'revisit_button' => $revisit_button,
		];

		[ $client, $config_version, $put_data ] = $this->put_settings_or_die( $api_data );

		$this->send_settings_saved( $client, __( 'Banner design saved. Your site shows it within a few minutes.', 'kukie-cookie-consent' ), $config_version, $put_data );
	}

	/**
	 * Coerce a banner colour to something the API accepts: a hex colour
	 * matching the server's validation regex, or '' (the "use default"
	 * sentinel) - never a value that would 422 the whole save. Deliberately
	 * NOT sanitize_hex_color(): that helper rejects 4/8-digit alpha hex,
	 * which the server accepts, so it would silently wipe a dashboard-set
	 * alpha colour on the next plugin-side save.
	 *
	 * @since 1.7.0
	 */
	private function sanitize_banner_color( mixed $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}
		$value = trim( $value );
		return preg_match( '/^#[0-9a-fA-F]{3,8}$/', $value ) ? $value : '';
	}

	/**
	 * Consent banner > Behaviour tab: the Banner Editor's Behaviour settings.
	 * show_branding is plan-gated server-side (forced back to true on plans
	 * that must keep branding - the tab renders that toggle locked).
	 * disabled_pages arrives as one URL pattern per line.
	 *
	 * @since 1.8.0
	 */
	public function ajax_save_behaviour(): void {
		check_ajax_referer( 'kukie_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to change Kukie.io settings.', 'kukie-cookie-consent' ) ], 403 );
		}

		$patterns = [];
		if ( isset( $_POST['disabled_pages'] ) ) {
			$lines = preg_split( '/\r\n|\r|\n/', sanitize_textarea_field( wp_unslash( $_POST['disabled_pages'] ) ) ) ?: [];
			foreach ( $lines as $line ) {
				$line = trim( $line );
				if ( $line !== '' && strlen( $line ) <= 500 && ! in_array( $line, $patterns, true ) ) {
					$patterns[] = $line;
				}
				if ( count( $patterns ) >= 100 ) {
					break;
				}
			}
		}

		$api_data = [
			'show_branding'      => rest_sanitize_boolean( $_POST['show_branding'] ?? true ),
			'auto_block_scripts' => rest_sanitize_boolean( $_POST['auto_block_scripts'] ?? false ),
			'respect_dnt'        => rest_sanitize_boolean( $_POST['respect_dnt'] ?? false ),
			'respect_gpc'        => rest_sanitize_boolean( $_POST['respect_gpc'] ?? false ),
			'reload_on_consent'  => rest_sanitize_boolean( $_POST['reload_on_consent'] ?? false ),
			'show_overlay'       => rest_sanitize_boolean( $_POST['show_overlay'] ?? true ),
			'disabled_pages'     => $patterns,
		];

		[ $client, $config_version, $put_data ] = $this->put_settings_or_die( $api_data );

		$this->send_settings_saved( $client, __( 'Behaviour settings saved', 'kukie-cookie-consent' ), $config_version, $put_data );
	}

	/**
	 * Consent banner > iFrame blocking tab. The service list is validated by
	 * the server against its registry; here only the token shape is
	 * sanitised (lowercase ids with hyphens, e.g. google-maps).
	 *
	 * @since 1.8.0
	 */
	public function ajax_save_iframes(): void {
		check_ajax_referer( 'kukie_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to change Kukie.io settings.', 'kukie-cookie-consent' ) ], 403 );
		}

		$api_data = [
			'iframe_blocking_enabled' => rest_sanitize_boolean( $_POST['iframe_blocking_enabled'] ?? true ),
			'blocked_iframe_services' => $this->sanitize_a11y_tokens( $_POST['blocked_iframe_services'] ?? [] ),
		];

		[ $client, $config_version, $put_data ] = $this->put_settings_or_die( $api_data );

		$this->send_settings_saved( $client, __( 'iFrame blocking settings saved', 'kukie-cookie-consent' ), $config_version, $put_data );
	}

	/**
	 * Consent banner > Preferences modal tab: the Banner Editor's Preferences
	 * modal settings the plugin manages (layout, button order, overlay,
	 * policy links, title icon). The per-language texts and the modal's own
	 * button colours stay on the Kukie.io dashboard, like the banner's.
	 *
	 * Every value is coerced to what the server accepts before the PUT; an
	 * invalid width is refused here with a readable message rather than sent.
	 * The server merges the block over the stored one and drops the title
	 * icon fields for a plan that must keep branding.
	 *
	 * @since 1.9.0
	 */
	public function ajax_save_preferences_modal(): void {
		check_ajax_referer( 'kukie_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to change Kukie.io settings.', 'kukie-cookie-consent' ) ], 403 );
		}

		$modal = [];

		$layout = sanitize_key( wp_unslash( $_POST['button_layout'] ?? '' ) );
		if ( in_array( $layout, [ 'row', 'stacked' ], true ) ) {
			$modal['button_layout'] = $layout;
		}

		$order = isset( $_POST['button_order'] ) && is_array( $_POST['button_order'] )
			? array_values( array_map( 'sanitize_key', wp_unslash( $_POST['button_order'] ) ) )
			: [];
		$sorted = $order;
		sort( $sorted );
		$expected = self::MODAL_BUTTONS;
		sort( $expected );
		if ( $sorted === $expected ) {
			$modal['button_order'] = $order;
		}

		if ( isset( $_POST['max_width'] ) ) {
			$width = trim( sanitize_text_field( wp_unslash( $_POST['max_width'] ) ) );
			if ( $width === '' ) {
				$modal['max_width'] = null;
			} elseif ( preg_match( '/^[0-9]{3,4}$/', $width ) && (int) $width >= 320 && (int) $width <= 1200 ) {
				$modal['max_width'] = (int) $width;
			} else {
				wp_send_json_error( [ 'message' => __( 'Enter a width from 320 to 1200 pixels, or leave it empty for 600 pixels.', 'kukie-cookie-consent' ) ] );
			}
		}

		if ( isset( $_POST['show_policy_links'] ) ) {
			$modal['show_policy_links'] = rest_sanitize_boolean( wp_unslash( $_POST['show_policy_links'] ) );
		}

		// 'banner' = follow the banner's overlay (null), 'on' / 'off' = the
		// modal's own choice.
		if ( isset( $_POST['show_overlay'] ) ) {
			$overlay = sanitize_key( wp_unslash( $_POST['show_overlay'] ) );
			$modal['show_overlay'] = $overlay === 'on' ? true : ( $overlay === 'off' ? false : null );
		}

		// Title icon: only when the form sent it (the tab omits these fields
		// on a plan that must keep branding; the server would drop them).
		if ( isset( $_POST['show_icon'] ) ) {
			$modal['show_icon'] = rest_sanitize_boolean( wp_unslash( $_POST['show_icon'] ) );
		}
		if ( isset( $_POST['logo_url'] ) ) {
			$logo = esc_url_raw( trim( sanitize_text_field( wp_unslash( $_POST['logo_url'] ) ) ), [ 'https', 'http' ] );
			$modal['logo_url'] = $logo !== '' ? $logo : null;
		}
		if ( isset( $_POST['logo_max_width'] ) ) {
			$modal['logo_max_width'] = max( 40, min( 100, absint( wp_unslash( $_POST['logo_max_width'] ) ) ) );
		}
		if ( isset( $_POST['logo_border_radius'] ) ) {
			$modal['logo_border_radius'] = max( 0, min( 50, absint( wp_unslash( $_POST['logo_border_radius'] ) ) ) );
		}

		[ $client, $config_version, $put_data ] = $this->put_settings_or_die( [ 'preferences_modal' => $modal ] );

		$this->send_settings_saved( $client, __( 'Preferences modal settings saved. Your site shows them within a few minutes.', 'kukie-cookie-consent' ), $config_version, $put_data );
	}

	/**
	 * Save the Accessibility widget page. Every value is sanitised to the
	 * server's whitelists here so a stray form value can never 422 the whole
	 * save (the sanitize_banner_color() discipline), and the whole block is
	 * forwarded as `accessibility_widget` on PUT /settings - the API stores
	 * it on the site's banner config and rebakes the CDN bundle. Nothing is
	 * mirrored locally: app.kukie.io is the only source of truth for these
	 * settings, and the page re-reads them on every load.
	 *
	 * A plan without the widget answers with a structured 403 that
	 * send_put_settings_error() turns into an upgrade CTA.
	 *
	 * @since 1.8.0
	 */
	public function ajax_save_a11y(): void {
		check_ajax_referer( 'kukie_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to change Kukie.io settings.', 'kukie-cookie-consent' ) ], 403 );
		}

		$position = sanitize_text_field( wp_unslash( $_POST['position'] ?? 'bottom-right' ) );
		if ( ! in_array( $position, self::A11Y_POSITIONS, true ) ) {
			$position = 'bottom-right';
		}

		$size = absint( wp_unslash( $_POST['size'] ?? 44 ) );
		if ( ! in_array( $size, self::A11Y_SIZES, true ) ) {
			$size = 44;
		}

		// http(s) only - anything else becomes '' (= no custom link), which the
		// server stores as null and falls back to the published statement.
		$statement_url = esc_url_raw( sanitize_text_field( wp_unslash( $_POST['statement_url'] ?? '' ) ) );
		if ( $statement_url !== '' && ! preg_match( '#^https?://#i', $statement_url ) ) {
			$statement_url = '';
		}

		$api_data = [
			'accessibility_widget' => [
				'enabled'           => rest_sanitize_boolean( $_POST['enabled'] ?? false ),
				'position'          => $position,
				// '' means "inherit the banner theme colour" (server stores null).
				'color'             => $this->sanitize_a11y_color( $_POST['color'] ?? '' ),
				'size'              => $size,
				'hide_mobile'       => rest_sanitize_boolean( $_POST['hide_mobile'] ?? false ),
				'hidden_modules'    => $this->sanitize_a11y_tokens( $_POST['hidden_modules'] ?? [] ),
				'statement_enabled' => rest_sanitize_boolean( $_POST['statement_enabled'] ?? true ),
				'statement_url'     => $statement_url,
				'languages'         => $this->sanitize_a11y_tokens( $_POST['languages'] ?? [] ),
				'default_language'  => $this->sanitize_a11y_token( $_POST['default_language'] ?? '' ),
			],
		];

		[ $client, $config_version, $put_data ] = $this->put_settings_or_die( $api_data );

		$this->send_settings_saved( $client, __( 'Accessibility widget settings saved. Your site shows them within a few minutes.', 'kukie-cookie-consent' ), $config_version, $put_data );
	}

	/**
	 * Widget accent colour: the server accepts #rrggbb only (six hex digits,
	 * the dashboard's ColorInput format) or null. Anything else becomes ''
	 * (inherit) rather than a value that would 422 the whole save.
	 *
	 * @since 1.8.0
	 */
	private function sanitize_a11y_color( mixed $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}
		$value = trim( $value );
		return preg_match( '/^#[0-9a-fA-F]{6}$/', $value ) ? $value : '';
	}

	/**
	 * A module key ('textSize') or a locale code ('pt-br'): letters, digits,
	 * hyphen and underscore, CASE PRESERVED - sanitize_key() would lowercase
	 * the camelCase module keys and every one would then fail the server's
	 * whitelist. The server validates the values themselves.
	 *
	 * @since 1.8.0
	 */
	private function sanitize_a11y_token( mixed $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}
		return (string) preg_replace( '/[^A-Za-z0-9_-]/', '', wp_unslash( $value ) );
	}

	/**
	 * @since 1.8.0
	 * @return string[] Unique, non-empty tokens in submission order.
	 */
	private function sanitize_a11y_tokens( mixed $values ): array {
		if ( ! is_array( $values ) ) {
			return [];
		}
		$clean = [];
		foreach ( $values as $value ) {
			$token = $this->sanitize_a11y_token( $value );
			if ( $token !== '' && ! in_array( $token, $clean, true ) ) {
				$clean[] = $token;
			}
		}
		return $clean;
	}

	public function ajax_trigger_scan(): void {
		check_ajax_referer( 'kukie_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to change Kukie.io settings.', 'kukie-cookie-consent' ) ], 403 );
		}

		$client = $this->plugin->get_api_client();
		if ( ! $client ) {
			wp_send_json_error( [ 'message' => __( 'This site is not connected to Kukie.io.', 'kukie-cookie-consent' ) ] );
		}

		$response = $client->post( '/scan' );

		if ( ! $response['success'] ) {
			// A 429 can mean scan-already-running, queue full, or rate limit -
			// the server sends a distinct error message for each, so show it
			// instead of assuming which case it was.
			wp_send_json_error( [ 'message' => $response['error'] ?? __( "Couldn't start the scan. Try again.", 'kukie-cookie-consent' ) ] );
		}

		delete_transient( 'kukie_dashboard_data' );

		wp_send_json_success( [ 'message' => __( 'Scan started. The results appear here when it finishes.', 'kukie-cookie-consent' ) ] );
	}

	/**
	 * Live progress of the latest scan (GET /scan-status), polled by the
	 * dashboard every few seconds while a scan is pending or running. It is
	 * deliberately NOT cached: the dashboard's /status payload sits in a
	 * 5-minute transient, which froze a running scan's counters for up to
	 * five minutes (KUK-QA-2026-458). When the poll sees the scan finish it
	 * drops that transient, so the next /status read carries the result.
	 *
	 * @since 1.9.0
	 */
	public function ajax_scan_status(): void {
		check_ajax_referer( 'kukie_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to change Kukie.io settings.', 'kukie-cookie-consent' ) ], 403 );
		}

		$client = $this->plugin->get_api_client();
		if ( ! $client ) {
			wp_send_json_error( [ 'message' => __( 'This site is not connected to Kukie.io.', 'kukie-cookie-consent' ) ] );
		}

		$response = $client->get( '/scan-status' );

		if ( ! $response['success'] || ! is_array( $response['data'] ) ) {
			wp_send_json_error( [ 'message' => $response['error'] ?? __( "Couldn't load the dashboard. Try again in a minute.", 'kukie-cookie-consent' ) ] );
		}

		$data = $response['data'];
		if ( ! in_array( $data['status'] ?? '', [ 'pending', 'running' ], true ) ) {
			delete_transient( 'kukie_dashboard_data' );
		}

		wp_send_json_success( [
			'status'        => sanitize_key( (string) ( $data['status'] ?? '' ) ),
			'pages_scanned' => absint( $data['pages_scanned'] ?? 0 ),
			'total_pages'   => absint( $data['total_pages'] ?? 0 ),
			'cookies_found' => absint( $data['cookies_found'] ?? 0 ),
			'created_at'    => sanitize_text_field( (string) ( $data['created_at'] ?? '' ) ),
			'completed_at'  => sanitize_text_field( (string) ( $data['completed_at'] ?? '' ) ),
		] );
	}

	public function ajax_verify(): void {
		check_ajax_referer( 'kukie_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to change Kukie.io settings.', 'kukie-cookie-consent' ) ], 403 );
		}

		$client = $this->plugin->get_api_client();
		if ( ! $client ) {
			wp_send_json_error( [ 'message' => __( 'This site is not connected to Kukie.io.', 'kukie-cookie-consent' ) ] );
		}

		// The server verify loop probes up to 3 URLs at connectTimeout 5s +
		// timeout 10s each (worst case ~45s); a shorter client timeout would
		// abort while the server is still checking and could report a network
		// error even though the server ends up marking the site verified.
		$response = $client->post( '/verify', null, 60 );

		if ( ! $response['success'] ) {
			wp_send_json_error( [ 'message' => $response['error'] ?? __( "Couldn't find the banner script on your site.", 'kukie-cookie-consent' ) ] );
		}

		wp_send_json_success( $response['data'] );
	}

	// ─────────────────────────────────────────
	// UPTIME MONITORING (1.9.0)
	// ─────────────────────────────────────────

	/**
	 * Load the Uptime monitoring page: GET /uptime, never cached. The plan
	 * gate, the monitor's state and its basic settings all live on Kukie.io
	 * and nothing is stored in WordPress, so the page can never disagree
	 * with the Kukie.io dashboard and an upgrade unlocks it at once.
	 *
	 * @since 1.9.0
	 */
	public function ajax_get_uptime(): void {
		check_ajax_referer( 'kukie_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to change Kukie.io settings.', 'kukie-cookie-consent' ) ], 403 );
		}

		$client = $this->plugin->get_api_client();
		if ( ! $client ) {
			wp_send_json_error( [ 'message' => __( 'This site is not connected to Kukie.io.', 'kukie-cookie-consent' ) ] );
		}

		$response = $client->get( '/uptime' );

		if ( ! $response['success'] || ! is_array( $response['data'] ) ) {
			// A 404 means the Kukie.io service predates the endpoint.
			wp_send_json_error( [
				'message' => $response['status'] === 404
					? __( "Couldn't load uptime monitoring from Kukie.io. Try again in a few minutes.", 'kukie-cookie-consent' )
					: ( $response['error'] ?? __( "Couldn't load uptime monitoring from Kukie.io. Try again in a few minutes.", 'kukie-cookie-consent' ) ),
			] );
		}

		wp_send_json_success( $response['data'] );
	}

	/**
	 * Save the BASIC uptime settings: on/off, the page to check, the check
	 * interval, the owner's alert emails and the monthly report. PRESENCE-
	 * BASED like ajax_save_settings(): only the posted fields are sent, and
	 * the server writes only those, so the extra recipients and the webhook
	 * (managed on the Kukie.io dashboard only) are never touched from here.
	 *
	 * Values are coerced to what the server accepts before the PUT; the
	 * server re-validates (domain, port, plan cadence) and its 422 message
	 * reaches the page verbatim. A plan without the feature answers with the
	 * structured 403 that becomes the upgrade call to action. Nothing is
	 * mirrored locally.
	 *
	 * @since 1.9.0
	 */
	public function ajax_save_uptime(): void {
		check_ajax_referer( 'kukie_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to change Kukie.io settings.', 'kukie-cookie-consent' ) ], 403 );
		}

		$body = [];

		foreach ( [ 'enabled', 'notify_email', 'report_enabled' ] as $flag ) {
			if ( isset( $_POST[ $flag ] ) ) {
				$body[ $flag ] = rest_sanitize_boolean( wp_unslash( $_POST[ $flag ] ) );
			}
		}

		if ( isset( $_POST['url'] ) ) {
			$url = esc_url_raw( trim( sanitize_text_field( wp_unslash( $_POST['url'] ) ) ), [ 'http', 'https' ] );
			if ( $url === '' ) {
				wp_send_json_error( [ 'message' => __( 'Enter a full web address that starts with https:// or http://.', 'kukie-cookie-consent' ) ] );
			}
			$body['url'] = $url;
		}

		if ( isset( $_POST['check_interval_seconds'] ) ) {
			$raw      = sanitize_text_field( wp_unslash( $_POST['check_interval_seconds'] ) );
			$interval = absint( $raw );
			// '' = the plan's own cadence (stored as null server-side).
			if ( $raw === '' ) {
				$body['check_interval_seconds'] = null;
			} elseif ( in_array( $interval, self::UPTIME_INTERVALS, true ) ) {
				$body['check_interval_seconds'] = $interval;
			}
		}

		$client = $this->plugin->get_api_client();
		if ( ! $client ) {
			wp_send_json_error( [ 'message' => __( 'This site is not connected to Kukie.io.', 'kukie-cookie-consent' ) ] );
		}

		$response = $client->put( '/uptime', $body );

		if ( ! $response['success'] ) {
			$this->send_plan_gate_error( $response );
			wp_send_json_error( [ 'message' => $response['error'] ?? __( "Couldn't save the settings. Try again.", 'kukie-cookie-consent' ) ] );
		}

		wp_send_json_success( [
			'message' => __( 'Uptime monitoring settings saved', 'kukie-cookie-consent' ),
			'uptime'  => is_array( $response['data'] ) ? $response['data'] : null,
		] );
	}
}
