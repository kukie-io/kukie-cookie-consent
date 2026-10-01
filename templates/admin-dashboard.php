<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$kukie_plugin  = Kukie_Plugin::instance();
$kukie_site_id = absint( $kukie_plugin->get_option( 'site_id', 0 ) );
$kukie_app_url = 'https://app.kukie.io';
?>
<div class="wrap kukie-wrap kukie-wrap--wide">
	<div class="kukie-header">
		<h1><?php esc_html_e( 'Kukie.io dashboard', 'kukie-cookie-consent' ); ?></h1>
		<a href="<?php echo esc_url( $kukie_plugin->get_option( 'dashboard_url', $kukie_app_url ) ); ?>" target="_blank" rel="noopener noreferrer" class="kukie-external-link">
			<?php esc_html_e( 'Open Kukie.io', 'kukie-cookie-consent' ); ?>
			<?php echo Kukie_Admin::new_tab_marker(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses-sanitised in the helper ?>
		</a>
	</div>
	<hr class="wp-header-end">

	<?php if ( $kukie_plugin->is_connected() && ! $kukie_plugin->is_api_key_valid() ) :
		$kukie_key_url = 'https://app.kukie.io/sites/' . rawurlencode( (string) $kukie_site_id );
	?>
		<div class="notice notice-error inline kukie-notice-api-key-invalid">
			<p>
				<strong><?php esc_html_e( 'The API key is no longer valid.', 'kukie-cookie-consent' ); ?></strong>
				<?php esc_html_e( 'Stats, scans and settings sync are paused. Your consent banner keeps working.', 'kukie-cookie-consent' ); ?>
			</p>
			<p>
				<?php esc_html_e( 'The key was regenerated or deleted on Kukie.io. To restore the connection, generate a new API key and reconnect with it.', 'kukie-cookie-consent' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( $kukie_key_url ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary">
					<?php esc_html_e( 'Generate a new API key', 'kukie-cookie-consent' ); ?>
					<?php echo Kukie_Admin::new_tab_marker(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses-sanitised in the helper ?>
				</a>
			</p>
		</div>
	<?php endif; ?>

	<div id="kukie-dashboard-error" class="notice notice-error inline kukie-notice" role="alert" hidden><p></p></div>

	<!-- Overview cards: banner health on the first row, features and plan on the second -->
	<h2 class="screen-reader-text"><?php esc_html_e( 'Overview', 'kukie-cookie-consent' ); ?></h2>
	<div class="kukie-stats-grid" id="kukie-overview-cards" aria-live="polite">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . Kukie_Admin::PAGE_SETTINGS ) ); ?>" class="kukie-stat-card kukie-stat-card--link">
			<div class="kukie-stat-icon kukie-stat-icon--banner" aria-hidden="true">
				<span class="dashicons dashicons-shield"></span>
			</div>
			<div class="kukie-stat-content">
				<span class="kukie-stat-label"><?php esc_html_e( 'Consent banner', 'kukie-cookie-consent' ); ?><span class="kukie-stat-card-go dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span></span>
				<span class="kukie-stat-value" id="kukie-stat-banner">
					<span class="kukie-skeleton"></span>
				</span>
			</div>
		</a>

		<div class="kukie-stat-card">
			<div class="kukie-stat-icon kukie-stat-icon--verify" aria-hidden="true">
				<span class="dashicons dashicons-yes-alt"></span>
			</div>
			<div class="kukie-stat-content">
				<span class="kukie-stat-label"><?php esc_html_e( 'Verification', 'kukie-cookie-consent' ); ?></span>
				<span class="kukie-stat-value" id="kukie-stat-verified">
					<span class="kukie-skeleton"></span>
				</span>
			</div>
		</div>

		<div class="kukie-stat-card">
			<div class="kukie-stat-icon kukie-stat-icon--consent" aria-hidden="true">
				<span class="dashicons dashicons-chart-bar"></span>
			</div>
			<div class="kukie-stat-content">
				<span class="kukie-stat-label"><?php esc_html_e( 'Consents today', 'kukie-cookie-consent' ); ?></span>
				<span class="kukie-stat-value" id="kukie-stat-today">
					<span class="kukie-skeleton"></span>
				</span>
			</div>
		</div>

		<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . Kukie_Admin::PAGE_ACCESSIBILITY ) ); ?>" class="kukie-stat-card kukie-stat-card--link">
			<div class="kukie-stat-icon kukie-stat-icon--a11y" aria-hidden="true">
				<span class="dashicons dashicons-universal-access-alt"></span>
			</div>
			<div class="kukie-stat-content">
				<span class="kukie-stat-label"><?php esc_html_e( 'Accessibility widget', 'kukie-cookie-consent' ); ?><span class="kukie-stat-card-go dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span></span>
				<span class="kukie-stat-value" id="kukie-stat-a11y">
					<span class="kukie-skeleton"></span>
				</span>
			</div>
		</a>

		<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . Kukie_Admin::PAGE_UPTIME ) ); ?>" class="kukie-stat-card kukie-stat-card--link">
			<div class="kukie-stat-icon kukie-stat-icon--uptime" aria-hidden="true">
				<span class="dashicons dashicons-heart"></span>
			</div>
			<div class="kukie-stat-content">
				<span class="kukie-stat-label"><?php esc_html_e( 'Uptime monitoring', 'kukie-cookie-consent' ); ?><span class="kukie-stat-card-go dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span></span>
				<span class="kukie-stat-value" id="kukie-stat-uptime">
					<span class="kukie-skeleton"></span>
				</span>
			</div>
		</a>

		<a href="<?php echo esc_url( $kukie_app_url . '/billing' ); ?>" target="_blank" rel="noopener noreferrer" class="kukie-stat-card kukie-stat-card--link">
			<div class="kukie-stat-icon kukie-stat-icon--plan" aria-hidden="true">
				<span class="dashicons dashicons-awards"></span>
			</div>
			<div class="kukie-stat-content">
				<span class="kukie-stat-label"><?php esc_html_e( 'Plan', 'kukie-cookie-consent' ); ?><span class="kukie-stat-card-go"><?php echo Kukie_Admin::new_tab_marker(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses-sanitised in the helper ?></span></span>
				<span class="kukie-stat-value" id="kukie-stat-plan">
					<span class="kukie-skeleton"></span>
				</span>
			</div>
		</a>
	</div>

	<!-- Two Column Section -->
	<div class="kukie-two-col">
		<!-- Consent Trends -->
		<div class="kukie-card">
			<div class="kukie-card-header">
				<h2 class="kukie-card-title"><?php esc_html_e( 'Consents', 'kukie-cookie-consent' ); ?></h2>
				<a href="<?php echo esc_url( $kukie_app_url . '/sites/' . $kukie_site_id . '/analytics' ); ?>" target="_blank" rel="noopener noreferrer" class="kukie-external-link">
					<?php esc_html_e( 'View analytics', 'kukie-cookie-consent' ); ?>
					<?php echo Kukie_Admin::new_tab_marker(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses-sanitised in the helper ?>
				</a>
			</div>
			<div class="kukie-consent-overview" id="kukie-consent-overview">
				<div class="kukie-consent-row">
					<span class="kukie-consent-label"><?php esc_html_e( 'Today', 'kukie-cookie-consent' ); ?></span>
					<div class="kukie-consent-values" id="kukie-consent-today">
						<span class="kukie-skeleton"></span>
					</div>
				</div>
				<div class="kukie-consent-row">
					<span class="kukie-consent-label"><?php esc_html_e( 'Last 7 days', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-consent-value" id="kukie-consent-week"><span class="kukie-skeleton"></span></span>
				</div>
				<div class="kukie-consent-row">
					<span class="kukie-consent-label"><?php esc_html_e( 'Last 30 days', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-consent-value" id="kukie-consent-month"><span class="kukie-skeleton"></span></span>
				</div>
			</div>
		</div>

		<!-- Last Scan -->
		<div class="kukie-card">
			<div class="kukie-card-header">
				<h2 class="kukie-card-title"><?php esc_html_e( 'Cookie scan', 'kukie-cookie-consent' ); ?></h2>
				<div class="kukie-card-actions">
					<a href="<?php echo esc_url( $kukie_app_url . '/sites/' . $kukie_site_id . '/cookies' ); ?>" target="_blank" rel="noopener noreferrer" class="kukie-external-link">
						<?php esc_html_e( 'View cookies', 'kukie-cookie-consent' ); ?>
						<?php echo Kukie_Admin::new_tab_marker(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses-sanitised in the helper ?>
					</a>
					<button type="button" id="kukie-trigger-scan" class="kukie-btn-secondary kukie-btn-sm">
						<span class="kukie-btn-text"><?php esc_html_e( 'Scan now', 'kukie-cookie-consent' ); ?></span>
						<span class="kukie-btn-loading" hidden>
							<span class="kukie-spinner" aria-hidden="true"></span>
							<?php esc_html_e( 'Starting…', 'kukie-cookie-consent' ); ?>
						</span>
					</button>
				</div>
			</div>
			<p class="kukie-help-text" id="kukie-scan-progress" role="status" hidden></p>
			<div id="kukie-scan-info">
				<div class="kukie-scan-detail" id="kukie-scan-status-row">
					<span class="kukie-scan-label"><?php esc_html_e( 'Status', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-scan-value" id="kukie-scan-status"><span class="kukie-skeleton"></span></span>
				</div>
				<div class="kukie-scan-detail">
					<span class="kukie-scan-label"><?php esc_html_e( 'Last scan', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-scan-value" id="kukie-scan-date"><span class="kukie-skeleton"></span></span>
				</div>
				<div class="kukie-scan-detail">
					<span class="kukie-scan-label"><?php esc_html_e( 'Cookies found', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-scan-value" id="kukie-scan-cookies"><span class="kukie-skeleton"></span></span>
				</div>
				<div class="kukie-scan-detail">
					<span class="kukie-scan-label"><?php esc_html_e( 'Pages scanned', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-scan-value" id="kukie-scan-pages"><span class="kukie-skeleton"></span></span>
				</div>
			</div>
		</div>
	</div>

	<!-- CTA Section -->
	<div class="kukie-card kukie-cta-card">
		<h2 class="kukie-card-title"><?php esc_html_e( 'Customise your banner', 'kukie-cookie-consent' ); ?></h2>
		<p><?php esc_html_e( 'Choose the layout and revisit button here in WordPress, or design every colour, text, category and consent rule in the Kukie.io dashboard.', 'kukie-cookie-consent' ); ?></p>
		<p class="kukie-card-actions-row kukie-card-actions-row--center">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . Kukie_Admin::PAGE_BANNER ) ); ?>" class="kukie-btn-secondary">
				<?php esc_html_e( 'Consent banner settings', 'kukie-cookie-consent' ); ?>
			</a>
			<a href="<?php echo esc_url( $kukie_app_url . '/sites/' . $kukie_site_id . '/banner' ); ?>" target="_blank" rel="noopener noreferrer" class="kukie-btn-primary">
				<?php esc_html_e( 'Open the banner editor on Kukie.io', 'kukie-cookie-consent' ); ?>
				<?php echo Kukie_Admin::new_tab_marker(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses-sanitised in the helper ?>
			</a>
		</p>
	</div>
</div>
