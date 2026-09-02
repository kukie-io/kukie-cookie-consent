<?php
/**
 * Consent banner > Regions tab (a partial of admin-banner.php): region rules
 * are managed on Kukie.io only - this tab just points there.
 *
 * @since 1.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$kukie_plugin  = Kukie_Plugin::instance();
$kukie_site_id = absint( $kukie_plugin->get_option( 'site_id', 0 ) );
$kukie_app_url = 'https://app.kukie.io';
?>
<div class="kukie-card kukie-cta-card">
	<span class="dashicons dashicons-admin-site-alt3 kukie-cta-glyph" aria-hidden="true"></span>
	<h2 class="kukie-card-title"><?php esc_html_e( 'Region rules', 'kukie-cookie-consent' ); ?></h2>
	<p><?php esc_html_e( 'Consent model, button visibility, texts and cookie-wall behaviour per visitor region (EU opt-in, US opt-out, sub-regions such as CNIL or Quebec) are managed in the Kukie.io banner editor. They apply to this site automatically - there is nothing to configure here.', 'kukie-cookie-consent' ); ?></p>
	<p class="kukie-card-actions-row kukie-card-actions-row--center">
		<a href="<?php echo esc_url( $kukie_app_url . '/sites/' . $kukie_site_id . '/banner?tab=regions' ); ?>" target="_blank" rel="noopener noreferrer" class="kukie-btn-primary">
			<?php esc_html_e( 'Manage region rules on Kukie.io', 'kukie-cookie-consent' ); ?>
			<?php echo Kukie_Admin::new_tab_marker(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses-sanitised in the helper ?>
		</a>
	</p>
</div>
