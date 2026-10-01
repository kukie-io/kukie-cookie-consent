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
	<?php esc_html_e( 'Loading settings…', 'kukie-cookie-consent' ); ?>
</div>

<form id="kukie-behaviour-form" hidden>
	<div class="kukie-card">
		<h2 class="kukie-card-title"><?php esc_html_e( 'Behaviour', 'kukie-cookie-consent' ); ?></h2>
		<p class="kukie-card-description"><?php esc_html_e( 'Privacy signals, script blocking and what the banner does after a visitor chooses.', 'kukie-cookie-consent' ); ?></p>

		<div class="kukie-form-row" id="kukie-branding-row">
			<div class="kukie-form-row-label">
				<span id="kukie-show-branding-label"><?php esc_html_e( 'Show branding', 'kukie-cookie-consent' ); ?> <span class="kukie-lock-badge" id="kukie-branding-locked" hidden><span class="dashicons dashicons-lock" aria-hidden="true"></span><?php esc_html_e( 'Higher plans', 'kukie-cookie-consent' ); ?></span></span>
				<span class="kukie-form-row-hint" id="kukie-show-branding-hint"><?php esc_html_e( "When on, the banner shows 'Powered by Kukie.io'. Hiding it needs a plan that includes branding removal.", 'kukie-cookie-consent' ); ?></span>
			</div>
			<label class="kukie-toggle">
				<input type="checkbox" role="switch" id="kukie-show-branding" value="1" aria-labelledby="kukie-show-branding-label" aria-describedby="kukie-show-branding-hint">
				<span class="kukie-toggle-slider" aria-hidden="true"></span>
			</label>
		</div>

		<div class="kukie-form-row">
			<div class="kukie-form-row-label">
				<span id="kukie-auto-block-label"><?php esc_html_e( 'Auto-block scripts', 'kukie-cookie-consent' ); ?></span>
				<span class="kukie-form-row-hint" id="kukie-auto-block-hint"><?php esc_html_e( 'When on, the banner holds back known trackers, such as Google Analytics and the Meta Pixel, until the visitor agrees. It is best effort: a browser cannot cancel a script other code has already run. To be certain, tag the script yourself with type="text/plain" and data-cc-category.', 'kukie-cookie-consent' ); ?></span>
			</div>
			<label class="kukie-toggle">
				<input type="checkbox" role="switch" id="kukie-auto-block" value="1" aria-labelledby="kukie-auto-block-label" aria-describedby="kukie-auto-block-hint">
				<span class="kukie-toggle-slider" aria-hidden="true"></span>
			</label>
		</div>

		<div class="kukie-form-row">
			<div class="kukie-form-row-label">
				<span id="kukie-respect-dnt-label"><?php esc_html_e( 'Respect Do Not Track', 'kukie-cookie-consent' ); ?></span>
				<span class="kukie-form-row-hint" id="kukie-respect-dnt-hint"><?php esc_html_e( 'When on, a browser that sends Do Not Track is treated as rejecting everything except essential cookies.', 'kukie-cookie-consent' ); ?></span>
			</div>
			<label class="kukie-toggle">
				<input type="checkbox" role="switch" id="kukie-respect-dnt" value="1" aria-labelledby="kukie-respect-dnt-label" aria-describedby="kukie-respect-dnt-hint">
				<span class="kukie-toggle-slider" aria-hidden="true"></span>
			</label>
		</div>

		<div class="kukie-form-row">
			<div class="kukie-form-row-label">
				<span id="kukie-respect-gpc-label"><?php esc_html_e( 'Respect Global Privacy Control', 'kukie-cookie-consent' ); ?></span>
				<span class="kukie-form-row-hint" id="kukie-respect-gpc-hint"><?php esc_html_e( 'When on, a browser that sends Global Privacy Control is treated as rejecting everything except essential cookies.', 'kukie-cookie-consent' ); ?></span>
			</div>
			<label class="kukie-toggle">
				<input type="checkbox" role="switch" id="kukie-respect-gpc" value="1" aria-labelledby="kukie-respect-gpc-label" aria-describedby="kukie-respect-gpc-hint">
				<span class="kukie-toggle-slider" aria-hidden="true"></span>
			</label>
		</div>

		<div class="kukie-form-row">
			<div class="kukie-form-row-label">
				<span id="kukie-reload-label"><?php esc_html_e( 'Reload on consent', 'kukie-cookie-consent' ); ?></span>
				<span class="kukie-form-row-hint" id="kukie-reload-hint"><?php esc_html_e( 'When on, the page reloads after a visitor makes or changes a choice.', 'kukie-cookie-consent' ); ?></span>
			</div>
			<label class="kukie-toggle">
				<input type="checkbox" role="switch" id="kukie-reload-on-consent" value="1" aria-labelledby="kukie-reload-label" aria-describedby="kukie-reload-hint">
				<span class="kukie-toggle-slider" aria-hidden="true"></span>
			</label>
		</div>

		<div class="kukie-form-row kukie-form-row--last">
			<div class="kukie-form-row-label">
				<span id="kukie-overlay-label"><?php esc_html_e( 'Show background overlay', 'kukie-cookie-consent' ); ?></span>
				<span class="kukie-form-row-hint" id="kukie-overlay-hint"><?php esc_html_e( 'When on, the page behind the banner is dimmed. A floating banner shows no overlay unless this is on.', 'kukie-cookie-consent' ); ?></span>
			</div>
			<label class="kukie-toggle">
				<input type="checkbox" role="switch" id="kukie-show-overlay" value="1" aria-labelledby="kukie-overlay-label" aria-describedby="kukie-overlay-hint">
				<span class="kukie-toggle-slider" aria-hidden="true"></span>
			</label>
		</div>
	</div>

	<div class="kukie-card">
		<h2 class="kukie-card-title"><?php esc_html_e( 'Disabled pages', 'kukie-cookie-consent' ); ?></h2>
		<p class="kukie-card-description" id="kukie-disabled-pages-hint"><?php esc_html_e( 'The banner and its revisit button are hidden on pages that match these patterns (* matches anything). Script blocking, embed placeholders and stored choices still work there.', 'kukie-cookie-consent' ); ?></p>
		<div class="kukie-form-group">
			<label for="kukie-disabled-pages"><?php esc_html_e( 'URL patterns, one per line', 'kukie-cookie-consent' ); ?></label>
			<textarea id="kukie-disabled-pages" class="kukie-input kukie-textarea" rows="4" placeholder="/checkout/*&#10;/account" aria-describedby="kukie-disabled-pages-hint"></textarea>
		</div>
	</div>

	<div class="kukie-form-actions">
		<button type="submit" class="kukie-btn-primary" id="kukie-behaviour-save">
			<span class="kukie-btn-text"><?php esc_html_e( 'Save changes', 'kukie-cookie-consent' ); ?></span>
			<span class="kukie-btn-loading" hidden>
				<span class="kukie-spinner" aria-hidden="true"></span>
				<?php esc_html_e( 'Saving…', 'kukie-cookie-consent' ); ?>
			</span>
		</button>
	</div>
</form>
