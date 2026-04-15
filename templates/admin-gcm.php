<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$kukie_plugin = Kukie_Plugin::instance();
?>
<div class="wrap kukie-wrap">
	<div class="kukie-header">
		<h1><?php esc_html_e( 'Google Consent Mode v2', 'kukie-cookie-consent' ); ?></h1>
		<a href="<?php echo esc_url( $kukie_plugin->get_option( 'dashboard_url', 'https://app.kukie.io' ) ); ?>" target="_blank" rel="noopener noreferrer" class="kukie-external-link">
			<?php esc_html_e( 'Full settings', 'kukie-cookie-consent' ); ?>
			<span class="dashicons dashicons-external"></span>
		</a>
	</div>
	<hr class="wp-header-end">

	<div id="kukie-gcm-error" class="kukie-notice kukie-notice-error" style="display:none;"></div>
	<div id="kukie-gcm-success" class="kukie-notice kukie-notice-success" style="display:none;"></div>

	<div id="kukie-gcm-loading" class="kukie-loading">
		<span class="kukie-spinner"></span>
		<?php esc_html_e( 'Loading settings...', 'kukie-cookie-consent' ); ?>
	</div>

	<form id="kukie-gcm-form" style="display:none;">
		<!-- GCM v2 -->
		<div class="kukie-card">
			<h2 class="kukie-card-title"><?php esc_html_e( 'Google Consent Mode v2', 'kukie-cookie-consent' ); ?></h2>
			<p class="kukie-card-description">
				<?php esc_html_e( 'Enable Google Consent Mode v2 to pass visitor consent choices to Google tags (Analytics, Ads, GTM). Required by Google Ads for audience building and remarketing in the EEA under Google\'s platform policies.', 'kukie-cookie-consent' ); ?>
			</p>

			<div class="kukie-form-row">
				<div class="kukie-form-row-label">
					<span><?php esc_html_e( 'Enable GCM v2', 'kukie-cookie-consent' ); ?></span>
				</div>
				<label class="kukie-toggle">
					<input type="checkbox" name="gcm_v2_enabled" id="kukie-gcm-enabled" value="1">
					<span class="kukie-toggle-slider"></span>
				</label>
			</div>
		</div>

		<!-- Default Consent State -->
		<div class="kukie-card">
			<h2 class="kukie-card-title"><?php esc_html_e( 'Default Consent State', 'kukie-cookie-consent' ); ?></h2>
			<p class="kukie-card-description">
				<?php esc_html_e( 'These are the default consent states before a user interacts with the banner. Managed through region rules in the Kukie.io dashboard.', 'kukie-cookie-consent' ); ?>
			</p>

			<table class="kukie-consent-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Category', 'kukie-cookie-consent' ); ?></th>
						<th><?php esc_html_e( 'Default State', 'kukie-cookie-consent' ); ?></th>
						<th><?php esc_html_e( 'Region', 'kukie-cookie-consent' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php esc_html_e( 'Analytics (analytics_storage)', 'kukie-cookie-consent' ); ?></td>
						<td><span class="kukie-badge kukie-badge--denied"><?php esc_html_e( 'Denied', 'kukie-cookie-consent' ); ?></span></td>
						<td><?php esc_html_e( 'All', 'kukie-cookie-consent' ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Advertisement (ad_storage)', 'kukie-cookie-consent' ); ?></td>
						<td><span class="kukie-badge kukie-badge--denied"><?php esc_html_e( 'Denied', 'kukie-cookie-consent' ); ?></span></td>
						<td><?php esc_html_e( 'All', 'kukie-cookie-consent' ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Functional (functionality_storage)', 'kukie-cookie-consent' ); ?></td>
						<td><span class="kukie-badge kukie-badge--denied"><?php esc_html_e( 'Denied', 'kukie-cookie-consent' ); ?></span></td>
						<td><?php esc_html_e( 'All', 'kukie-cookie-consent' ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Necessary (security_storage)', 'kukie-cookie-consent' ); ?></td>
						<td><span class="kukie-badge kukie-badge--granted"><?php esc_html_e( 'Granted', 'kukie-cookie-consent' ); ?></span></td>
						<td><?php esc_html_e( 'All', 'kukie-cookie-consent' ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>

		<!-- Auto-block -->
		<div class="kukie-card">
			<h2 class="kukie-card-title"><?php esc_html_e( 'Script Blocking', 'kukie-cookie-consent' ); ?></h2>

			<div class="kukie-form-row">
				<div class="kukie-form-row-label">
					<span><?php esc_html_e( 'Auto-block third-party scripts', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-form-row-hint"><?php esc_html_e( 'Automatically block known tracking scripts until consent is given.', 'kukie-cookie-consent' ); ?></span>
				</div>
				<label class="kukie-toggle">
					<input type="checkbox" name="auto_block_scripts" id="kukie-auto-block" value="1">
					<span class="kukie-toggle-slider"></span>
				</label>
			</div>
		</div>

		<!-- Save -->
		<div class="kukie-form-actions">
			<button type="submit" class="kukie-btn-primary" id="kukie-gcm-save">
				<span class="kukie-btn-text"><?php esc_html_e( 'Save Settings', 'kukie-cookie-consent' ); ?></span>
				<span class="kukie-btn-loading" style="display:none;">
					<span class="kukie-spinner"></span>
					<?php esc_html_e( 'Saving...', 'kukie-cookie-consent' ); ?>
				</span>
			</button>
		</div>
	</form>
</div>
