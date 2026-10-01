<?php
/**
 * Consent banner > Preferences modal tab (a partial of admin-banner.php): the
 * Banner Editor's Preferences modal settings - layout, button order,
 * background overlay, policy links and the title icon. Values load from and
 * save to Kukie.io (the `preferences_modal` block of GET/PUT /settings); the
 * per-language texts and the modal's button colours stay on the dashboard.
 *
 * @since 1.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$kukie_plugin  = Kukie_Plugin::instance();
$kukie_site_id = absint( $kukie_plugin->get_option( 'site_id', 0 ) );
$kukie_app_url = 'https://app.kukie.io';
?>
<div id="kukie-modal-error" class="notice notice-error inline kukie-notice" role="alert" hidden><p></p></div>

<div id="kukie-modal-loading" class="kukie-loading" role="status">
	<span class="kukie-spinner" aria-hidden="true"></span>
	<?php esc_html_e( 'Loading settings…', 'kukie-cookie-consent' ); ?>
</div>

<form id="kukie-modal-form" novalidate hidden>
	<div class="kukie-design-layout">
		<div class="kukie-design-controls">

			<!-- Layout -->
			<div class="kukie-card">
				<h2 class="kukie-card-title"><?php esc_html_e( 'Layout', 'kukie-cookie-consent' ); ?></h2>
				<p class="kukie-card-description"><?php esc_html_e( 'The size of the preferences modal and how its buttons are arranged.', 'kukie-cookie-consent' ); ?></p>

				<div class="kukie-form-group">
					<label for="kukie-modal-width"><?php esc_html_e( 'Modal width (px)', 'kukie-cookie-consent' ); ?></label>
					<input type="number" id="kukie-modal-width" class="kukie-input kukie-input--short" min="320" max="1200" step="10" placeholder="600" inputmode="numeric" aria-describedby="kukie-modal-width-hint">
					<p class="kukie-help-text" id="kukie-modal-width-hint"><?php esc_html_e( 'The widest the modal gets on a computer, from 320 to 1200. Leave it empty for 600. On phones the modal always fills the screen.', 'kukie-cookie-consent' ); ?></p>
				</div>

				<fieldset class="kukie-fieldset kukie-form-group">
					<legend class="kukie-legend"><?php esc_html_e( 'Button layout', 'kukie-cookie-consent' ); ?></legend>
					<div class="kukie-layout-grid">
						<label class="kukie-layout-option">
							<input type="radio" name="kukie_modal_layout" value="row" checked>
							<span class="kukie-layout-option-body">
								<strong><?php esc_html_e( 'One row', 'kukie-cookie-consent' ); ?></strong>
								<span><?php esc_html_e( 'All three buttons on one line, in the order below. Phones still stack them.', 'kukie-cookie-consent' ); ?></span>
							</span>
						</label>
						<label class="kukie-layout-option">
							<input type="radio" name="kukie_modal_layout" value="stacked">
							<span class="kukie-layout-option-body">
								<strong><?php esc_html_e( 'Stacked', 'kukie-cookie-consent' ); ?></strong>
								<span><?php esc_html_e( 'The first two buttons side by side, the third full width below.', 'kukie-cookie-consent' ); ?></span>
							</span>
						</label>
					</div>
				</fieldset>

				<fieldset class="kukie-fieldset kukie-form-group kukie-form-group--last">
					<legend class="kukie-legend"><?php esc_html_e( 'Button order', 'kukie-cookie-consent' ); ?></legend>
					<ol class="kukie-order-list" id="kukie-modal-order" aria-describedby="kukie-modal-order-hint"></ol>
					<p class="kukie-help-text" id="kukie-modal-order-hint"><?php esc_html_e( 'Use the arrows to move a button. The preview shows the result.', 'kukie-cookie-consent' ); ?></p>
					<p class="screen-reader-text" id="kukie-modal-order-status" role="status" aria-live="polite"></p>
				</fieldset>
			</div>

			<!-- Background overlay -->
			<div class="kukie-card">
				<fieldset class="kukie-fieldset">
					<legend class="kukie-card-title"><?php esc_html_e( 'Background overlay', 'kukie-cookie-consent' ); ?></legend>
					<p class="kukie-card-description" id="kukie-modal-overlay-hint"><?php esc_html_e( 'Dims the page behind the modal while it is open.', 'kukie-cookie-consent' ); ?></p>
					<div class="kukie-position-group">
						<label class="kukie-position-pill">
							<input type="radio" name="kukie_modal_overlay" value="banner" checked aria-describedby="kukie-modal-overlay-hint">
							<span id="kukie-modal-overlay-follow"><?php esc_html_e( 'Same as the banner', 'kukie-cookie-consent' ); ?></span>
						</label>
						<label class="kukie-position-pill">
							<input type="radio" name="kukie_modal_overlay" value="on" aria-describedby="kukie-modal-overlay-hint">
							<span><?php esc_html_e( 'Show', 'kukie-cookie-consent' ); ?></span>
						</label>
						<label class="kukie-position-pill">
							<input type="radio" name="kukie_modal_overlay" value="off" aria-describedby="kukie-modal-overlay-hint">
							<span><?php esc_html_e( 'Hide', 'kukie-cookie-consent' ); ?></span>
						</label>
					</div>
				</fieldset>
			</div>

			<!-- Content -->
			<div class="kukie-card">
				<h2 class="kukie-card-title"><?php esc_html_e( 'Content', 'kukie-cookie-consent' ); ?></h2>

				<div class="kukie-form-row">
					<div class="kukie-form-row-label">
						<span id="kukie-modal-links-label"><?php esc_html_e( 'Show policy links', 'kukie-cookie-consent' ); ?></span>
						<span class="kukie-form-row-hint" id="kukie-modal-links-hint"><?php esc_html_e( 'When on, the privacy policy and cookie policy links appear under the description. Which links exist, and where they point, is set on Kukie.io.', 'kukie-cookie-consent' ); ?></span>
					</div>
					<label class="kukie-toggle">
						<input type="checkbox" role="switch" id="kukie-modal-links" value="1" aria-labelledby="kukie-modal-links-label" aria-describedby="kukie-modal-links-hint">
						<span class="kukie-toggle-slider" aria-hidden="true"></span>
					</label>
				</div>

				<div class="kukie-form-row" id="kukie-modal-icon-row">
					<div class="kukie-form-row-label">
						<span id="kukie-modal-icon-label"><?php esc_html_e( 'Show an icon next to the title', 'kukie-cookie-consent' ); ?> <span class="kukie-lock-badge" id="kukie-modal-icon-locked" hidden><span class="dashicons dashicons-lock" aria-hidden="true"></span><?php esc_html_e( 'Higher plans', 'kukie-cookie-consent' ); ?></span></span>
						<span class="kukie-form-row-hint" id="kukie-modal-icon-hint"><?php esc_html_e( 'The cookie icon, or your own logo below. Hiding it or using a logo needs a plan that includes branding removal.', 'kukie-cookie-consent' ); ?></span>
					</div>
					<label class="kukie-toggle">
						<input type="checkbox" role="switch" id="kukie-modal-icon" value="1" aria-labelledby="kukie-modal-icon-label" aria-describedby="kukie-modal-icon-hint">
						<span class="kukie-toggle-slider" aria-hidden="true"></span>
					</label>
				</div>

				<div id="kukie-modal-logo-fields">
					<div class="kukie-form-group">
						<label for="kukie-modal-logo"><?php esc_html_e( 'Logo address (optional)', 'kukie-cookie-consent' ); ?></label>
						<input type="url" id="kukie-modal-logo" class="kukie-input" maxlength="500" placeholder="https://example.com/logo.svg" spellcheck="false" aria-describedby="kukie-modal-logo-hint">
						<p class="kukie-help-text" id="kukie-modal-logo-hint"><?php esc_html_e( 'Replaces the cookie icon in the modal only. Leave it empty to keep the icon.', 'kukie-cookie-consent' ); ?></p>
					</div>
					<div class="kukie-form-grid">
						<div class="kukie-form-group">
							<label for="kukie-modal-logo-width"><?php esc_html_e( 'Logo width (px)', 'kukie-cookie-consent' ); ?></label>
							<input type="number" id="kukie-modal-logo-width" class="kukie-input" min="40" max="100" value="60" inputmode="numeric" aria-describedby="kukie-modal-logo-width-hint">
							<p class="kukie-help-text" id="kukie-modal-logo-width-hint"><?php esc_html_e( 'From 40 to 100.', 'kukie-cookie-consent' ); ?></p>
						</div>
						<div class="kukie-form-group">
							<label for="kukie-modal-logo-radius"><?php esc_html_e( 'Logo corner radius (px)', 'kukie-cookie-consent' ); ?></label>
							<input type="number" id="kukie-modal-logo-radius" class="kukie-input" min="0" max="50" value="0" inputmode="numeric" aria-describedby="kukie-modal-logo-radius-hint">
							<p class="kukie-help-text" id="kukie-modal-logo-radius-hint"><?php esc_html_e( 'From 0 to 50.', 'kukie-cookie-consent' ); ?></p>
						</div>
					</div>
				</div>
			</div>

			<div class="kukie-form-actions">
				<button type="submit" class="kukie-btn-primary" id="kukie-modal-save">
					<span class="kukie-btn-text"><?php esc_html_e( 'Save changes', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-btn-loading" hidden>
						<span class="kukie-spinner" aria-hidden="true"></span>
						<?php esc_html_e( 'Saving…', 'kukie-cookie-consent' ); ?>
					</span>
				</button>
			</div>

			<div class="kukie-cta-banner">
				<span class="dashicons dashicons-admin-customizer" aria-hidden="true"></span>
				<p>
					<?php
					printf(
						/* translators: %s: link to the Preferences modal tab of the banner editor on Kukie.io */
						esc_html__( 'The modal title and description in each language, and its own button colours, are in %s.', 'kukie-cookie-consent' ),
						'<a href="' . esc_url( $kukie_app_url . '/sites/' . $kukie_site_id . '/banner?tab=preferences' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'the banner editor on Kukie.io', 'kukie-cookie-consent' ) . Kukie_Admin::new_tab_marker() . '</a>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every part escaped above
					);
					?>
				</p>
			</div>
		</div>

		<!-- Preview: a sketch of the modal's layout, order and title icon -->
		<div class="kukie-design-preview">
			<div class="kukie-card kukie-preview-card">
				<div class="kukie-preview-header">
					<h2 class="kukie-card-title"><?php esc_html_e( 'Preview', 'kukie-cookie-consent' ); ?></h2>
				</div>
				<div class="kukie-modal-preview" id="kukie-modal-preview" data-overlay="1" aria-hidden="true">
					<div class="kukie-mpv">
						<div class="kukie-mpv-head">
							<span class="kukie-mpv-icon" id="kukie-mpv-icon"></span>
							<strong><?php esc_html_e( 'Cookie preferences', 'kukie-cookie-consent' ); ?></strong>
						</div>
						<span class="kukie-mpv-line"></span>
						<span class="kukie-mpv-line kukie-mpv-line--short"></span>
						<span class="kukie-mpv-links" id="kukie-mpv-links"><?php esc_html_e( 'Privacy policy', 'kukie-cookie-consent' ); ?> &middot; <?php esc_html_e( 'Cookie policy', 'kukie-cookie-consent' ); ?></span>
						<div class="kukie-mpv-list">
							<span class="kukie-mpv-row"></span>
							<span class="kukie-mpv-row"></span>
							<span class="kukie-mpv-row"></span>
						</div>
						<div class="kukie-mpv-btns" id="kukie-mpv-btns" data-layout="row"></div>
					</div>
				</div>
				<p class="kukie-preview-note"><?php esc_html_e( 'A sketch of the layout and button order. Colours and texts come from Kukie.io.', 'kukie-cookie-consent' ); ?></p>
			</div>
		</div>
	</div>
</form>
