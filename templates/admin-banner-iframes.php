<?php
/**
 * Consent banner > iFrame blocking tab (a partial of admin-banner.php).
 *
 * @since 1.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="kukie-iframes-error" class="notice notice-error inline kukie-notice" role="alert" hidden><p></p></div>

<div id="kukie-iframes-loading" class="kukie-loading" role="status">
	<span class="kukie-spinner" aria-hidden="true"></span>
	<?php esc_html_e( 'Loading settings…', 'kukie-cookie-consent' ); ?>
</div>

<form id="kukie-iframes-form" hidden>
	<div class="kukie-card">
		<h2 class="kukie-card-title"><?php esc_html_e( 'iFrame blocking', 'kukie-cookie-consent' ); ?></h2>
		<p class="kukie-card-description"><?php esc_html_e( 'Embedded videos, maps and widgets from other companies can set cookies as soon as they load. To hold them back until the visitor agrees, turn on iFrame blocking.', 'kukie-cookie-consent' ); ?></p>

		<div class="kukie-form-row">
			<div class="kukie-form-row-label">
				<span id="kukie-iframe-enabled-label"><?php esc_html_e( 'Block third-party iFrames until consent', 'kukie-cookie-consent' ); ?></span>
				<span class="kukie-form-row-hint" id="kukie-iframe-enabled-hint"><?php esc_html_e( 'When on, the services below stay hidden until the visitor accepts their cookie category. Visitors see a placeholder with a button to allow them.', 'kukie-cookie-consent' ); ?></span>
			</div>
			<label class="kukie-toggle">
				<input type="checkbox" role="switch" id="kukie-iframe-enabled" value="1" aria-labelledby="kukie-iframe-enabled-label" aria-describedby="kukie-iframe-enabled-hint">
				<span class="kukie-toggle-slider" aria-hidden="true"></span>
			</label>
		</div>

		<fieldset class="kukie-fieldset kukie-form-group" id="kukie-iframe-services-wrap" hidden>
			<legend class="kukie-legend"><?php esc_html_e( 'Blocked services', 'kukie-cookie-consent' ); ?></legend>
			<div class="kukie-checkbox-grid" id="kukie-iframe-services"></div>
			<p class="kukie-help-text"><?php esc_html_e( 'Deselect a service to let it load straight away.', 'kukie-cookie-consent' ); ?></p>
		</fieldset>
	</div>

	<div class="kukie-form-actions">
		<button type="submit" class="kukie-btn-primary" id="kukie-iframes-save">
			<span class="kukie-btn-text"><?php esc_html_e( 'Save changes', 'kukie-cookie-consent' ); ?></span>
			<span class="kukie-btn-loading" hidden>
				<span class="kukie-spinner" aria-hidden="true"></span>
				<?php esc_html_e( 'Saving…', 'kukie-cookie-consent' ); ?>
			</span>
		</button>
	</div>
</form>
