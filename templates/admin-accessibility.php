<?php
/**
 * Accessibility widget page. Every value here is read from and written to
 * app.kukie.io through the plugin API (GET/PUT /settings, `accessibility_widget`
 * block) - nothing is stored in WordPress, so the page can never disagree
 * with the Kukie.io dashboard. admin.js populates the form after a
 * successful load and renders the locked state when the plan lacks the
 * feature (the save button stays disabled in that state).
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
<div class="wrap kukie-wrap">
	<div class="kukie-header">
		<h1><?php esc_html_e( 'Accessibility widget', 'kukie-cookie-consent' ); ?></h1>
		<a href="<?php echo esc_url( $kukie_app_url . '/sites/' . $kukie_site_id . '/accessibility' ); ?>" target="_blank" rel="noopener noreferrer" class="kukie-external-link">
			<?php esc_html_e( 'Open on Kukie.io', 'kukie-cookie-consent' ); ?>
			<span class="dashicons dashicons-external" aria-hidden="true"></span>
		</a>
	</div>
	<hr class="wp-header-end">

	<div id="kukie-a11y-error" class="notice notice-error inline kukie-notice" role="alert" hidden><p></p></div>

	<div id="kukie-a11y-loading" class="kukie-loading" role="status">
		<span class="kukie-spinner" aria-hidden="true"></span>
		<?php esc_html_e( 'Loading accessibility widget settings...', 'kukie-cookie-consent' ); ?>
	</div>

	<div id="kukie-a11y-content" hidden>

		<!-- Locked state: the plan does not include the widget -->
		<div id="kukie-a11y-locked" class="kukie-card kukie-card--locked" hidden>
			<h2 class="kukie-card-title"><?php esc_html_e( 'Included on higher plans', 'kukie-cookie-consent' ); ?></h2>
			<p id="kukie-a11y-locked-text" class="kukie-card-description"></p>
			<p class="kukie-card-description">
				<?php esc_html_e( 'The widget adds a floating button to your site that opens a panel of reading, contrast and navigation aids for visitors - bigger text, a dyslexia-friendly font, high contrast, read aloud, one-tap profiles and more - delivered inside the same Kukie script as your cookie banner.', 'kukie-cookie-consent' ); ?>
			</p>
			<p id="kukie-a11y-still-on" class="kukie-card-description kukie-text-warning" hidden></p>
			<p class="kukie-card-actions-row">
				<a id="kukie-a11y-upgrade" href="<?php echo esc_url( $kukie_app_url . '/billing' ); ?>" target="_blank" rel="noopener noreferrer" class="kukie-btn-primary">
					<?php esc_html_e( 'Upgrade on Kukie.io', 'kukie-cookie-consent' ); ?>
					<span class="dashicons dashicons-external" aria-hidden="true"></span>
				</a>
				<button type="button" id="kukie-a11y-recheck" class="kukie-btn-secondary">
					<span class="kukie-btn-text"><?php esc_html_e( 'Already upgraded? Check again', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-btn-loading" hidden>
						<span class="kukie-spinner" aria-hidden="true"></span>
					</span>
				</button>
			</p>
		</div>

		<!-- Unlocked intro -->
		<div id="kukie-a11y-intro" class="kukie-card" hidden>
			<h2 class="kukie-card-title"><?php esc_html_e( 'What your visitors get', 'kukie-cookie-consent' ); ?></h2>
			<ul class="kukie-feature-list">
				<li><?php esc_html_e( '17 adjustments: bigger text, spacing, line height, dyslexia-friendly font, contrast modes, saturation, highlight links and titles, hide images, pause animations, mute sounds, big cursor, reading guide, read aloud and more.', 'kukie-cookie-consent' ); ?></li>
				<li><?php esc_html_e( '6 one-tap profiles (motor impaired, low vision, dyslexia, cognitive, ADHD, seizure-safe) and a page structure navigator.', 'kukie-cookie-consent' ); ?></li>
				<li><?php esc_html_e( 'The panel opens in the visitor\'s language, with 70+ built-in translations, and respects their system preferences for reduced motion and contrast.', 'kukie-cookie-consent' ); ?></li>
				<li><?php esc_html_e( 'Fully self-hosted inside the Kukie script: no third-party requests, and visitor preferences stay in their own browser.', 'kukie-cookie-consent' ); ?></li>
			</ul>
			<p class="kukie-card-description kukie-card-description--last">
				<?php esc_html_e( 'Changes take effect once the banner script cache refreshes - usually within a minute or two. If you still see the previous version, do a hard refresh in your browser.', 'kukie-cookie-consent' ); ?>
			</p>
		</div>

		<form id="kukie-a11y-form">
			<fieldset id="kukie-a11y-fields" class="kukie-fieldset">
				<legend class="screen-reader-text"><?php esc_html_e( 'Accessibility widget settings', 'kukie-cookie-consent' ); ?></legend>

				<!-- Activation -->
				<div class="kukie-card">
					<div class="kukie-form-row kukie-form-row--flush">
						<div class="kukie-form-row-label">
							<span id="kukie-a11y-enabled-label" class="kukie-card-title"><?php esc_html_e( 'Enable accessibility widget', 'kukie-cookie-consent' ); ?></span>
							<span class="kukie-form-row-hint" id="kukie-a11y-enabled-hint"><?php esc_html_e( 'Shows the widget button on every public page of this site.', 'kukie-cookie-consent' ); ?></span>
						</div>
						<label class="kukie-toggle">
							<input type="checkbox" role="switch" id="kukie-a11y-enabled" value="1" aria-labelledby="kukie-a11y-enabled-label" aria-describedby="kukie-a11y-enabled-hint">
							<span class="kukie-toggle-slider" aria-hidden="true"></span>
						</label>
					</div>
				</div>

				<!-- Appearance -->
				<div class="kukie-card">
					<h2 class="kukie-card-title"><?php esc_html_e( 'Appearance', 'kukie-cookie-consent' ); ?></h2>

					<fieldset class="kukie-fieldset kukie-form-group">
						<legend class="kukie-legend"><?php esc_html_e( 'Position', 'kukie-cookie-consent' ); ?></legend>
						<div class="kukie-position-group">
							<label class="kukie-position-pill">
								<input type="radio" name="kukie_a11y_position" value="bottom-right" checked>
								<span><?php esc_html_e( 'Bottom Right', 'kukie-cookie-consent' ); ?></span>
							</label>
							<label class="kukie-position-pill">
								<input type="radio" name="kukie_a11y_position" value="bottom-left">
								<span><?php esc_html_e( 'Bottom Left', 'kukie-cookie-consent' ); ?></span>
							</label>
						</div>
						<p class="kukie-help-text"><?php esc_html_e( 'If your cookie banner\'s revisit button uses the same corner, the two stack neatly instead of overlapping.', 'kukie-cookie-consent' ); ?></p>
					</fieldset>

					<div class="kukie-form-grid">
						<div class="kukie-form-group">
							<label for="kukie-a11y-size"><?php esc_html_e( 'Button size', 'kukie-cookie-consent' ); ?></label>
							<select id="kukie-a11y-size" class="kukie-select" aria-describedby="kukie-a11y-size-hint">
								<option value="44"><?php esc_html_e( 'Standard (44px)', 'kukie-cookie-consent' ); ?></option>
								<option value="52"><?php esc_html_e( 'Large (52px)', 'kukie-cookie-consent' ); ?></option>
								<option value="60"><?php esc_html_e( 'Extra large (60px)', 'kukie-cookie-consent' ); ?></option>
							</select>
							<p class="kukie-help-text" id="kukie-a11y-size-hint"><?php esc_html_e( '44px is the recognised accessibility minimum for a touch target, so there is no smaller option.', 'kukie-cookie-consent' ); ?></p>
						</div>
					</div>

					<fieldset class="kukie-fieldset kukie-form-group">
						<legend class="kukie-legend"><?php esc_html_e( 'Button colour', 'kukie-cookie-consent' ); ?></legend>
						<label class="kukie-checkbox-label">
							<input type="checkbox" id="kukie-a11y-color-inherit" checked>
							<?php esc_html_e( 'Inherit the banner theme\'s primary colour', 'kukie-cookie-consent' ); ?>
							<span class="kukie-swatch" id="kukie-a11y-theme-swatch" aria-hidden="true"></span>
						</label>
						<div class="kukie-color-input-group" id="kukie-a11y-color-group" hidden>
							<input type="color" id="kukie-a11y-color-picker" value="#2563eb" aria-label="<?php esc_attr_e( 'Pick a button colour', 'kukie-cookie-consent' ); ?>">
							<input type="text" id="kukie-a11y-color" class="kukie-input" placeholder="#2563eb" maxlength="7" pattern="#[0-9a-fA-F]{6}" aria-label="<?php esc_attr_e( 'Button colour (hex)', 'kukie-cookie-consent' ); ?>">
						</div>
					</fieldset>

					<div class="kukie-form-row">
						<div class="kukie-form-row-label">
							<span id="kukie-a11y-mobile-label"><?php esc_html_e( 'Hide on mobile', 'kukie-cookie-consent' ); ?></span>
							<span class="kukie-form-row-hint" id="kukie-a11y-mobile-hint"><?php esc_html_e( 'Hides the widget button on screens narrower than 768px. Adjustments a visitor already applied stay applied.', 'kukie-cookie-consent' ); ?></span>
						</div>
						<label class="kukie-toggle">
							<input type="checkbox" role="switch" id="kukie-a11y-hide-mobile" value="1" aria-labelledby="kukie-a11y-mobile-label" aria-describedby="kukie-a11y-mobile-hint">
							<span class="kukie-toggle-slider" aria-hidden="true"></span>
						</label>
					</div>
				</div>

				<!-- Modules -->
				<div class="kukie-card">
					<h2 class="kukie-card-title"><?php esc_html_e( 'Modules', 'kukie-cookie-consent' ); ?></h2>
					<p class="kukie-card-description" id="kukie-a11y-modules-hint"><?php esc_html_e( 'Untick a tool and visitors never see it. A hidden module is fully inactive, and new tools added to the widget later appear automatically unless you hide them.', 'kukie-cookie-consent' ); ?></p>
					<fieldset class="kukie-fieldset kukie-form-group" aria-describedby="kukie-a11y-modules-hint">
						<legend class="kukie-legend"><?php esc_html_e( 'Adjustments', 'kukie-cookie-consent' ); ?></legend>
						<div class="kukie-checkbox-grid" id="kukie-a11y-modules"></div>
					</fieldset>
					<fieldset class="kukie-fieldset kukie-form-group">
						<legend class="kukie-legend"><?php esc_html_e( 'Panel sections', 'kukie-cookie-consent' ); ?></legend>
						<div class="kukie-checkbox-grid" id="kukie-a11y-sections"></div>
					</fieldset>
				</div>

				<!-- Languages -->
				<div class="kukie-card">
					<h2 class="kukie-card-title"><?php esc_html_e( 'Languages', 'kukie-cookie-consent' ); ?></h2>
					<p class="kukie-card-description"><?php esc_html_e( 'By default the panel offers your banner\'s languages plus English and opens in the language the banner detected for the visitor.', 'kukie-cookie-consent' ); ?></p>

					<div class="kukie-form-row">
						<div class="kukie-form-row-label">
							<span id="kukie-a11y-langs-custom-label"><?php esc_html_e( 'Choose which languages to offer', 'kukie-cookie-consent' ); ?></span>
							<span class="kukie-form-row-hint" id="kukie-a11y-langs-custom-hint"><?php esc_html_e( 'Pick any of the 70+ built-in translations. With more than 8 languages the panel\'s flag picker becomes a dropdown.', 'kukie-cookie-consent' ); ?></span>
						</div>
						<label class="kukie-toggle">
							<input type="checkbox" role="switch" id="kukie-a11y-langs-custom" value="1" aria-labelledby="kukie-a11y-langs-custom-label" aria-describedby="kukie-a11y-langs-custom-hint">
							<span class="kukie-toggle-slider" aria-hidden="true"></span>
						</label>
					</div>

					<fieldset class="kukie-fieldset kukie-form-group" id="kukie-a11y-langs-wrap" hidden>
						<legend class="kukie-legend"><?php esc_html_e( 'Offered languages', 'kukie-cookie-consent' ); ?></legend>
						<div class="kukie-checkbox-grid" id="kukie-a11y-languages"></div>
					</fieldset>

					<div class="kukie-form-group">
						<label for="kukie-a11y-default-language"><?php esc_html_e( 'Default language', 'kukie-cookie-consent' ); ?></label>
						<select id="kukie-a11y-default-language" class="kukie-select" aria-describedby="kukie-a11y-default-language-hint"></select>
						<p class="kukie-help-text" id="kukie-a11y-default-language-hint"><?php esc_html_e( 'Used when the visitor\'s language cannot be detected. Auto-detection always wins when it resolves.', 'kukie-cookie-consent' ); ?></p>
					</div>
				</div>

				<!-- Accessibility statement -->
				<div class="kukie-card">
					<h2 class="kukie-card-title"><?php esc_html_e( 'Accessibility statement', 'kukie-cookie-consent' ); ?></h2>

					<div class="kukie-form-row">
						<div class="kukie-form-row-label">
							<span id="kukie-a11y-stmt-label"><?php esc_html_e( 'Link to an accessibility statement from the panel', 'kukie-cookie-consent' ); ?></span>
							<span class="kukie-form-row-hint" id="kukie-a11y-stmt-hint"><?php esc_html_e( 'Shown in the panel footer.', 'kukie-cookie-consent' ); ?></span>
						</div>
						<label class="kukie-toggle">
							<input type="checkbox" role="switch" id="kukie-a11y-stmt-enabled" value="1" aria-labelledby="kukie-a11y-stmt-label" aria-describedby="kukie-a11y-stmt-hint">
							<span class="kukie-toggle-slider" aria-hidden="true"></span>
						</label>
					</div>

					<div class="kukie-form-group" id="kukie-a11y-stmt-url-group">
						<label for="kukie-a11y-stmt-url"><?php esc_html_e( 'Statement URL', 'kukie-cookie-consent' ); ?></label>
						<input type="url" id="kukie-a11y-stmt-url" class="kukie-input" placeholder="https://" maxlength="500" aria-describedby="kukie-a11y-stmt-url-hint">
						<p class="kukie-help-text" id="kukie-a11y-stmt-url-hint">
							<?php
							printf(
								/* translators: %s: link to the site's Legal Documents page on Kukie.io */
								esc_html__( 'Leave empty to link the statement generated and published from the site\'s %s on Kukie.io.', 'kukie-cookie-consent' ),
								'<a href="' . esc_url( $kukie_app_url . '/sites/' . $kukie_site_id . '/legal-documents' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Legal Documents page', 'kukie-cookie-consent' ) . '</a>'
							);
							?>
						</p>
					</div>
				</div>

				<div class="kukie-cta-banner">
					<span class="dashicons dashicons-editor-textcolor" aria-hidden="true"></span>
					<p>
						<?php
						printf(
							/* translators: %s: link to the accessibility widget page on Kukie.io */
							esc_html__( 'Every text in the panel can be reworded per language in %s.', 'kukie-cookie-consent' ),
							'<a href="' . esc_url( $kukie_app_url . '/sites/' . $kukie_site_id . '/accessibility' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'your Kukie.io dashboard', 'kukie-cookie-consent' ) . '</a>'
						);
						?>
					</p>
				</div>
			</fieldset>

			<div class="kukie-form-actions">
				<button type="submit" class="kukie-btn-primary" id="kukie-a11y-save">
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
