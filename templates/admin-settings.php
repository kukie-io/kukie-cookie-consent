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

	<div id="kukie-settings-error" class="kukie-notice kukie-notice-error" style="display:none;"></div>
	<div id="kukie-settings-success" class="kukie-notice kukie-notice-success" style="display:none;"></div>

	<div id="kukie-settings-loading" class="kukie-loading">
		<span class="kukie-spinner"></span>
		<?php esc_html_e( 'Loading settings...', 'kukie-cookie-consent' ); ?>
	</div>

	<div id="kukie-settings-content" style="display:none;">
		<!-- Connection Info -->
		<div class="kukie-card">
			<h2 class="kukie-card-title"><?php esc_html_e( 'Connection', 'kukie-cookie-consent' ); ?></h2>
			<div class="kukie-info-grid">
				<div class="kukie-info-row">
					<span class="kukie-info-label"><?php esc_html_e( 'Status', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-info-value">
						<span class="kukie-status-dot kukie-status-dot--connected"></span>
						<?php esc_html_e( 'Connected', 'kukie-cookie-consent' ); ?>
					</span>
				</div>
				<div class="kukie-info-row">
					<span class="kukie-info-label"><?php esc_html_e( 'Organisation', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-info-value"><?php echo esc_html( $kukie_plugin->get_option( 'organisation', '' ) ); ?></span>
				</div>
				<div class="kukie-info-row">
					<span class="kukie-info-label"><?php esc_html_e( 'Plan', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-info-value"><?php echo esc_html( $kukie_plugin->get_option( 'plan_name', 'Free' ) ); ?></span>
				</div>
				<div class="kukie-info-row">
					<span class="kukie-info-label"><?php esc_html_e( 'Domain', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-info-value"><?php echo esc_html( $kukie_plugin->get_option( 'domain', '' ) ); ?></span>
				</div>
				<div class="kukie-info-row">
					<span class="kukie-info-label"><?php esc_html_e( 'Site Key', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-info-value"><code><?php echo esc_html( $kukie_plugin->get_option( 'site_key', '' ) ); ?></code></span>
				</div>
			</div>

			<div class="kukie-disconnect-section">
				<button type="button" id="kukie-disconnect-btn" class="kukie-btn-danger">
					<?php esc_html_e( 'Disconnect from Kukie.io', 'kukie-cookie-consent' ); ?>
				</button>
				<p class="kukie-help-text"><?php esc_html_e( 'This will remove the cookie consent banner from your site.', 'kukie-cookie-consent' ); ?></p>
			</div>
		</div>

		<!-- Banner Script -->
		<form id="kukie-settings-form">
			<div class="kukie-card">
				<div class="kukie-card-header">
					<h2 class="kukie-card-title"><?php esc_html_e( 'Banner Script', 'kukie-cookie-consent' ); ?></h2>
					<label class="kukie-toggle">
						<input type="checkbox" name="banner_enabled" id="kukie-banner-enabled" value="1">
						<span class="kukie-toggle-slider"></span>
					</label>
				</div>

				<div class="kukie-form-group" style="margin-bottom:12px;">
					<label><?php esc_html_e( 'Script Position', 'kukie-cookie-consent' ); ?></label>
					<div class="kukie-radio-group">
						<label class="kukie-radio">
							<input type="radio" name="script_position" value="head" checked>
							<span class="kukie-radio-label">
								<strong>&lt;head&gt;</strong>
								<span class="kukie-radio-hint"><?php esc_html_e( 'Recommended', 'kukie-cookie-consent' ); ?></span>
							</span>
						</label>
						<label class="kukie-radio">
							<input type="radio" name="script_position" value="body">
							<span class="kukie-radio-label">
								<strong>&lt;body&gt;</strong>
								<span class="kukie-radio-hint"><?php esc_html_e( 'After opening tag', 'kukie-cookie-consent' ); ?></span>
							</span>
						</label>
						<label class="kukie-radio">
							<input type="radio" name="script_position" value="manual">
							<span class="kukie-radio-label">
								<strong><?php esc_html_e( 'Manual', 'kukie-cookie-consent' ); ?></strong>
								<span class="kukie-radio-hint"><?php esc_html_e( 'Embed code', 'kukie-cookie-consent' ); ?></span>
							</span>
						</label>
					</div>
				</div>

				<div id="kukie-manual-embed" class="kukie-embed-code" style="display:none;">
					<label><?php esc_html_e( 'Embed Code', 'kukie-cookie-consent' ); ?></label>
					<code id="kukie-embed-code-display"></code>
					<p class="kukie-help-text"><?php esc_html_e( 'Add this code to your theme header template.', 'kukie-cookie-consent' ); ?></p>
				</div>

				<div class="kukie-form-row" style="border-bottom:none;">
					<div class="kukie-form-row-label">
						<span><?php esc_html_e( 'Verification', 'kukie-cookie-consent' ); ?></span>
						<span class="kukie-form-row-hint" id="kukie-verified-status"><?php esc_html_e( 'Check if the banner script is detected on your site.', 'kukie-cookie-consent' ); ?></span>
					</div>
					<button type="button" id="kukie-verify-btn" class="kukie-btn-secondary kukie-btn-sm">
						<span class="kukie-btn-text"><?php esc_html_e( 'Verify', 'kukie-cookie-consent' ); ?></span>
						<span class="kukie-btn-loading" style="display:none;">
							<span class="kukie-spinner"></span>
						</span>
					</button>
				</div>
			</div>

			<!-- Language Settings -->
			<div class="kukie-card">
				<h2 class="kukie-card-title"><?php esc_html_e( 'Language', 'kukie-cookie-consent' ); ?></h2>

				<div class="kukie-form-group">
					<label for="kukie-force-language"><?php esc_html_e( 'Banner language', 'kukie-cookie-consent' ); ?></label>
					<select name="force_language" id="kukie-force-language" class="kukie-select">
						<option value="auto"><?php esc_html_e( 'Auto-detect (WPML / Polylang / WordPress)', 'kukie-cookie-consent' ); ?></option>
						<option value="en">English</option>
						<option value="de">Deutsch</option>
						<option value="fr">Français</option>
						<option value="es">Español</option>
						<option value="it">Italiano</option>
						<option value="pt">Português</option>
						<option value="pt-br">Português (Brasil)</option>
						<option value="nl">Nederlands</option>
						<option value="pl">Polski</option>
						<option value="ru">Русский</option>
						<option value="tr">Türkçe</option>
						<option value="ja">日本語</option>
						<option value="zh-cn">中文 (简体)</option>
						<option value="zh-tw">中文 (繁體)</option>
						<option value="ar">العربية</option>
						<option value="bg">Български</option>
						<option value="cs">Čeština</option>
						<option value="da">Dansk</option>
						<option value="el">Ελληνικά</option>
						<option value="fi">Suomi</option>
						<option value="he">עברית</option>
						<option value="hu">Magyar</option>
						<option value="id">Bahasa Indonesia</option>
						<option value="ko">한국어</option>
						<option value="no">Norsk</option>
						<option value="ro">Română</option>
						<option value="sk">Slovenčina</option>
						<option value="sv">Svenska</option>
						<option value="th">ภาษาไทย</option>
						<option value="uk">Українська</option>
						<option value="vi">Tiếng Việt</option>
					</select>
					<p class="kukie-help-text"><?php esc_html_e( 'Choose "Auto-detect" (recommended) to follow WPML, Polylang, or the WordPress site language. Select a specific language to force the banner into that locale regardless of page context.', 'kukie-cookie-consent' ); ?></p>
				</div>

				<div class="kukie-form-row">
					<div class="kukie-form-row-label">
						<span><?php esc_html_e( 'Auto-Translate', 'kukie-cookie-consent' ); ?></span>
						<span class="kukie-form-row-hint"><?php esc_html_e( 'Automatically detect visitor language and show the banner in their language.', 'kukie-cookie-consent' ); ?></span>
					</div>
					<label class="kukie-toggle">
						<input type="checkbox" name="auto_translate" id="kukie-auto-translate" value="1">
						<span class="kukie-toggle-slider"></span>
					</label>
				</div>

				<div id="kukie-language-options" style="display:none;">
					<div class="kukie-form-group">
						<label for="kukie-default-language"><?php esc_html_e( 'Default Language', 'kukie-cookie-consent' ); ?></label>
						<select name="default_language" id="kukie-default-language" class="kukie-select">
							<option value="en">English</option>
						</select>
						<p class="kukie-help-text"><?php esc_html_e( 'The fallback language when auto-translate cannot determine the visitor language.', 'kukie-cookie-consent' ); ?></p>
					</div>

					<div class="kukie-form-group">
						<label><?php esc_html_e( 'Enabled Languages', 'kukie-cookie-consent' ); ?></label>
						<p class="kukie-help-text"><?php esc_html_e( 'Select which languages are available for your cookie banner.', 'kukie-cookie-consent' ); ?></p>
						<div class="kukie-checkbox-grid" id="kukie-languages-grid">
							<div class="kukie-loading">
								<span class="kukie-spinner"></span>
							</div>
						</div>
						<div class="kukie-cta-banner">
							<span class="dashicons dashicons-edit"></span>
							<p>
								<?php
								printf(
									/* translators: %s: link to Kukie.io banner settings */
									esc_html__( 'Want to customise banner texts and translations? Edit them in %s.', 'kukie-cookie-consent' ),
									'<a href="' . esc_url( $kukie_app_url . '/sites/' . $kukie_site_id . '/banner' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'your banner settings on Kukie.io', 'kukie-cookie-consent' ) . '</a>'
								);
								?>
							</p>
						</div>
					</div>
				</div>
			</div>

			<!-- Save -->
			<div class="kukie-form-actions">
				<button type="submit" class="kukie-btn-primary" id="kukie-settings-save">
					<span class="kukie-btn-text"><?php esc_html_e( 'Save Settings', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-btn-loading" style="display:none;">
						<span class="kukie-spinner"></span>
						<?php esc_html_e( 'Saving...', 'kukie-cookie-consent' ); ?>
					</span>
				</button>
			</div>
		</form>
	</div>
</div>
