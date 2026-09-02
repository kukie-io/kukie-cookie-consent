<?php
/**
 * Renders one admin template to static HTML with the WordPress stubs, so the
 * real admin.css + admin.js can be exercised in headless Chromium (run.mjs).
 * Usage: php tests/e2e/render.php admin-settings.php [page-slug] [tab]
 */
// Renders a plugin admin template to static HTML using the test stubs, so the
// real admin.css + admin.js can be exercised in a headless browser.
$src = dirname( __DIR__, 2 );
$out = __DIR__ . '/out';
if ( ! is_dir( $out ) ) { mkdir( $out, 0777, true ); }
define( 'ABSPATH', $src . '/' );
define( 'KUKIE_VERSION', '1.8.0' );
define( 'KUKIE_PLUGIN_FILE', $src . '/kukie-cookie-consent.php' );
define( 'KUKIE_PLUGIN_DIR', $src . '/' );
define( 'KUKIE_PLUGIN_URL', 'http://localhost:8765/plugin/' );
define( 'KUKIE_API_BASE', 'https://app.kukie.io/api/v1/plugin' );
define( 'MINUTE_IN_SECONDS', 60 );
require $src . '/tests/stubs/wordpress.php';
foreach ( glob( $src . '/includes/*.php' ) as $f ) { require_once $f; }
if ( ! function_exists( 'kukie_array_contains_domain' ) ) { function kukie_array_contains_domain( array $l, string $d ): bool { return false; } }
kukie_test_reset();
kukie_test_seed_settings( [ 'api_key_encrypted' => Kukie_Encryption::encrypt( str_repeat( 'a', 64 ) ), 'api_key_valid' => true, 'site_key' => 'site-key-1', 'site_id' => 7, 'domain' => 'obshti-uslovia.com', 'banner_enabled' => true, 'script_position' => 'head', 'plugin_version' => KUKIE_VERSION, 'organisation' => 'Pixadoro', 'plan_name' => 'Pixadoro Custom Plan' ] );
Kukie_Plugin::instance();
$_GET = [ 'page' => $argv[2] ?? 'kukie', 'tab' => $argv[3] ?? '' ];
ob_start();
require $src . '/templates/' . $argv[1];
$body = ob_get_clean();
$html = '<!doctype html><html><head><meta charset="utf-8"><link rel="stylesheet" href="https://obshti-uslovia.com/wp-includes/css/dashicons.min.css"><link rel="stylesheet" href="/plugin/assets/css/admin.css"></head><body class="wp-admin wp-core-ui"><div id="wpbody-content">' . $body . '</div><script src="/plugin/assets/js/admin.js"></script></body></html>';
$name = pathinfo( $argv[1], PATHINFO_FILENAME ) . ( ( $argv[3] ?? '' ) !== '' ? '-' . $argv[3] : '' );
file_put_contents( $out . '/' . $name . '.html', $html );
echo "rendered " . $argv[1] . "\n";
