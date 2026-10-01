<?php
/**
 * Uptime monitoring page. Every value is read from and written to
 * app.kukie.io through the plugin API (GET/PUT /uptime) - nothing is stored
 * in WordPress. The menu entry is visible on every plan: admin.js renders
 * the locked pitch when the plan does not include the feature, and the
 * status, the basic settings, recent incidents and the checker details when
 * it does. Extra alert recipients and the webhook stay on the Kukie.io
 * dashboard (the API never sends them here).
 *
 * @since 1.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$kukie_plugin  = Kukie_Plugin::instance();
$kukie_site_id = absint( $kukie_plugin->get_option( 'site_id', 0 ) );
$kukie_app_url = 'https://app.kukie.io';
$kukie_up_url  = $kukie_app_url . '/sites/' . $kukie_site_id . '/uptime';
?>
<div class="wrap kukie-wrap">
	<div class="kukie-header">
		<h1><?php esc_html_e( 'Uptime monitoring', 'kukie-cookie-consent' ); ?></h1>
		<a href="<?php echo esc_url( $kukie_up_url ); ?>" target="_blank" rel="noopener noreferrer" class="kukie-external-link">
			<?php esc_html_e( 'Open on Kukie.io', 'kukie-cookie-consent' ); ?>
			<?php echo Kukie_Admin::new_tab_marker(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses-sanitised in the helper ?>
		</a>
	</div>
	<hr class="wp-header-end">

	<div id="kukie-uptime-error" class="notice notice-error inline kukie-notice" role="alert" hidden><p></p></div>

	<div id="kukie-uptime-loading" class="kukie-loading" role="status">
		<span class="kukie-spinner" aria-hidden="true"></span>
		<?php esc_html_e( 'Loading uptime monitoring…', 'kukie-cookie-consent' ); ?>
	</div>

	<div id="kukie-uptime-content" hidden>

		<!-- Locked: the plan does not include uptime monitoring -->
		<div id="kukie-uptime-locked" hidden>
			<div class="kukie-card kukie-card--locked">
				<h2 class="kukie-card-title"><?php esc_html_e( 'Know when your site goes down', 'kukie-cookie-consent' ); ?></h2>
				<p id="kukie-uptime-locked-text" class="kukie-card-description"></p>
				<ul class="kukie-feature-list">
					<li><?php esc_html_e( 'Kukie.io checks your homepage around the clock and emails you when it stops responding, and again when it is back, with the cause in plain words.', 'kukie-cookie-consent' ); ?></li>
					<li><?php esc_html_e( 'An alert needs two failed checks in a row, so a single network blip is ignored.', 'kukie-cookie-consent' ); ?></li>
					<li><?php esc_html_e( 'You get a warning before your SSL certificate expires, and a monthly uptime report if you want one.', 'kukie-cookie-consent' ); ?></li>
					<li><?php esc_html_e( 'Checks run no JavaScript, so they never count as visits in Google Analytics or similar tools.', 'kukie-cookie-consent' ); ?></li>
				</ul>
				<p class="kukie-card-actions-row">
					<a id="kukie-uptime-upgrade" href="<?php echo esc_url( $kukie_app_url . '/billing' ); ?>" target="_blank" rel="noopener noreferrer" class="kukie-btn-primary">
						<span id="kukie-uptime-upgrade-label"><?php esc_html_e( 'See plans on Kukie.io', 'kukie-cookie-consent' ); ?></span>
						<?php echo Kukie_Admin::new_tab_marker(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses-sanitised in the helper ?>
					</a>
					<button type="button" id="kukie-uptime-recheck" class="kukie-btn-secondary">
						<span class="kukie-btn-text"><?php esc_html_e( 'Already upgraded? Check again', 'kukie-cookie-consent' ); ?></span>
						<span class="kukie-btn-loading" hidden>
							<span class="kukie-spinner" aria-hidden="true"></span>
						</span>
					</button>
				</p>
			</div>

			<div class="kukie-card" id="kukie-uptime-plans-card" hidden>
				<h2 class="kukie-card-title"><?php esc_html_e( 'Plans with uptime monitoring', 'kukie-cookie-consent' ); ?></h2>
				<table class="widefat striped kukie-table">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Plan', 'kukie-cookie-consent' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Fastest check', 'kukie-cookie-consent' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Incident history', 'kukie-cookie-consent' ); ?></th>
						</tr>
					</thead>
					<tbody id="kukie-uptime-plans"></tbody>
				</table>
			</div>
		</div>

		<!-- Unlocked -->
		<div id="kukie-uptime-unlocked" hidden>

			<!-- Status -->
			<div class="kukie-card kukie-uptime-status" id="kukie-uptime-status" aria-live="polite">
				<div class="kukie-uptime-status-head">
					<span class="kukie-uptime-dot" id="kukie-uptime-dot" aria-hidden="true"></span>
					<div class="kukie-uptime-status-text">
						<h2 class="kukie-card-title" id="kukie-uptime-status-title"></h2>
						<p class="kukie-card-description" id="kukie-uptime-status-body"></p>
					</div>
				</div>
				<dl class="kukie-uptime-stats" id="kukie-uptime-stats" hidden>
					<div class="kukie-uptime-stat">
						<dt><?php esc_html_e( 'Uptime, 24 hours', 'kukie-cookie-consent' ); ?></dt>
						<dd id="kukie-uptime-24h"></dd>
					</div>
					<div class="kukie-uptime-stat">
						<dt><?php esc_html_e( 'Uptime, 7 days', 'kukie-cookie-consent' ); ?></dt>
						<dd id="kukie-uptime-7d"></dd>
					</div>
					<div class="kukie-uptime-stat">
						<dt><?php esc_html_e( 'Uptime, 30 days', 'kukie-cookie-consent' ); ?></dt>
						<dd id="kukie-uptime-30d"></dd>
					</div>
					<div class="kukie-uptime-stat">
						<dt><?php esc_html_e( 'Average response, 24 hours', 'kukie-cookie-consent' ); ?></dt>
						<dd id="kukie-uptime-avg"></dd>
					</div>
					<div class="kukie-uptime-stat">
						<dt><?php esc_html_e( 'Incidents, 30 days', 'kukie-cookie-consent' ); ?></dt>
						<dd id="kukie-uptime-incidents-30d"></dd>
					</div>
					<div class="kukie-uptime-stat">
						<dt><?php esc_html_e( 'SSL certificate', 'kukie-cookie-consent' ); ?></dt>
						<dd id="kukie-uptime-ssl"></dd>
					</div>
				</dl>
			</div>

			<!-- Settings -->
			<form id="kukie-uptime-form" novalidate>
				<div class="kukie-card">
					<h2 class="kukie-card-title"><?php esc_html_e( 'Settings', 'kukie-cookie-consent' ); ?></h2>

					<div class="kukie-form-row">
						<div class="kukie-form-row-label">
							<span id="kukie-uptime-enabled-label"><?php esc_html_e( 'Monitor this site', 'kukie-cookie-consent' ); ?></span>
							<span class="kukie-form-row-hint" id="kukie-uptime-enabled-hint"><?php esc_html_e( 'When on, Kukie.io checks the page below at the interval you choose and alerts you when it stops responding.', 'kukie-cookie-consent' ); ?></span>
						</div>
						<label class="kukie-toggle">
							<input type="checkbox" role="switch" id="kukie-uptime-enabled" value="1" aria-labelledby="kukie-uptime-enabled-label" aria-describedby="kukie-uptime-enabled-hint">
							<span class="kukie-toggle-slider" aria-hidden="true"></span>
						</label>
					</div>

					<div class="kukie-form-group">
						<label for="kukie-uptime-url"><?php esc_html_e( 'Page to check', 'kukie-cookie-consent' ); ?></label>
						<input type="url" id="kukie-uptime-url" class="kukie-input" maxlength="255" autocomplete="url" spellcheck="false" aria-describedby="kukie-uptime-url-hint">
						<p class="kukie-help-text" id="kukie-uptime-url-hint"></p>
					</div>

					<fieldset class="kukie-fieldset kukie-form-group">
						<legend class="kukie-legend"><?php esc_html_e( 'Check interval', 'kukie-cookie-consent' ); ?></legend>
						<p class="kukie-help-text kukie-help-text--above" id="kukie-uptime-interval-hint"><?php esc_html_e( 'A failed check is always repeated one minute later, whatever the interval, and two failures in a row count as an outage. Faster checks also notice a recovery sooner.', 'kukie-cookie-consent' ); ?></p>
						<div class="kukie-interval-grid" id="kukie-uptime-intervals"></div>
					</fieldset>

					<div class="kukie-form-row">
						<div class="kukie-form-row-label">
							<span id="kukie-uptime-notify-label"><?php esc_html_e( 'Email the organisation owner', 'kukie-cookie-consent' ); ?></span>
							<span class="kukie-form-row-hint" id="kukie-uptime-notify-hint"><?php esc_html_e( 'Sends an email when the site goes down and again when it is back, plus SSL certificate warnings.', 'kukie-cookie-consent' ); ?></span>
						</div>
						<label class="kukie-toggle">
							<input type="checkbox" role="switch" id="kukie-uptime-notify" value="1" aria-labelledby="kukie-uptime-notify-label" aria-describedby="kukie-uptime-notify-hint">
							<span class="kukie-toggle-slider" aria-hidden="true"></span>
						</label>
					</div>

					<div class="kukie-form-row">
						<div class="kukie-form-row-label">
							<span id="kukie-uptime-report-label"><?php esc_html_e( 'Monthly uptime report', 'kukie-cookie-consent' ); ?></span>
							<span class="kukie-form-row-hint" id="kukie-uptime-report-hint"><?php esc_html_e( "Emails the organisation owner a summary of this site's uptime at the start of each month.", 'kukie-cookie-consent' ); ?></span>
						</div>
						<label class="kukie-toggle">
							<input type="checkbox" role="switch" id="kukie-uptime-report" value="1" aria-labelledby="kukie-uptime-report-label" aria-describedby="kukie-uptime-report-hint">
							<span class="kukie-toggle-slider" aria-hidden="true"></span>
						</label>
					</div>

					<dl class="kukie-info-grid kukie-uptime-managed">
						<div class="kukie-info-row">
							<dt class="kukie-info-label"><?php esc_html_e( 'Additional recipients', 'kukie-cookie-consent' ); ?></dt>
							<dd class="kukie-info-value" id="kukie-uptime-recipients"></dd>
						</div>
						<div class="kukie-info-row">
							<dt class="kukie-info-label"><?php esc_html_e( 'Webhook', 'kukie-cookie-consent' ); ?></dt>
							<dd class="kukie-info-value" id="kukie-uptime-webhook"></dd>
						</div>
					</dl>
					<p class="kukie-help-text">
						<?php
						printf(
							/* translators: %s: link to the site's Uptime page on Kukie.io */
							esc_html__( 'Additional recipients and the webhook are managed by an owner or admin in %s.', 'kukie-cookie-consent' ),
							'<a href="' . esc_url( $kukie_up_url . '?tab=settings' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'the uptime settings on Kukie.io', 'kukie-cookie-consent' ) . Kukie_Admin::new_tab_marker() . '</a>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every part escaped above
						);
						?>
					</p>
				</div>

				<div class="kukie-form-actions">
					<button type="submit" class="kukie-btn-primary" id="kukie-uptime-save">
						<span class="kukie-btn-text"><?php esc_html_e( 'Save changes', 'kukie-cookie-consent' ); ?></span>
						<span class="kukie-btn-loading" hidden>
							<span class="kukie-spinner" aria-hidden="true"></span>
							<?php esc_html_e( 'Saving…', 'kukie-cookie-consent' ); ?>
						</span>
					</button>
				</div>
			</form>

			<!-- Recent incidents -->
			<div class="kukie-card">
				<div class="kukie-card-header">
					<h2 class="kukie-card-title"><?php esc_html_e( 'Recent incidents', 'kukie-cookie-consent' ); ?></h2>
					<a href="<?php echo esc_url( $kukie_up_url . '?tab=incidents' ); ?>" target="_blank" rel="noopener noreferrer" class="kukie-external-link">
						<?php esc_html_e( 'View all incidents', 'kukie-cookie-consent' ); ?>
						<?php echo Kukie_Admin::new_tab_marker(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses-sanitised in the helper ?>
					</a>
				</div>
				<div id="kukie-uptime-incidents-empty" class="kukie-empty" hidden>
					<p class="kukie-empty-title"><?php esc_html_e( 'No incidents yet', 'kukie-cookie-consent' ); ?></p>
					<p class="kukie-empty-body"><?php esc_html_e( 'An outage appears here after two failed checks in a row.', 'kukie-cookie-consent' ); ?></p>
				</div>
				<table class="widefat striped kukie-table" id="kukie-uptime-incidents-table" hidden>
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Started', 'kukie-cookie-consent' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Duration', 'kukie-cookie-consent' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Cause', 'kukie-cookie-consent' ); ?></th>
						</tr>
					</thead>
					<tbody id="kukie-uptime-incidents"></tbody>
				</table>
			</div>

			<!-- Checker details -->
			<div class="kukie-card">
				<h2 class="kukie-card-title"><?php esc_html_e( 'Let the checker through your firewall', 'kukie-cookie-consent' ); ?></h2>
				<p class="kukie-card-description"><?php esc_html_e( 'Security plugins such as Wordfence, and firewalls such as Cloudflare, can block the check and make a working site look down. Add these addresses and this user agent to their allow list.', 'kukie-cookie-consent' ); ?></p>
				<dl class="kukie-info-grid">
					<div class="kukie-info-row">
						<dt class="kukie-info-label"><?php esc_html_e( 'IPv4 address', 'kukie-cookie-consent' ); ?></dt>
						<dd class="kukie-info-value kukie-copy-row"><code id="kukie-uptime-ipv4"></code><button type="button" class="button-link kukie-copy-btn" data-kukie-copy="kukie-uptime-ipv4" aria-label="<?php esc_attr_e( 'Copy IPv4 address', 'kukie-cookie-consent' ); ?>"><?php esc_html_e( 'Copy', 'kukie-cookie-consent' ); ?></button></dd>
					</div>
					<div class="kukie-info-row">
						<dt class="kukie-info-label"><?php esc_html_e( 'IPv6 address', 'kukie-cookie-consent' ); ?></dt>
						<dd class="kukie-info-value kukie-copy-row"><code id="kukie-uptime-ipv6"></code><button type="button" class="button-link kukie-copy-btn" data-kukie-copy="kukie-uptime-ipv6" aria-label="<?php esc_attr_e( 'Copy IPv6 address', 'kukie-cookie-consent' ); ?>"><?php esc_html_e( 'Copy', 'kukie-cookie-consent' ); ?></button></dd>
					</div>
					<div class="kukie-info-row">
						<dt class="kukie-info-label"><?php esc_html_e( 'User agent', 'kukie-cookie-consent' ); ?></dt>
						<dd class="kukie-info-value kukie-copy-row"><code id="kukie-uptime-ua"></code><button type="button" class="button-link kukie-copy-btn" data-kukie-copy="kukie-uptime-ua" aria-label="<?php esc_attr_e( 'Copy user agent', 'kukie-cookie-consent' ); ?>"><?php esc_html_e( 'Copy', 'kukie-cookie-consent' ); ?></button></dd>
					</div>
				</dl>
				<p class="kukie-help-text"><?php esc_html_e( 'Checks run no JavaScript, so they never count as visits in Google Analytics or similar tools. Server-log statistics can filter them out by the user agent.', 'kukie-cookie-consent' ); ?></p>
			</div>
		</div>
	</div>
</div>
