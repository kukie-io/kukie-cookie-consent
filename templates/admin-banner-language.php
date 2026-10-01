<?php
/**
 * Consent banner > Language tab (a partial of admin-banner.php). Moved here
 * from the Settings page in 1.8.0: every field concerns the consent banner
 * only. force_language is a local (WordPress-side) override; the other
 * fields sync to Kukie.io.
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
<div id="kukie-language-error" class="notice notice-error inline kukie-notice" role="alert" hidden><p></p></div>

<div id="kukie-language-loading" class="kukie-loading" role="status">
	<span class="kukie-spinner" aria-hidden="true"></span>
	<?php esc_html_e( 'Loading settings…', 'kukie-cookie-consent' ); ?>
</div>

<form id="kukie-language-form" hidden>
	<div class="kukie-card">
		<h2 class="kukie-card-title"><?php esc_html_e( 'Language', 'kukie-cookie-consent' ); ?></h2>

		<div class="kukie-form-group">
			<label for="kukie-force-language"><?php esc_html_e( 'Banner language', 'kukie-cookie-consent' ); ?></label>
			<select name="force_language" id="kukie-force-language" class="kukie-select" aria-describedby="kukie-force-language-hint">
				<option value="auto"><?php esc_html_e( 'Auto-detect (WPML / Polylang / WordPress)', 'kukie-cookie-consent' ); ?></option>
				<option value="en">English</option>
				<option value="de">Deutsch</option>
				<option value="fr">Français</option>
				<option value="es">Español</option>
				<option value="es-mx">Español (México)</option>
				<option value="es-ar">Español (Argentina)</option>
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
			<p class="kukie-help-text" id="kukie-force-language-hint"><?php esc_html_e( 'Auto-detect follows WPML, Polylang or the WordPress site language, and suits almost every site. Choose a language only to show the banner in it on every page.', 'kukie-cookie-consent' ); ?></p>
		</div>

		<div class="kukie-form-row">
			<div class="kukie-form-row-label">
				<span id="kukie-auto-translate-label"><?php esc_html_e( 'Auto-translate', 'kukie-cookie-consent' ); ?></span>
				<span class="kukie-form-row-hint" id="kukie-auto-translate-hint"><?php esc_html_e( "When on, the banner appears in the visitor's language when it is one of the languages below.", 'kukie-cookie-consent' ); ?></span>
			</div>
			<label class="kukie-toggle">
				<input type="checkbox" role="switch" name="auto_translate" id="kukie-auto-translate" value="1" aria-labelledby="kukie-auto-translate-label" aria-describedby="kukie-auto-translate-hint">
				<span class="kukie-toggle-slider" aria-hidden="true"></span>
			</label>
		</div>

		<div id="kukie-language-options" hidden>
			<div class="kukie-form-group">
				<label for="kukie-default-language"><?php esc_html_e( 'Default language', 'kukie-cookie-consent' ); ?></label>
				<select name="default_language" id="kukie-default-language" class="kukie-select" aria-describedby="kukie-default-language-hint">
					<option value="en">English</option>
				</select>
				<p class="kukie-help-text" id="kukie-default-language-hint"><?php esc_html_e( "Used when the visitor's language is not one of the languages below.", 'kukie-cookie-consent' ); ?></p>
			</div>

			<fieldset class="kukie-fieldset kukie-form-group">
				<legend class="kukie-legend"><?php esc_html_e( 'Banner languages', 'kukie-cookie-consent' ); ?></legend>
				<p class="kukie-help-text kukie-help-text--above"><?php esc_html_e( 'The languages the banner can appear in.', 'kukie-cookie-consent' ); ?></p>
				<div class="kukie-checkbox-grid" id="kukie-languages-grid">
					<div class="kukie-loading" role="status">
						<span class="kukie-spinner" aria-hidden="true"></span>
					</div>
				</div>
			</fieldset>
		</div>

		<div class="kukie-cta-banner">
			<span class="dashicons dashicons-edit" aria-hidden="true"></span>
			<p>
				<?php
				printf(
					/* translators: %s: link to Kukie.io banner settings */
					esc_html__( 'Banner texts and their translations are in %s.', 'kukie-cookie-consent' ),
					'<a href="' . esc_url( $kukie_app_url . '/sites/' . $kukie_site_id . '/banner?tab=texts' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'your banner settings on Kukie.io', 'kukie-cookie-consent' ) . Kukie_Admin::new_tab_marker() . '</a>'
				);
				?>
			</p>
		</div>
	</div>

	<div class="kukie-form-actions">
		<button type="submit" class="kukie-btn-primary" id="kukie-language-save">
			<span class="kukie-btn-text"><?php esc_html_e( 'Save changes', 'kukie-cookie-consent' ); ?></span>
			<span class="kukie-btn-loading" hidden>
				<span class="kukie-spinner" aria-hidden="true"></span>
				<?php esc_html_e( 'Saving…', 'kukie-cookie-consent' ); ?>
			</span>
		</button>
	</div>
</form>
