<?php
/**
 * Consent banner > Google Consent Mode v2 tab (a partial of admin-banner.php).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$kukie_plugin = Kukie_Plugin::instance();
?>
<div id="kukie-gcm-error" class="notice notice-error inline kukie-notice" role="alert" hidden><p></p></div>

<div id="kukie-gcm-loading" class="kukie-loading" role="status">
	<span class="kukie-spinner" aria-hidden="true"></span>
	<?php esc_html_e( 'Loading settings...', 'kukie-cookie-consent' ); ?>
</div>

<form id="kukie-gcm-form" hidden>
	<div class="kukie-card">
		<h2 class="kukie-card-title"><?php esc_html_e( 'Google Consent Mode v2', 'kukie-cookie-consent' ); ?></h2>
		<p class="kukie-card-description" id="kukie-gcm-enabled-hint">
			<?php esc_html_e( 'Enable Google Consent Mode v2 to pass visitor consent choices to Google tags (Analytics, Ads, GTM). Required by Google Ads for audience building and remarketing in the EEA under Google\'s platform policies.', 'kukie-cookie-consent' ); ?>
		</p>

		<div class="kukie-form-row">
			<div class="kukie-form-row-label">
				<span id="kukie-gcm-enabled-label"><?php esc_html_e( 'Enable GCM v2', 'kukie-cookie-consent' ); ?></span>
			</div>
			<label class="kukie-toggle">
				<input type="checkbox" role="switch" name="gcm_v2_enabled" id="kukie-gcm-enabled" value="1" aria-labelledby="kukie-gcm-enabled-label" aria-describedby="kukie-gcm-enabled-hint">
				<span class="kukie-toggle-slider" aria-hidden="true"></span>
			</label>
		</div>
	</div>

	<!-- Deliberately no per-category table here: the real defaults are
	     region-rule-driven (opt-in regions deny by default, opt-out
	     regions grant), so any static rendering would be wrong for part
	     of every audience. The dashboard shows the live rules. -->
	<div class="kukie-card">
		<h2 class="kukie-card-title"><?php esc_html_e( 'Default Consent State', 'kukie-cookie-consent' ); ?></h2>
		<p class="kukie-card-description">
			<?php esc_html_e( 'The default consent states before a visitor interacts with the banner depend on the visitor\'s region: opt-in regions start with consent denied, opt-out regions start with consent granted. They are managed through region rules in the Kukie.io dashboard.', 'kukie-cookie-consent' ); ?>
		</p>
		<p>
			<a href="<?php echo esc_url( $kukie_plugin->get_option( 'dashboard_url', 'https://app.kukie.io' ) ); ?>" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Manage region rules in the Kukie.io dashboard', 'kukie-cookie-consent' ); ?>
				<?php echo Kukie_Admin::new_tab_marker(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses-sanitised in the helper ?>
			</a>
		</p>
	</div>

	<div class="kukie-form-actions">
		<button type="submit" class="kukie-btn-primary" id="kukie-gcm-save">
			<span class="kukie-btn-text"><?php esc_html_e( 'Save Settings', 'kukie-cookie-consent' ); ?></span>
			<span class="kukie-btn-loading" hidden>
				<span class="kukie-spinner" aria-hidden="true"></span>
				<?php esc_html_e( 'Saving...', 'kukie-cookie-consent' ); ?>
			</span>
		</button>
	</div>
</form>
