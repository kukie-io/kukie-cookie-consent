<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Kukie_Plugin {

	private static ?Kukie_Plugin $instance = null;
	private ?array $settings = null;

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
		if ( $this->settings === null ) {
			$this->settings = get_option( 'kukie_settings', [] );
		}
		return $this->settings;
	}

	/**
	 * Drop the per-request memo and re-read the option from the database.
	 * update_option()/update_options() merge into the memo, so a handler
	 * that slept on an HTTP round-trip since bootstrap must call this before
	 * writing, or it persists a stale snapshot over concurrent writes from
	 * other requests (worst case: resurrecting a just-disconnected install).
	 *
	 * @since 1.7.1
	 */
	public function refresh_settings(): array {
		wp_cache_delete( 'kukie_settings', 'options' );
		$fresh = get_option( 'kukie_settings', [] );
		$this->settings = is_array( $fresh ) ? $fresh : [];
		return $this->settings;
	}

	public function get_option( string $key, mixed $default = null ): mixed {
		$settings = $this->get_settings();
		return $settings[ $key ] ?? $default;
	}

	public function update_option( string $key, mixed $value ): void {
		$settings = $this->get_settings();
		$settings[ $key ] = $value;
		$this->settings = $settings;
		update_option( 'kukie_settings', $settings );
	}

	public function update_options( array $values ): void {
		$settings = array_merge( $this->get_settings(), $values );
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
