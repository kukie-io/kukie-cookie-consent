<?php
/**
 * Consent banner page: one page, three tabs (Design / Google Consent Mode v2 /
 * Microsoft UET). The tab partials are the pre-1.8.0 per-page templates with
 * their page chrome removed; only the active tab's partial is included.
 *
 * @since 1.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$kukie_plugin  = Kukie_Plugin::instance();
$kukie_site_id = absint( $kukie_plugin->get_option( 'site_id', 0 ) );
$kukie_app_url = 'https://app.kukie.io';
$kukie_tab     = Kukie_Admin::current_banner_tab();
$kukie_tabs    = [
	'design' => __( 'Design', 'kukie-cookie-consent' ),
	'gcm'    => __( 'Google Consent Mode v2', 'kukie-cookie-consent' ),
	'uet'    => __( 'Microsoft UET', 'kukie-cookie-consent' ),
];
?>
<div class="wrap kukie-wrap<?php echo 'design' === $kukie_tab ? ' kukie-wrap--wide' : ''; ?>">
	<div class="kukie-header">
		<h1><?php esc_html_e( 'Consent banner', 'kukie-cookie-consent' ); ?></h1>
		<a href="<?php echo esc_url( $kukie_app_url . '/sites/' . $kukie_site_id . '/banner' ); ?>" target="_blank" rel="noopener noreferrer" class="kukie-external-link">
			<?php esc_html_e( 'Open the banner editor on Kukie.io', 'kukie-cookie-consent' ); ?>
			<?php echo Kukie_Admin::new_tab_marker(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses-sanitised in the helper ?>
		</a>
	</div>
	<hr class="wp-header-end">

	<nav class="nav-tab-wrapper wp-clearfix kukie-tabs" aria-label="<?php esc_attr_e( 'Consent banner sections', 'kukie-cookie-consent' ); ?>">
		<?php foreach ( $kukie_tabs as $kukie_tab_key => $kukie_tab_label ) : ?>
			<?php if ( $kukie_tab_key === $kukie_tab ) : ?>
				<a href="<?php echo esc_url( Kukie_Admin::banner_tab_url( $kukie_tab_key ) ); ?>" class="nav-tab nav-tab-active" aria-current="page"><?php echo esc_html( $kukie_tab_label ); ?></a>
			<?php else : ?>
				<a href="<?php echo esc_url( Kukie_Admin::banner_tab_url( $kukie_tab_key ) ); ?>" class="nav-tab"><?php echo esc_html( $kukie_tab_label ); ?></a>
			<?php endif; ?>
		<?php endforeach; ?>
	</nav>

	<?php
	// Literal paths only (the tab value is whitelisted, but a literal keeps
	// the include set enumerable for static review).
	switch ( $kukie_tab ) {
		case 'gcm':
			require KUKIE_PLUGIN_DIR . 'templates/admin-banner-gcm.php';
			break;
		case 'uet':
			require KUKIE_PLUGIN_DIR . 'templates/admin-banner-uet.php';
			break;
		default:
			require KUKIE_PLUGIN_DIR . 'templates/admin-banner-design.php';
			break;
	}
	?>
</div>
