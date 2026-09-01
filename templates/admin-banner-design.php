<?php
/**
 * Consent banner > Design tab (a partial of admin-banner.php).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$kukie_plugin  = Kukie_Plugin::instance();
$kukie_site_id = absint( $kukie_plugin->get_option( 'site_id', 0 ) );
$kukie_app_url = 'https://app.kukie.io';
?>
<div id="kukie-design-loading" class="kukie-loading" role="status">
	<span class="kukie-spinner" aria-hidden="true"></span>
	<?php esc_html_e( 'Loading design settings...', 'kukie-cookie-consent' ); ?>
</div>

<div id="kukie-design-content" hidden>
	<div class="kukie-design-layout">
		<!-- Left: Controls -->
		<div class="kukie-design-controls">
			<!-- Layout -->
			<div class="kukie-card">
				<fieldset class="kukie-fieldset">
					<legend class="kukie-card-title"><?php esc_html_e( 'Layout', 'kukie-cookie-consent' ); ?></legend>
					<div class="kukie-layout-grid">
						<label class="kukie-layout-option">
							<input type="radio" name="banner_layout" value="popup" checked>
							<span class="kukie-layout-option-body">
								<strong><?php esc_html_e( 'Popup (Center)', 'kukie-cookie-consent' ); ?></strong>
								<span><?php esc_html_e( 'Classic centered modal', 'kukie-cookie-consent' ); ?></span>
							</span>
						</label>
						<label class="kukie-layout-option">
							<input type="radio" name="banner_layout" value="bar-bottom">
							<span class="kukie-layout-option-body">
								<strong><?php esc_html_e( 'Bottom Bar', 'kukie-cookie-consent' ); ?></strong>
								<span><?php esc_html_e( 'Full-width bar at bottom', 'kukie-cookie-consent' ); ?></span>
							</span>
						</label>
						<label class="kukie-layout-option">
							<input type="radio" name="banner_layout" value="bar-top">
							<span class="kukie-layout-option-body">
								<strong><?php esc_html_e( 'Top Bar', 'kukie-cookie-consent' ); ?></strong>
								<span><?php esc_html_e( 'Full-width bar at top', 'kukie-cookie-consent' ); ?></span>
							</span>
						</label>
						<label class="kukie-layout-option">
							<input type="radio" name="banner_layout" value="floating">
							<span class="kukie-layout-option-body">
								<strong><?php esc_html_e( 'Floating', 'kukie-cookie-consent' ); ?></strong>
								<span><?php esc_html_e( 'Small floating card', 'kukie-cookie-consent' ); ?></span>
							</span>
						</label>
					</div>
				</fieldset>
			</div>

			<!-- Position -->
			<div class="kukie-card" id="kukie-position-card">
				<fieldset class="kukie-fieldset">
					<legend class="kukie-card-title"><?php esc_html_e( 'Position', 'kukie-cookie-consent' ); ?></legend>
					<div class="kukie-position-group">
						<label class="kukie-position-pill">
							<input type="radio" name="banner_position" value="center" checked>
							<span><?php esc_html_e( 'Center', 'kukie-cookie-consent' ); ?></span>
						</label>
						<label class="kukie-position-pill">
							<input type="radio" name="banner_position" value="bottom-left">
							<span><?php esc_html_e( 'Bottom Left', 'kukie-cookie-consent' ); ?></span>
						</label>
						<label class="kukie-position-pill">
							<input type="radio" name="banner_position" value="bottom-center">
							<span><?php esc_html_e( 'Bottom Center', 'kukie-cookie-consent' ); ?></span>
						</label>
						<label class="kukie-position-pill">
							<input type="radio" name="banner_position" value="bottom-right">
							<span><?php esc_html_e( 'Bottom Right', 'kukie-cookie-consent' ); ?></span>
						</label>
					</div>
				</fieldset>
			</div>

			<!-- Revisit Button -->
			<div class="kukie-card" id="kukie-revisit-card">
				<div class="kukie-card-header">
					<h2 class="kukie-card-title" id="kukie-revisit-enabled-label"><?php esc_html_e( 'Revisit Button', 'kukie-cookie-consent' ); ?></h2>
					<label class="kukie-toggle">
						<input type="checkbox" role="switch" id="kukie-revisit-enabled" checked aria-labelledby="kukie-revisit-enabled-label">
						<span class="kukie-toggle-slider" aria-hidden="true"></span>
					</label>
				</div>
				<div id="kukie-revisit-fields">
					<div class="kukie-form-grid">
						<div class="kukie-form-group">
							<label for="kukie-revisit-position"><?php esc_html_e( 'Position', 'kukie-cookie-consent' ); ?></label>
							<select id="kukie-revisit-position" class="kukie-select">
								<option value="bottom_left"><?php esc_html_e( 'Bottom Left', 'kukie-cookie-consent' ); ?></option>
								<option value="bottom_right"><?php esc_html_e( 'Bottom Right', 'kukie-cookie-consent' ); ?></option>
								<option value="top_left"><?php esc_html_e( 'Top Left', 'kukie-cookie-consent' ); ?></option>
								<option value="top_right"><?php esc_html_e( 'Top Right', 'kukie-cookie-consent' ); ?></option>
							</select>
						</div>
						<div class="kukie-form-group">
							<label for="kukie-revisit-style"><?php esc_html_e( 'Style', 'kukie-cookie-consent' ); ?></label>
							<select id="kukie-revisit-style" class="kukie-select">
								<option value="icon"><?php esc_html_e( 'Icon Only', 'kukie-cookie-consent' ); ?></option>
								<option value="pill"><?php esc_html_e( 'Pill (Icon + Text)', 'kukie-cookie-consent' ); ?></option>
								<option value="tab"><?php esc_html_e( 'Tab (Text)', 'kukie-cookie-consent' ); ?></option>
							</select>
						</div>
						<div class="kukie-form-group">
							<label for="kukie-revisit-icon"><?php esc_html_e( 'Icon', 'kukie-cookie-consent' ); ?></label>
							<select id="kukie-revisit-icon" class="kukie-select">
								<option value="cookie"><?php esc_html_e( 'Cookie', 'kukie-cookie-consent' ); ?></option>
								<option value="shield"><?php esc_html_e( 'Shield', 'kukie-cookie-consent' ); ?></option>
								<option value="settings"><?php esc_html_e( 'Settings', 'kukie-cookie-consent' ); ?></option>
								<option value="fingerprint"><?php esc_html_e( 'Fingerprint', 'kukie-cookie-consent' ); ?></option>
								<option value="lock"><?php esc_html_e( 'Lock', 'kukie-cookie-consent' ); ?></option>
								<option value="sliders"><?php esc_html_e( 'Sliders', 'kukie-cookie-consent' ); ?></option>
							</select>
						</div>
						<div class="kukie-form-group">
							<label for="kukie-revisit-text"><?php esc_html_e( 'Button Text', 'kukie-cookie-consent' ); ?></label>
							<input type="text" id="kukie-revisit-text" class="kukie-input" value="Cookie Settings" maxlength="100">
						</div>
					</div>
					<div class="kukie-form-group">
						<label for="kukie-revisit-color"><?php esc_html_e( 'Background Color (empty = primary)', 'kukie-cookie-consent' ); ?></label>
						<div class="kukie-color-field">
							<input type="color" id="kukie-revisit-color-picker" value="#2563eb" aria-label="<?php esc_attr_e( 'Pick a background colour', 'kukie-cookie-consent' ); ?>">
							<input type="text" id="kukie-revisit-color" class="kukie-input" placeholder="#2563eb" maxlength="20">
						</div>
					</div>
					<fieldset class="kukie-fieldset kukie-form-group">
						<legend class="kukie-legend"><?php esc_html_e( 'Icon Color', 'kukie-cookie-consent' ); ?></legend>
						<div class="kukie-color-field">
							<label class="kukie-checkbox-label">
								<input type="checkbox" id="kukie-revisit-icon-auto" checked>
								<?php esc_html_e( 'Auto (contrast)', 'kukie-cookie-consent' ); ?>
							</label>
							<div class="kukie-color-input-group" id="kukie-icon-color-group" hidden>
								<input type="color" id="kukie-revisit-icon-color-picker" value="#ffffff" aria-label="<?php esc_attr_e( 'Pick an icon colour', 'kukie-cookie-consent' ); ?>">
								<input type="text" id="kukie-revisit-icon-color" class="kukie-input" placeholder="#ffffff" maxlength="20" aria-label="<?php esc_attr_e( 'Icon colour (hex)', 'kukie-cookie-consent' ); ?>">
							</div>
						</div>
					</fieldset>
					<div class="kukie-form-grid">
						<div class="kukie-form-group">
							<label for="kukie-revisit-offset-x"><?php esc_html_e( 'Offset X (px)', 'kukie-cookie-consent' ); ?></label>
							<input type="number" id="kukie-revisit-offset-x" class="kukie-input" value="20" min="0" max="200">
						</div>
						<div class="kukie-form-group">
							<label for="kukie-revisit-offset-y"><?php esc_html_e( 'Offset Y (px)', 'kukie-cookie-consent' ); ?></label>
							<input type="number" id="kukie-revisit-offset-y" class="kukie-input" value="20" min="0" max="200">
						</div>
					</div>
				</div>
			</div>

			<div class="kukie-form-actions">
				<button type="button" id="kukie-design-save" class="kukie-btn-primary">
					<span class="kukie-btn-text"><?php esc_html_e( 'Save Changes', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-btn-loading" hidden>
						<span class="kukie-spinner" aria-hidden="true"></span>
						<?php esc_html_e( 'Saving...', 'kukie-cookie-consent' ); ?>
					</span>
				</button>
			</div>

			<!-- CTA -->
			<div class="kukie-cta-banner">
				<span class="dashicons dashicons-admin-customizer" aria-hidden="true"></span>
				<p>
					<?php
					printf(
						/* translators: %s: link to Kukie.io banner settings */
						esc_html__( 'Want more customisation options? Edit colours, texts and advanced settings in %s.', 'kukie-cookie-consent' ),
						'<a href="' . esc_url( $kukie_app_url . '/sites/' . $kukie_site_id . '/banner' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'your Kukie.io dashboard', 'kukie-cookie-consent' ) . Kukie_Admin::new_tab_marker() . '</a>'
					);
					?>
				</p>
			</div>
		</div>

		<!-- Right: Preview -->
		<div class="kukie-design-preview">
			<div class="kukie-card kukie-preview-card">
				<div class="kukie-preview-header">
					<h2 class="kukie-card-title"><?php esc_html_e( 'Live Preview', 'kukie-cookie-consent' ); ?></h2>
				</div>

				<div class="kukie-preview-browser-wrap" id="kukie-preview-wrap" aria-hidden="true">
					<div class="kukie-preview-browser">
						<div class="kukie-preview-chrome">
							<div class="kukie-preview-dots">
								<span class="kukie-preview-dot kukie-preview-dot--red"></span>
								<span class="kukie-preview-dot kukie-preview-dot--amber"></span>
								<span class="kukie-preview-dot kukie-preview-dot--green"></span>
							</div>
							<div class="kukie-preview-url-bar">
								<span class="kukie-preview-url"><?php echo esc_html( $kukie_plugin->get_option( 'domain', 'example.com' ) ); ?></span>
							</div>
						</div>
						<div class="kukie-preview-page" id="kukie-preview-page" data-layout="popup" data-position="center">
							<div class="kukie-preview-page-content">
								<div class="kukie-mock-block kukie-mock-nav"></div>
								<div class="kukie-mock-block kukie-mock-hero"></div>
								<div class="kukie-mock-row">
									<div class="kukie-mock-block kukie-mock-card"></div>
									<div class="kukie-mock-block kukie-mock-card"></div>
									<div class="kukie-mock-block kukie-mock-card"></div>
								</div>
								<div class="kukie-mock-block kukie-mock-text"></div>
								<div class="kukie-mock-block kukie-mock-text kukie-mock-text--short"></div>
								<div class="kukie-mock-row">
									<div class="kukie-mock-block kukie-mock-card"></div>
									<div class="kukie-mock-block kukie-mock-card"></div>
								</div>
								<div class="kukie-mock-block kukie-mock-text"></div>
							</div>

							<div class="kukie-preview-overlay" id="kukie-preview-overlay"></div>

							<div class="kukie-preview-banner" id="kukie-preview-banner">
								<div class="kukie-pbanner-head">
									<svg width="10" height="10" viewBox="0 0 16 16" fill="none" aria-hidden="true">
										<path d="M8 1L2 4v4c0 3.3 2.6 6.4 6 7 3.4-.6 6-3.7 6-7V4L8 1z" stroke="#2563eb" stroke-width="1.5" fill="#2563eb" fill-opacity="0.15"/>
										<path d="M6 8l1.5 1.5L10.5 6" stroke="#2563eb" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
									</svg>
									<strong><?php esc_html_e( 'We value your privacy', 'kukie-cookie-consent' ); ?></strong>
								</div>
								<p class="kukie-pbanner-text"><?php esc_html_e( 'We use cookies to enhance your browsing experience, serve personalised content, and analyse our traffic.', 'kukie-cookie-consent' ); ?></p>
								<div class="kukie-pbanner-btns">
									<span class="kukie-pbanner-btn"><?php esc_html_e( 'Customise', 'kukie-cookie-consent' ); ?></span>
									<span class="kukie-pbanner-btn"><?php esc_html_e( 'Reject All', 'kukie-cookie-consent' ); ?></span>
									<span class="kukie-pbanner-btn kukie-pbanner-btn--primary"><?php esc_html_e( 'Accept All', 'kukie-cookie-consent' ); ?></span>
								</div>
								<span class="kukie-pbanner-powered">Powered by Kukie.io</span>
							</div>
						</div>
					</div>
				</div>

				<p class="kukie-preview-note"><?php esc_html_e( 'Updates instantly as you change settings', 'kukie-cookie-consent' ); ?></p>
			</div>
		</div>
	</div>
</div>
