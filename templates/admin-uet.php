<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$kukie_plugin = Kukie_Plugin::instance();
?>
<div class="wrap kukie-wrap">
	<div class="kukie-header">
		<h1><?php esc_html_e( 'Microsoft UET', 'kukie-cookie-consent' ); ?></h1>
		<a href="<?php echo esc_url( $kukie_plugin->get_option( 'dashboard_url', 'https://app.kukie.io' ) ); ?>" target="_blank" rel="noopener noreferrer" class="kukie-external-link">
			<?php esc_html_e( 'Full settings', 'kukie-cookie-consent' ); ?>
			<span class="dashicons dashicons-external"></span>
		</a>
	</div>
	<hr class="wp-header-end">

	<div id="kukie-uet-error" class="kukie-notice kukie-notice-error" style="display:none;"></div>
	<div id="kukie-uet-success" class="kukie-notice kukie-notice-success" style="display:none;"></div>

	<div id="kukie-uet-loading" class="kukie-loading">
		<span class="kukie-spinner"></span>
		<?php esc_html_e( 'Loading settings...', 'kukie-cookie-consent' ); ?>
	</div>

	<form id="kukie-uet-form" style="display:none;">
		<div class="kukie-card">
			<h2 class="kukie-card-title"><?php esc_html_e( 'Microsoft UET Consent Signals', 'kukie-cookie-consent' ); ?></h2>
			<p class="kukie-card-description">
				<?php esc_html_e( 'Pass consent signals to Microsoft Advertising Universal Event Tracking. When enabled, visitor consent choices are forwarded to Microsoft UET tags so they can adjust their data-collection behaviour.', 'kukie-cookie-consent' ); ?>
			</p>

			<div class="kukie-form-row">
				<div class="kukie-form-row-label">
					<span><?php esc_html_e( 'Enable Microsoft UET consent signals', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-form-row-hint"><?php esc_html_e( 'Pass consent signals to Microsoft Advertising Universal Event Tracking.', 'kukie-cookie-consent' ); ?></span>
				</div>
				<label class="kukie-toggle">
					<input type="checkbox" name="ms_uet_enabled" id="kukie-uet-enabled" value="1">
					<span class="kukie-toggle-slider"></span>
				</label>
			</div>
		</div>

		<!-- Save -->
		<div class="kukie-form-actions">
			<button type="submit" class="kukie-btn-primary" id="kukie-uet-save">
				<span class="kukie-btn-text"><?php esc_html_e( 'Save Settings', 'kukie-cookie-consent' ); ?></span>
				<span class="kukie-btn-loading" style="display:none;">
					<span class="kukie-spinner"></span>
					<?php esc_html_e( 'Saving...', 'kukie-cookie-consent' ); ?>
				</span>
			</button>
		</div>
	</form>
</div>
