<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$kukie_plugin  = Kukie_Plugin::instance();
$kukie_site_id = absint( $kukie_plugin->get_option( 'site_id', 0 ) );
$kukie_app_url = 'https://app.kukie.io';
?>
<div class="wrap kukie-wrap">
	<div class="kukie-header">
		<h1><?php esc_html_e( 'Kukie.io Settings', 'kukie-cookie-consent' ); ?></h1>
	</div>
	<hr class="wp-header-end">

	<div id="kukie-settings-error" class="notice notice-error inline kukie-notice" role="alert" hidden><p></p></div>

	<div id="kukie-settings-loading" class="kukie-loading" role="status">
		<span class="kukie-spinner" aria-hidden="true"></span>
		<?php esc_html_e( 'Loading settings...', 'kukie-cookie-consent' ); ?>
	</div>

	<div id="kukie-settings-content" hidden>
		<!-- Connection Info -->
		<div class="kukie-card">
			<h2 class="kukie-card-title"><?php esc_html_e( 'Connection', 'kukie-cookie-consent' ); ?></h2>
			<div class="kukie-info-grid">
				<div class="kukie-info-row">
					<span class="kukie-info-label"><?php esc_html_e( 'Status', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-info-value">
						<span class="kukie-status-dot kukie-status-dot--connected" aria-hidden="true"></span>
						<?php esc_html_e( 'Connected', 'kukie-cookie-consent' ); ?>
					</span>
				</div>
				<div class="kukie-info-row">
					<span class="kukie-info-label"><?php esc_html_e( 'Organisation', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-info-value" id="kukie-conn-org"><?php echo esc_html( $kukie_plugin->get_option( 'organisation', '' ) ); ?></span>
				</div>
				<div class="kukie-info-row">
					<span class="kukie-info-label"><?php esc_html_e( 'Plan', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-info-value" id="kukie-conn-plan"><?php echo esc_html( $kukie_plugin->get_option( 'plan_name', 'Free' ) ); ?></span>
				</div>
				<div class="kukie-info-row">
					<span class="kukie-info-label"><?php esc_html_e( 'Domain', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-info-value" id="kukie-conn-domain"><?php echo esc_html( $kukie_plugin->get_option( 'domain', '' ) ); ?></span>
				</div>
				<div class="kukie-info-row">
					<span class="kukie-info-label"><?php esc_html_e( 'Site Key', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-info-value"><code><?php echo esc_html( $kukie_plugin->get_option( 'site_key', '' ) ); ?></code></span>
				</div>
			</div>

			<div class="kukie-disconnect-section">
				<button type="button" id="kukie-disconnect-btn" class="kukie-btn-danger" aria-describedby="kukie-disconnect-hint">
					<?php esc_html_e( 'Disconnect from Kukie.io', 'kukie-cookie-consent' ); ?>
				</button>
				<p class="kukie-help-text" id="kukie-disconnect-hint"><?php esc_html_e( 'This will remove the cookie consent banner from your site.', 'kukie-cookie-consent' ); ?></p>
			</div>
		</div>

		<!-- Banner Script -->
		<form id="kukie-settings-form">
			<div class="kukie-card">
				<div class="kukie-card-header">
					<div class="kukie-form-row-label">
						<h2 class="kukie-card-title" id="kukie-banner-enabled-label"><?php esc_html_e( 'Banner Script', 'kukie-cookie-consent' ); ?></h2>
						<span class="kukie-form-row-hint" id="kukie-banner-enabled-hint"><?php esc_html_e( 'Adds the Kukie consent banner to every public page of this site.', 'kukie-cookie-consent' ); ?></span>
					</div>
					<label class="kukie-toggle">
						<input type="checkbox" role="switch" name="banner_enabled" id="kukie-banner-enabled" value="1" aria-labelledby="kukie-banner-enabled-label" aria-describedby="kukie-banner-enabled-hint">
						<span class="kukie-toggle-slider" aria-hidden="true"></span>
					</label>
				</div>

				<fieldset class="kukie-fieldset kukie-form-group">
					<legend class="kukie-legend"><?php esc_html_e( 'Script Position', 'kukie-cookie-consent' ); ?></legend>
					<p class="kukie-help-text kukie-help-text--above" id="kukie-script-position-hint"><?php esc_html_e( 'Where the plugin adds the banner script to your pages. The page head is right for almost every site.', 'kukie-cookie-consent' ); ?></p>
					<div class="kukie-option-list">
						<label class="kukie-option">
							<input type="radio" name="script_position" value="head" checked aria-describedby="kukie-script-position-hint">
							<span class="kukie-option-figure kukie-option-figure--head" aria-hidden="true"><span></span><span></span><span></span><span></span></span>
							<span class="kukie-option-body">
								<span class="kukie-option-title"><?php esc_html_e( 'In the page head', 'kukie-cookie-consent' ); ?> <code>&lt;head&gt;</code> <span class="kukie-option-badge"><?php esc_html_e( 'Recommended', 'kukie-cookie-consent' ); ?></span></span>
								<span class="kukie-option-desc"><?php esc_html_e( 'Loads before the rest of the page, so cookies are blocked as early as possible.', 'kukie-cookie-consent' ); ?></span>
							</span>
							<span class="kukie-option-check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
						</label>
						<label class="kukie-option">
							<input type="radio" name="script_position" value="body" aria-describedby="kukie-script-position-hint">
							<span class="kukie-option-figure kukie-option-figure--body" aria-hidden="true"><span></span><span></span><span></span><span></span></span>
							<span class="kukie-option-body">
								<span class="kukie-option-title"><?php esc_html_e( 'At the end of the page', 'kukie-cookie-consent' ); ?> <code>&lt;body&gt;</code></span>
								<span class="kukie-option-desc"><?php esc_html_e( 'Use it only if another script in the head interferes with the banner. The banner appears a moment later.', 'kukie-cookie-consent' ); ?></span>
							</span>
							<span class="kukie-option-check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
						</label>
						<label class="kukie-option">
							<input type="radio" name="script_position" value="manual" aria-describedby="kukie-script-position-hint">
							<span class="kukie-option-figure kukie-option-figure--manual" aria-hidden="true"><span class="dashicons dashicons-editor-code"></span></span>
							<span class="kukie-option-body">
								<span class="kukie-option-title"><?php esc_html_e( 'Manual embed', 'kukie-cookie-consent' ); ?></span>
								<span class="kukie-option-desc"><?php esc_html_e( 'The plugin adds nothing. Paste the embed code below into your theme yourself.', 'kukie-cookie-consent' ); ?></span>
							</span>
							<span class="kukie-option-check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
						</label>
					</div>
				</fieldset>

				<div id="kukie-manual-embed" class="kukie-embed-code" hidden>
					<span class="kukie-legend" id="kukie-embed-code-label"><?php esc_html_e( 'Embed Code', 'kukie-cookie-consent' ); ?></span>
					<code id="kukie-embed-code-display" aria-labelledby="kukie-embed-code-label" tabindex="0"></code>
					<p class="kukie-help-text"><?php esc_html_e( 'Add this code to your theme header template.', 'kukie-cookie-consent' ); ?></p>
				</div>

				<div class="kukie-form-row kukie-form-row--last">
					<div class="kukie-form-row-label">
						<span id="kukie-verify-label"><?php esc_html_e( 'Verification', 'kukie-cookie-consent' ); ?></span>
						<span class="kukie-form-row-hint" id="kukie-verified-status" aria-live="polite"><?php esc_html_e( 'Check if the banner script is detected on your site.', 'kukie-cookie-consent' ); ?></span>
					</div>
					<button type="button" id="kukie-verify-btn" class="kukie-btn-secondary kukie-btn-sm" aria-describedby="kukie-verified-status">
						<span class="kukie-btn-text"><?php esc_html_e( 'Verify', 'kukie-cookie-consent' ); ?></span>
						<span class="kukie-btn-loading" hidden>
							<span class="kukie-spinner" aria-hidden="true"></span>
						</span>
					</button>
				</div>
			</div>

			<!-- Save -->
			<div class="kukie-form-actions">
				<button type="submit" class="kukie-btn-primary" id="kukie-settings-save">
					<span class="kukie-btn-text"><?php esc_html_e( 'Save Settings', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-btn-loading" hidden>
						<span class="kukie-spinner" aria-hidden="true"></span>
						<?php esc_html_e( 'Saving...', 'kukie-cookie-consent' ); ?>
					</span>
				</button>
			</div>
		</form>
	</div>
</div>
