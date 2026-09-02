<?php
/**
 * Consent banner > Behaviour tab (a partial of admin-banner.php): the Banner
 * Editor's Behaviour settings. Values load from and save to Kukie.io.
 *
 * @since 1.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="kukie-behaviour-error" class="notice notice-error inline kukie-notice" role="alert" hidden><p></p></div>

<div id="kukie-behaviour-loading" class="kukie-loading" role="status">
	<span class="kukie-spinner" aria-hidden="true"></span>
	<?php esc_html_e( 'Loading settings...', 'kukie-cookie-consent' ); ?>
</div>

<form id="kukie-behaviour-form" hidden>
	<div class="kukie-card">
		<h2 class="kukie-card-title"><?php esc_html_e( 'Behaviour', 'kukie-cookie-consent' ); ?></h2>
		<p class="kukie-card-description"><?php esc_html_e( 'Privacy signals, script blocking, and how the banner reacts to consent.', 'kukie-cookie-consent' ); ?></p>

		<div class="kukie-form-row" id="kukie-branding-row">
			<div class="kukie-form-row-label">
				<span id="kukie-show-branding-label"><?php esc_html_e( 'Show Branding', 'kukie-cookie-consent' ); ?> <span class="kukie-lock-badge" id="kukie-branding-locked" hidden><span class="dashicons dashicons-lock" aria-hidden="true"></span><?php esc_html_e( 'Higher plans', 'kukie-cookie-consent' ); ?></span></span>
				<span class="kukie-form-row-hint" id="kukie-show-branding-hint"><?php esc_html_e( 'Display "Powered by Kukie.io" branding. Hiding it is available on plans that include branding removal.', 'kukie-cookie-consent' ); ?></span>
			</div>
			<label class="kukie-toggle">
				<input type="checkbox" role="switch" id="kukie-show-branding" value="1" aria-labelledby="kukie-show-branding-label" aria-describedby="kukie-show-branding-hint">
				<span class="kukie-toggle-slider" aria-hidden="true"></span>
			</label>
		</div>

		<div class="kukie-form-row">
			<div class="kukie-form-row-label">
				<span id="kukie-auto-block-label"><?php esc_html_e( 'Auto-block Scripts', 'kukie-cookie-consent' ); ?></span>
				<span class="kukie-form-row-hint" id="kukie-auto-block-hint"><?php esc_html_e( 'Best-effort blocking of known tracker scripts (Google Analytics, Facebook Pixel, etc.) until the visitor gives consent. Works with the opt-in consent model. Browsers cannot cancel scripts that were already inserted by other code, so for guaranteed blocking tag the script manually with type="text/plain" and data-cc-category.', 'kukie-cookie-consent' ); ?></span>
			</div>
			<label class="kukie-toggle">
				<input type="checkbox" role="switch" id="kukie-auto-block" value="1" aria-labelledby="kukie-auto-block-label" aria-describedby="kukie-auto-block-hint">
				<span class="kukie-toggle-slider" aria-hidden="true"></span>
			</label>
		</div>

		<div class="kukie-form-row">
			<div class="kukie-form-row-label">
				<span id="kukie-respect-dnt-label"><?php esc_html_e( 'Respect Do Not Track', 'kukie-cookie-consent' ); ?></span>
				<span class="kukie-form-row-hint" id="kukie-respect-dnt-hint"><?php esc_html_e( 'Auto-reject non-essential cookies when the browser sends the DNT signal.', 'kukie-cookie-consent' ); ?></span>
			</div>
			<label class="kukie-toggle">
				<input type="checkbox" role="switch" id="kukie-respect-dnt" value="1" aria-labelledby="kukie-respect-dnt-label" aria-describedby="kukie-respect-dnt-hint">
				<span class="kukie-toggle-slider" aria-hidden="true"></span>
			</label>
		</div>

		<div class="kukie-form-row">
			<div class="kukie-form-row-label">
				<span id="kukie-respect-gpc-label"><?php esc_html_e( 'Respect Global Privacy Control', 'kukie-cookie-consent' ); ?></span>
				<span class="kukie-form-row-hint" id="kukie-respect-gpc-hint"><?php esc_html_e( 'Auto-reject when a GPC signal is detected.', 'kukie-cookie-consent' ); ?></span>
			</div>
			<label class="kukie-toggle">
				<input type="checkbox" role="switch" id="kukie-respect-gpc" value="1" aria-labelledby="kukie-respect-gpc-label" aria-describedby="kukie-respect-gpc-hint">
				<span class="kukie-toggle-slider" aria-hidden="true"></span>
			</label>
		</div>

		<div class="kukie-form-row">
			<div class="kukie-form-row-label">
				<span id="kukie-reload-label"><?php esc_html_e( 'Reload on Consent', 'kukie-cookie-consent' ); ?></span>
				<span class="kukie-form-row-hint" id="kukie-reload-hint"><?php esc_html_e( 'Reload the page after consent is given or updated.', 'kukie-cookie-consent' ); ?></span>
			</div>
			<label class="kukie-toggle">
				<input type="checkbox" role="switch" id="kukie-reload-on-consent" value="1" aria-labelledby="kukie-reload-label" aria-describedby="kukie-reload-hint">
				<span class="kukie-toggle-slider" aria-hidden="true"></span>
			</label>
		</div>

		<div class="kukie-form-row kukie-form-row--last">
			<div class="kukie-form-row-label">
				<span id="kukie-overlay-label"><?php esc_html_e( 'Show Background Overlay', 'kukie-cookie-consent' ); ?></span>
				<span class="kukie-form-row-hint" id="kukie-overlay-hint"><?php esc_html_e( 'Dims the page behind the banner. Floating banners show no overlay unless this is on.', 'kukie-cookie-consent' ); ?></span>
			</div>
			<label class="kukie-toggle">
				<input type="checkbox" role="switch" id="kukie-show-overlay" value="1" aria-labelledby="kukie-overlay-label" aria-describedby="kukie-overlay-hint">
				<span class="kukie-toggle-slider" aria-hidden="true"></span>
			</label>
		</div>
	</div>

	<div class="kukie-card">
		<h2 class="kukie-card-title"><?php esc_html_e( 'Disabled Pages', 'kukie-cookie-consent' ); ?></h2>
		<p class="kukie-card-description" id="kukie-disabled-pages-hint"><?php esc_html_e( 'The banner and its reopen button are hidden on these URL patterns (supports * wildcards), but consent enforcement still applies: script blocking, embed placeholders and stored consent keep working.', 'kukie-cookie-consent' ); ?></p>
		<div class="kukie-form-group">
			<label for="kukie-disabled-pages"><?php esc_html_e( 'URL patterns, one per line', 'kukie-cookie-consent' ); ?></label>
			<textarea id="kukie-disabled-pages" class="kukie-input kukie-textarea" rows="4" placeholder="/checkout/*&#10;/account" aria-describedby="kukie-disabled-pages-hint"></textarea>
		</div>
	</div>

	<div class="kukie-form-actions">
		<button type="submit" class="kukie-btn-primary" id="kukie-behaviour-save">
			<span class="kukie-btn-text"><?php esc_html_e( 'Save Settings', 'kukie-cookie-consent' ); ?></span>
			<span class="kukie-btn-loading" hidden>
				<span class="kukie-spinner" aria-hidden="true"></span>
				<?php esc_html_e( 'Saving...', 'kukie-cookie-consent' ); ?>
			</span>
		</button>
	</div>
</form>
