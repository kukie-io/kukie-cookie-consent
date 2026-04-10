<?php
/**
 * Plugin Name:       Kukie - Cookie Banner and Consent Management (GDPR, CCPA, DSVGO, CNIL, PIPEDA)
 * Plugin URI:        https://kukie.io/wordpress
 * Description:       Connect your WordPress site to Kukie.io for GDPR, CCPA, DSVGO, CNIL, LGPD, PIPEDA cookie consent management. Requires a Kukie.io account.
 * Version:           1.5.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Tested up to:      6.9
 * Author:            Kukie.io
 * Author URI:        https://kukie.io
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       kukie-cookie-consent
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KUKIE_VERSION', '1.5.0' );
define( 'KUKIE_PLUGIN_FILE', __FILE__ );
define( 'KUKIE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'KUKIE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'KUKIE_API_BASE', 'https://app.kukie.io/api/v1/plugin' );

/**
 * Check if any entry in a list contains the given domain string.
 *
 * @param array  $list   List of strings to search.
 * @param string $domain Domain to look for.
 * @return bool
 */
function kukie_array_contains_domain( array $list, string $domain ): bool {
	foreach ( $list as $entry ) {
		if ( str_contains( (string) $entry, $domain ) ) {
			return true;
		}
	}
	return false;
}

// Load text domain for translations
function kukie_load_textdomain() {
	load_plugin_textdomain(
		'kukie-cookie-consent',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'init', 'kukie_load_textdomain' );

// Load classes
require_once KUKIE_PLUGIN_DIR . 'includes/class-kukie-encryption.php';
require_once KUKIE_PLUGIN_DIR . 'includes/class-kukie-api-client.php';
require_once KUKIE_PLUGIN_DIR . 'includes/class-kukie-settings.php';
require_once KUKIE_PLUGIN_DIR . 'includes/class-kukie-admin.php';
require_once KUKIE_PLUGIN_DIR . 'includes/class-kukie-script-injector.php';
require_once KUKIE_PLUGIN_DIR . 'includes/class-kukie-wp-consent-api.php';
require_once KUKIE_PLUGIN_DIR . 'includes/class-kukie-plugin.php';

// Register caching plugin exclusion filters early (before connection check).
// These must run unconditionally because caching plugins scan all scripts regardless.
Kukie_Script_Injector::register_cache_exclusions();

// Bootstrap
Kukie_Plugin::instance();

// Activation/deactivation
register_activation_hook( __FILE__, [ 'Kukie_Plugin', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'Kukie_Plugin', 'deactivate' ] );
