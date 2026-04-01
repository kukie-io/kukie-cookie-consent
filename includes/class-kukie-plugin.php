<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Kukie_Plugin {

	private static ?Kukie_Plugin $instance = null;
	private ?array $settings = null;

	public static function instance(): Kukie_Plugin {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
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

	public static function activate(): void {
		// Set redirect flag so admin sees connect page on first load
		if ( ! get_option( 'kukie_settings' ) ) {
			update_option( 'kukie_settings', [] );
			set_transient( 'kukie_activation_redirect', true, 30 );
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

	public function is_connected(): bool {
		$key = $this->get_option( 'api_key_encrypted' );
		return ! empty( $key ) && ! empty( $this->get_option( 'site_key' ) );
	}

	public function get_api_key(): string {
		$encrypted = $this->get_option( 'api_key_encrypted', '' );
		if ( empty( $encrypted ) ) {
			return '';
		}
		return Kukie_Encryption::decrypt( $encrypted );
	}

	public function get_api_client(): ?Kukie_Api_Client {
		$key = $this->get_api_key();
		if ( empty( $key ) ) {
			return null;
		}
		return new Kukie_Api_Client( $key );
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
