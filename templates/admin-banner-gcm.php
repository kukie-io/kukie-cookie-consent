<?php
/**
 * Consent banner > Google Consent Mode v2 tab (a partial of admin-banner.php).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$kukie_plugin = Kukie_Plugin::instance();
?>
<div id="kukie-gcm-error" class="notice notice-error inline kukie-notice" role="alert" hidden><p></p></div>

<div id="kukie-gcm-loading" class="kukie-loading" role="status">
	<span class="kukie-spinner" aria-hidden="true"></span>
	<?php esc_html_e( 'Loading settings…', 'kukie-cookie-consent' ); ?>
</div>

<form id="kukie-gcm-form" hidden>
	<div class="kukie-card">
		<h2 class="kukie-card-title"><?php esc_html_e( 'Google Consent Mode v2', 'kukie-cookie-consent' ); ?></h2>
		<p class="kukie-card-description" id="kukie-gcm-enabled-hint">
			<?php esc_html_e( "Google Consent Mode v2 passes each visitor's choice to Google tags such as Google Analytics, Google Ads and Google Tag Manager, so they adjust what they collect. Google requires it for audience building and remarketing in the EEA.", 'kukie-cookie-consent' ); ?>
		</p>

		<div class="kukie-form-row">
			<div class="kukie-form-row-label">
				<span id="kukie-gcm-enabled-label"><?php esc_html_e( 'Google Consent Mode v2', 'kukie-cookie-consent' ); ?></span>
			</div>
			<label class="kukie-toggle">
				<input type="checkbox" role="switch" name="gcm_v2_enabled" id="kukie-gcm-enabled" value="1" aria-labelledby="kukie-gcm-enabled-label" aria-describedby="kukie-gcm-enabled-hint">
				<span class="kukie-toggle-slider" aria-hidden="true"></span>
			</label>
		</div>
	</div>

	<!-- Google's banner text (since 1.10.0, Google requirement v.3). Shown
	     while the switch is on and the plan has Consent Mode; admin.js shows
	     one of three states from the google_template block of GET /settings:
	     not applied (the recommendation), applied but no longer complete (a
	     warning with Re-apply), complete (a success notice). The apply runs
	     the Kukie.io banner editor's own implementation. -->
	<div class="kukie-card" id="kukie-gtpl-card" hidden>
		<h2 class="kukie-card-title"><?php esc_html_e( "Google's banner text", 'kukie-cookie-consent' ); ?></h2>
		<p class="kukie-card-description">
			<?php esc_html_e( "Google recommends banner text for sites that use Consent Mode. Google's EU user consent policy asks the banner to:", 'kukie-cookie-consent' ); ?>
		</p>
		<ul class="kukie-notice-list kukie-gtpl-asks">
			<li><?php esc_html_e( 'Say that data is collected for ads personalisation', 'kukie-cookie-consent' ); ?></li>
			<li><?php esc_html_e( "Link to Google's privacy page (business.safety.google/privacy/)", 'kukie-cookie-consent' ); ?></li>
			<li><?php esc_html_e( 'Offer a clear Accept all button', 'kukie-cookie-consent' ); ?></li>
		</ul>

		<div id="kukie-gtpl-recommend" hidden>
			<p class="kukie-card-description">
				<?php esc_html_e( 'Applying the text puts it in every language of your banner and replaces the description you have now. It also turns on Accept all, Reject all and Manage preferences.', 'kukie-cookie-consent' ); ?>
			</p>
			<p class="kukie-card-description" id="kukie-gtpl-languages"></p>
		</div>

		<div id="kukie-gtpl-broken" class="notice notice-warning inline kukie-notice" hidden>
			<p><strong><?php esc_html_e( "Your banner no longer has all of Google's banner text", 'kukie-cookie-consent' ); ?></strong></p>
			<ul class="kukie-notice-list" id="kukie-gtpl-gaps"></ul>
			<p><?php esc_html_e( 'Re-applying replaces the description in every language and turns Accept all back on.', 'kukie-cookie-consent' ); ?></p>
		</div>

		<div id="kukie-gtpl-intact" class="notice notice-success inline kukie-notice" hidden>
			<p><?php esc_html_e( "Google's banner text is in every language of your banner, with Accept all.", 'kukie-cookie-consent' ); ?></p>
		</div>

		<p class="kukie-card-actions-row" id="kukie-gtpl-actions" hidden>
			<button type="button" class="kukie-btn-primary" id="kukie-gtpl-apply">
				<span class="kukie-btn-text"><?php esc_html_e( "Apply Google's banner text", 'kukie-cookie-consent' ); ?></span>
				<span class="kukie-btn-loading" hidden>
					<span class="kukie-spinner" aria-hidden="true"></span>
					<?php esc_html_e( 'Applying…', 'kukie-cookie-consent' ); ?>
				</span>
			</button>
		</p>

		<p class="kukie-card-description kukie-card-description--last">
			<a href="https://kukie.io/docs/integrations/google-banner-requirements" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( "Google's banner requirements", 'kukie-cookie-consent' ); ?>
				<?php echo Kukie_Admin::new_tab_marker(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses-sanitised in the helper ?>
			</a>
		</p>
	</div>

	<!-- Deliberately no per-category table here: what Google tags assume
	     before a choice depends on the visitor's region rule, so any static
	     table would be wrong for part of every audience. Since Kukie.io
	     v1.112.0 the global default is denied, the regions whose rule allows
	     cookies until a choice get a granted default for that region, and a
	     visible banner sends no granted update before the visitor acts. -->
	<div class="kukie-card">
		<h2 class="kukie-card-title"><?php esc_html_e( 'Default consent state', 'kukie-cookie-consent' ); ?></h2>
		<p class="kukie-card-description">
			<?php esc_html_e( 'Before a visitor chooses, Google tags start with consent denied. In regions where your rules allow cookies until the visitor chooses (opt-out or notice only) or show no banner, Kukie.io sets the default for that region to granted, unless the visitor chose earlier or their browser sends Global Privacy Control or Do Not Track. While the banner is on screen, Kukie.io sends no consent update until the visitor makes a choice.', 'kukie-cookie-consent' ); ?>
		</p>
		<p class="kukie-card-description">
			<?php esc_html_e( 'Region rules are set on Kukie.io. To see the consent signals a page sends, open it with debug mode on.', 'kukie-cookie-consent' ); ?>
		</p>
		<ul class="kukie-link-list">
			<li>
				<a href="<?php echo esc_url( $kukie_plugin->get_option( 'dashboard_url', 'https://app.kukie.io' ) ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Manage region rules on Kukie.io', 'kukie-cookie-consent' ); ?>
					<?php echo Kukie_Admin::new_tab_marker(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses-sanitised in the helper ?>
				</a>
			</li>
			<li>
				<a href="https://kukie.io/docs/integrations/google-consent-mode-debug-mode" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Debug mode for Google Consent Mode', 'kukie-cookie-consent' ); ?>
					<?php echo Kukie_Admin::new_tab_marker(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses-sanitised in the helper ?>
				</a>
			</li>
			<li>
				<a href="https://developers.google.com/tag-platform/security/guides/consent" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( "Google's guide to setting up Consent Mode", 'kukie-cookie-consent' ); ?>
					<?php echo Kukie_Admin::new_tab_marker(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses-sanitised in the helper ?>
				</a>
			</li>
		</ul>
	</div>

	<div class="kukie-form-actions">
		<button type="submit" class="kukie-btn-primary" id="kukie-gcm-save">
			<span class="kukie-btn-text"><?php esc_html_e( 'Save changes', 'kukie-cookie-consent' ); ?></span>
			<span class="kukie-btn-loading" hidden>
				<span class="kukie-spinner" aria-hidden="true"></span>
				<?php esc_html_e( 'Saving…', 'kukie-cookie-consent' ); ?>
			</span>
		</button>
	</div>
</form>
