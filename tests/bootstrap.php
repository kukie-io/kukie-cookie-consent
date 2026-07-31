<?php
/**
 * PHPUnit bootstrap.
 *
 * Loads the WordPress stubs, then the plugin's classes - WITHOUT loading
 * kukie-cookie-consent.php, which bootstraps the singleton at include time.
 *
 * KUKIE_SRC selects the source tree under test. It defaults to this repo, but
 * pointing it at an older checkout is how this suite proves it is not
 * tautological: the paths it locks were genuinely broken before 1.7.1, so
 *
 *   git archive 5abca18 | tar -x -C /tmp/kukie-1.7.0
 *   KUKIE_SRC=/tmp/kukie-1.7.0 vendor/bin/phpunit
 *
 * must FAIL, while an unset KUKIE_SRC must pass. See tests/README.md.
 */

$kukie_src = getenv( 'KUKIE_SRC' ) ?: dirname( __DIR__ );
$kukie_src = rtrim( $kukie_src, '/' );

if ( ! is_dir( $kukie_src . '/includes' ) ) {
	fwrite( STDERR, "KUKIE_SRC does not look like a plugin tree: {$kukie_src}\n" );
	exit( 1 );
}

// The plugin's own guard against direct file access.
define( 'ABSPATH', __DIR__ . '/' );

// Read the version from the tree under test rather than hardcoding it, so a
// run against an older checkout exercises that release's upgrade comparison.
$kukie_main = file_get_contents( $kukie_src . '/kukie-cookie-consent.php' );
preg_match( "/define\(\s*'KUKIE_VERSION',\s*'([^']+)'/", $kukie_main, $kukie_version_match );

define( 'KUKIE_VERSION', $kukie_version_match[1] ?? '0.0.0' );
define( 'KUKIE_PLUGIN_FILE', $kukie_src . '/kukie-cookie-consent.php' );
define( 'KUKIE_PLUGIN_DIR', $kukie_src . '/' );
define( 'KUKIE_PLUGIN_URL', 'https://example.test/wp-content/plugins/kukie-cookie-consent/' );
define( 'KUKIE_API_BASE', 'https://app.kukie.io/api/v1/plugin' );

define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );

require_once __DIR__ . '/stubs/wordpress.php';

// Globbed, not enumerated: the include set differs between releases (1.7.0
// still shipped class-kukie-settings.php), and every class is independent, so
// load order does not matter.
foreach ( glob( $kukie_src . '/includes/*.php' ) as $kukie_class_file ) {
	require_once $kukie_class_file;
}

// The main plugin file defines this helper outside any class; the injector's
// cache-exclusion filters call it.
if ( ! function_exists( 'kukie_array_contains_domain' ) ) {
	function kukie_array_contains_domain( array $list, string $domain ): bool {
		foreach ( $list as $entry ) {
			if ( str_contains( (string) $entry, $domain ) ) {
				return true;
			}
		}
		return false;
	}
}

require_once __DIR__ . '/KukieTestCase.php';

kukie_test_reset();
