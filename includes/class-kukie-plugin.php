<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Kukie_Plugin {

	private static ?Kukie_Plugin $instance = null;
	private ?array $settings = null;

	// Set by Kukie_Api_Client after every HTTP round trip: the memo above
	// was loaded before the request slept, so it may no longer reflect the
	// database (another request may have written, or disconnected, meanwhile).
	// get_settings() re-reads before serving or merging while this is set.
	private bool $settings_stale = false;

	// Per-request memo of the decrypted API key, keyed to the ciphertext it
	// was derived from so a reconnect (new ciphertext) self-invalidates.
	private ?string $api_key_plain = null;
	private string $api_key_cipher_seen = '';

	public static function instance(): Kukie_Plugin {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->maybe_upgrade();

		if ( is_admin() ) {
			$admin = new Kukie_Admin( $this );
			$admin->init();
		}

		$injector = new Kukie_Script_Injector( $this );
		$injector->init();

		// WP Consent API integration (delayed until all plugins are loaded,
		// because wp-consent-api loads after kukie-cookie-consent alphabetically)
		add_action( 'plugins_loaded', static function () {
			$wp_consent_api = new Kukie_WP_Consent_API();
			$wp_consent_api->init();
		} );

		// "Settings" link on plugins list page
		add_filter( 'plugin_action_links_' . plugin_basename( KUKIE_PLUGIN_FILE ), [ $this, 'add_action_links' ] );
	}

	/**
	 * Version-gated upgrade routine. Runs at most one option write per plugin
	 * version bump: the stored plugin_version is compared against KUKIE_VERSION
	 * on every request, but the write (and any migration) only happens when they
	 * differ. After the write, subsequent requests short-circuit on the first
	 * comparison, so there is no per-request write storm.
	 *
	 * Migration for 1.6.3: self-heal a stale embed_url. Pre-CDN installs stored
	 * a legacy app.kukie.io URL that no longer serves anything; rewrite it to the
	 * canonical CDN form built from the stored site_key so the admin snippet and
	 * any downstream reader see the correct value without requiring a reconnect.
	 * Injection itself no longer trusts embed_url (see Kukie_Script_Injector).
	 *
	 * @since 1.6.3
	 */
	private function maybe_upgrade(): void {
		$settings = $this->get_settings();

		if ( ( $settings['plugin_version'] ?? '' ) === KUKIE_VERSION ) {
			return;
		}

		$site_key = $settings['site_key'] ?? '';

		// Only heal embed_url when connected; never write cdn.kukie.io/s//c.js.
		if ( $site_key !== '' ) {
			$canonical = sprintf( 'https://cdn.kukie.io/s/%s/c.js', rawurlencode( $site_key ) );
			if ( ( $settings['embed_url'] ?? '' ) !== $canonical ) {
				$settings['embed_url'] = $canonical;
			}
		}

		// Heal a present-but-empty dashboard_url (pre-1.7.1 connects stored
		// '' when the connect response omitted it, which defeats the
		// get_option() default in every reader and renders links as
		// href=""). Removing the key restores the default everywhere.
		if ( isset( $settings['dashboard_url'] ) && $settings['dashboard_url'] === '' ) {
			unset( $settings['dashboard_url'] );
		}

		// First-ever write on a fresh install: WordPress includes the plugin
		// file (which runs this method and writes the option) BEFORE firing
		// the activation hook, so activate() cannot detect a fresh install by
		// option absence. Mark it here; activate() consumes the marker to set
		// the one-time connect-page redirect.
		if ( $settings === [] ) {
			$settings['first_activation'] = true;
		}

		$settings['plugin_version'] = KUKIE_VERSION;

		$this->settings = $settings;
		update_option( 'kukie_settings', $settings );

		// Serve the corrected value immediately if the plugin caches settings.
		delete_transient( 'kukie_settings_cache' );
		delete_transient( 'kukie_dashboard_data' );
	}

	public static function activate(): void {
		// Redirect to the connect page exactly once, on the first-ever
		// activation. maybe_upgrade() has already written kukie_settings by
		// the time this hook fires (the include runs first), so we gate on
		// the first_activation marker it sets rather than option presence.
		$settings = get_option( 'kukie_settings', [] );

		if ( is_array( $settings ) && ! empty( $settings['first_activation'] ) ) {
			unset( $settings['first_activation'] );
			update_option( 'kukie_settings', $settings );

			// Keep the already-bootstrapped singleton's memo in sync so a
			// later write in this request cannot re-persist the marker.
			if ( self::$instance !== null ) {
				self::$instance->settings = $settings;
			}

			// Redirect only when the install is not connected: a stale
			// marker on a connected install (e.g. one that disconnected and
			// reconnected before 1.7.1 cleared markers on connect) must not
			// bounce a working setup to the connect page.
			if ( empty( $settings['site_key'] ) ) {
				set_transient( 'kukie_activation_redirect', true, 30 );
			}
		}
	}

	public static function deactivate(): void {
		delete_transient( 'kukie_dashboard_data' );
		delete_transient( 'kukie_settings_cache' );
	}

	public function get_settings(): array {
		if ( $this->settings_stale ) {
			return $this->refresh_settings();
		}
		if ( $this->settings === null ) {
			$this->settings = get_option( 'kukie_settings', [] );
		}
		return $this->settings;
	}

	/**
	 * Flag the per-request memo as predating an HTTP round trip. Called by
	 * Kukie_Api_Client::request() after every wp_remote_request(), whatever
	 * the outcome (a timeout sleeps longest of all). The next get_settings()
	 * call - and therefore the next update_option()/update_options(), which
	 * merge on top of get_settings() - re-reads the option first, so a
	 * post-round-trip write merges caller values onto the FRESH database
	 * state instead of the bootstrap snapshot. Writes nothing itself, so it
	 * is safe to call from untrusted (candidate-key) clients too.
	 *
	 * @since 1.7.2
	 */
	public function mark_settings_stale(): void {
		$this->settings_stale = true;
	}

	/**
	 * Drop the per-request memo and re-read the option from the database.
	 *
	 * Handlers rarely need to call this by hand: the API client marks the
	 * memo stale after every round trip (mark_settings_stale()) and
	 * get_settings() re-reads when the mark is set, while a write that runs
	 * after a round trip MUST gate on settings_still_connected() and relies
	 * on update_options()' ghost-row guard - see those docblocks for the
	 * caller contract. This method is the underlying forced re-read.
	 *
	 * Cache layers, and which this defeats: get_option() consults the
	 * request's alloptions snapshot FIRST for autoloaded options, and
	 * kukie_settings is autoloaded (written with the two-argument
	 * update_option()), so deleting only the per-option entry never
	 * produced a fresh read on a default install - the "fresh" value was
	 * this request's bootstrap snapshot. Evicting 'alloptions' (the same
	 * eviction WordPress core performs inside update_option()) and
	 * 'notoptions' forces the next read to the database on a default
	 * install; with a persistent object cache (Redis/Memcached) the shared
	 * entries are evicted and re-primed from the database, so both
	 * topologies end fresh. Cost: one alloptions reload query on the next
	 * autoloaded-option read - acceptable at this call frequency (a
	 * handful of admin AJAX writes per request).
	 *
	 * What this does NOT do: serialise concurrent writers. WordPress
	 * offers no lock here; the refresh narrows the stale window to the gap
	 * between this read and the following write, it does not close it.
	 *
	 * @since 1.7.1
	 * @since 1.7.2 actually bypasses alloptions/notoptions (previously only
	 *              the per-option cache entry was cleared, which
	 *              get_option() never consults for an autoloaded option, so
	 *              on a default install the re-read served the stale
	 *              alloptions snapshot).
	 */
	public function refresh_settings(): array {
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'notoptions', 'options' );
		wp_cache_delete( 'kukie_settings', 'options' );
		$fresh                = get_option( 'kukie_settings', [] );
		$this->settings       = is_array( $fresh ) ? $fresh : [];
		$this->settings_stale = false;
		return $this->settings;
	}

	/**
	 * Whether the install is still connected: the settings carry a site_key.
	 *
	 * Reads through get_settings(), so after an HTTP round trip (which marks
	 * the memo stale) the verdict comes from a fresh database read - the
	 * strongest guarantee available without a lock. Deliberately does NOT
	 * force a second read when the memo is already post-round-trip fresh: a
	 * forced re-read would narrow an unclosable window by microseconds while
	 * doubling alloptions reloads (sitewide evictions on a persistent object
	 * cache).
	 *
	 * This is the shared guard for work that must not outlive a concurrent
	 * disconnect (ajax_disconnect deletes the whole option): the trusted
	 * client's api_key_valid write, the post-save local commits, and every
	 * post-round-trip transient re-creation. The option writers below carry
	 * a structural ghost-row backstop of their own (see update_options()),
	 * so this guard is defense-in-depth plus skip-pointless-work, not the
	 * only defense. A FRESH connect must NOT use it: ajax_connect()
	 * legitimately writes into an empty option (its payload carries the new
	 * site_key, which is exactly what the ghost-row guard admits).
	 *
	 * Deliberate narrowing: a present-but-keyless row (a connect that stored
	 * site_key '') counts as NOT connected, so trust-state writes freeze in
	 * that broken state until the reconnect the admin UI is already forcing.
	 *
	 * @since 1.7.2
	 */
	public function settings_still_connected(): bool {
		return ! empty( $this->get_settings()['site_key'] );
	}

	public function get_option( string $key, mixed $default = null ): mixed {
		$settings = $this->get_settings();
		return $settings[ $key ] ?? $default;
	}

	public function update_option( string $key, mixed $value ): void {
		$this->update_options( [ $key => $value ] );
	}

	/**
	 * Merge values into kukie_settings and persist. The merge base comes
	 * from get_settings(), so a caller running after an HTTP round trip
	 * merges onto a fresh database read (the API client marks the memo
	 * stale), never onto the bootstrap snapshot.
	 *
	 * Ghost-row guard: when that fresh base is EMPTY, the install was
	 * disconnected (delete_option) or never initialised - re-creating the
	 * option from a write that does not itself establish a connection would
	 * leave a ghost row that maybe_upgrade() then stamps with
	 * plugin_version, permanently disarming the one-time first_activation
	 * redirect. Only a write carrying a site_key (ajax_connect establishing
	 * the connection) may create the option here; maybe_upgrade() and
	 * activate() write the raw option directly and never pass this guard.
	 * Post-round-trip callers should still gate on
	 * settings_still_connected() to skip pointless work and any non-option
	 * side effects (transients, notices).
	 *
	 * @since 1.7.2 ghost-row guard; update_option() now delegates here.
	 */
	public function update_options( array $values ): void {
		$base = $this->get_settings();
		if ( $base === [] && empty( $values['site_key'] ) ) {
			return;
		}
		$settings       = array_merge( $base, $values );
		$this->settings = $settings;
		update_option( 'kukie_settings', $settings );
	}

	/**
	 * Connected means the stored API key is actually USABLE, not merely
	 * present. After a salt rotation (wp config shuffle-salts) or a
	 * cross-environment DB clone, the stored ciphertext no longer decrypts;
	 * treating that install as "connected" showed a working Dashboard while
	 * every action failed with "Not connected." and no reconnect guidance.
	 *
	 * The front-end banner is NOT gated on this - Kukie_Script_Injector only
	 * needs the site_key.
	 *
	 * @since 1.7.0 accounts for decryptability, not just presence.
	 */
	public function is_connected(): bool {
		return ! empty( $this->get_option( 'site_key' ) )
			&& $this->has_stored_api_key()
			&& $this->get_api_key() !== '';
	}

	public function has_stored_api_key(): bool {
		return ! empty( $this->get_option( 'api_key_encrypted' ) );
	}

	/**
	 * An API key is stored but can no longer be decrypted (salt rotation or
	 * DB clone with different salts). Distinct from a fresh install, which
	 * has no stored key at all.
	 *
	 * @since 1.7.0
	 */
	public function api_key_decrypt_failed(): bool {
		return $this->has_stored_api_key() && $this->get_api_key() === '';
	}

	public function get_api_key(): string {
		$encrypted = (string) $this->get_option( 'api_key_encrypted', '' );
		if ( $encrypted === '' ) {
			return '';
		}

		if ( $this->api_key_plain === null || $this->api_key_cipher_seen !== $encrypted ) {
			$this->api_key_plain       = Kukie_Encryption::decrypt( $encrypted );
			$this->api_key_cipher_seen = $encrypted;

			// Migrate legacy '::'-delimited ciphertext to the fixed-length-IV
			// format on the first successful decrypt (see Kukie_Encryption).
			if ( $this->api_key_plain !== '' && Kukie_Encryption::is_legacy( $encrypted ) ) {
				$reencrypted = Kukie_Encryption::encrypt( $this->api_key_plain );
				if ( $reencrypted !== '' ) {
					$this->update_option( 'api_key_encrypted', $reencrypted );
					$this->api_key_cipher_seen = $reencrypted;
				}
			}
		}

		return $this->api_key_plain;
	}

	public function get_api_client(): ?Kukie_Api_Client {
		$key = $this->get_api_key();
		if ( empty( $key ) ) {
			return null;
		}
		// Trusted: built from the STORED key, so its 401/2xx results may
		// update the global api_key_valid state (see Kukie_Api_Client).
		return new Kukie_Api_Client( $key, true );
	}

	public function is_api_key_valid(): bool {
		$settings = $this->get_settings();
		if ( empty( $settings['api_key_encrypted'] ) ) {
			return false;
		}
		return $settings['api_key_valid'] ?? true;
	}

	public function set_api_key_valid( bool $valid ): void {
		$this->update_option( 'api_key_valid', $valid );
		if ( ! $valid ) {
			delete_transient( 'kukie_dashboard_data' );
			delete_transient( 'kukie_settings_cache' );
		}
	}

	public function add_action_links( array $links ): array {
		$url = $this->is_connected()
			? admin_url( 'admin.php?page=kukie' )
			: admin_url( 'admin.php?page=kukie-connect' );

		$label = $this->is_connected()
			? __( 'Dashboard', 'kukie-cookie-consent' )
			: __( 'Connect', 'kukie-cookie-consent' );

		array_unshift( $links, sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $label ) ) );

		return $links;
	}
}
