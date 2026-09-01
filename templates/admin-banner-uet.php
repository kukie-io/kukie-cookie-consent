<?php
/**
 * Cookie banner > Microsoft UET tab (a partial of admin-banner.php).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="kukie-uet-error" class="notice notice-error inline kukie-notice" role="alert" hidden><p></p></div>

<div id="kukie-uet-loading" class="kukie-loading" role="status">
	<span class="kukie-spinner" aria-hidden="true"></span>
	<?php esc_html_e( 'Loading settings...', 'kukie-cookie-consent' ); ?>
</div>

<form id="kukie-uet-form" hidden>
	<div class="kukie-card">
		<h2 class="kukie-card-title"><?php esc_html_e( 'Microsoft UET Consent Signals', 'kukie-cookie-consent' ); ?></h2>
		<p class="kukie-card-description">
			<?php esc_html_e( 'Pass consent signals to Microsoft Advertising Universal Event Tracking. When enabled, visitor consent choices are forwarded to Microsoft UET tags so they can adjust their data-collection behaviour.', 'kukie-cookie-consent' ); ?>
		</p>

		<div class="kukie-form-row">
			<div class="kukie-form-row-label">
				<span id="kukie-uet-enabled-label"><?php esc_html_e( 'Enable Microsoft UET consent signals', 'kukie-cookie-consent' ); ?></span>
				<span class="kukie-form-row-hint" id="kukie-uet-enabled-hint"><?php esc_html_e( 'Pass consent signals to Microsoft Advertising Universal Event Tracking.', 'kukie-cookie-consent' ); ?></span>
			</div>
			<label class="kukie-toggle">
				<input type="checkbox" role="switch" name="ms_uet_enabled" id="kukie-uet-enabled" value="1" aria-labelledby="kukie-uet-enabled-label" aria-describedby="kukie-uet-enabled-hint">
				<span class="kukie-toggle-slider" aria-hidden="true"></span>
			</label>
		</div>
	</div>

	<div class="kukie-form-actions">
		<button type="submit" class="kukie-btn-primary" id="kukie-uet-save">
			<span class="kukie-btn-text"><?php esc_html_e( 'Save Settings', 'kukie-cookie-consent' ); ?></span>
			<span class="kukie-btn-loading" hidden>
				<span class="kukie-spinner" aria-hidden="true"></span>
				<?php esc_html_e( 'Saving...', 'kukie-cookie-consent' ); ?>
			</span>
		</button>
	</div>
</form>
