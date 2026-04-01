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
		<h1><?php esc_html_e( 'Kukie.io Dashboard', 'kukie-cookie-consent' ); ?></h1>
		<a href="<?php echo esc_url( $kukie_plugin->get_option( 'dashboard_url', $kukie_app_url ) ); ?>" target="_blank" rel="noopener noreferrer" class="kukie-external-link">
			<?php esc_html_e( 'Open full dashboard', 'kukie-cookie-consent' ); ?>
			<span class="dashicons dashicons-external"></span>
		</a>
	</div>
	<hr class="wp-header-end">

	<?php if ( $kukie_plugin->is_connected() && ! $kukie_plugin->is_api_key_valid() ) :
		$kukie_key_url = 'https://app.kukie.io/sites/' . rawurlencode( (string) $kukie_site_id );
	?>
		<div class="notice notice-error kukie-notice-api-key-invalid">
			<p>
				<strong><?php esc_html_e( 'Invalid API key.', 'kukie-cookie-consent' ); ?></strong>
				<?php esc_html_e( 'The cookie consent banner is currently disabled on your site.', 'kukie-cookie-consent' ); ?>
			</p>
			<p>
				<?php esc_html_e( 'Your API key has been regenerated or deleted in the Kukie dashboard. Generate a new API key and enter it below to restore the banner.', 'kukie-cookie-consent' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( $kukie_key_url ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary">
					<?php esc_html_e( 'Generate a new API key', 'kukie-cookie-consent' ); ?> &rarr;
				</a>
			</p>
		</div>
	<?php endif; ?>

	<div id="kukie-dashboard-error" class="kukie-notice kukie-notice-error" style="display:none;"></div>

	<!-- Overview Cards -->
	<div class="kukie-stats-grid" id="kukie-overview-cards">
		<div class="kukie-stat-card">
			<div class="kukie-stat-icon kukie-stat-icon--banner">
				<span class="dashicons dashicons-shield"></span>
			</div>
			<div class="kukie-stat-content">
				<span class="kukie-stat-label"><?php esc_html_e( 'Banner Status', 'kukie-cookie-consent' ); ?></span>
				<span class="kukie-stat-value" id="kukie-stat-banner">
					<span class="kukie-skeleton"></span>
				</span>
			</div>
		</div>

		<div class="kukie-stat-card">
			<div class="kukie-stat-icon kukie-stat-icon--consent">
				<span class="dashicons dashicons-chart-bar"></span>
			</div>
			<div class="kukie-stat-content">
				<span class="kukie-stat-label"><?php esc_html_e( 'Consents Today', 'kukie-cookie-consent' ); ?></span>
				<span class="kukie-stat-value" id="kukie-stat-today">
					<span class="kukie-skeleton"></span>
				</span>
			</div>
		</div>

		<a href="<?php echo esc_url( $kukie_app_url . '/billing' ); ?>" target="_blank" rel="noopener noreferrer" class="kukie-stat-card kukie-stat-card--link">
			<div class="kukie-stat-icon kukie-stat-icon--plan">
				<span class="dashicons dashicons-awards"></span>
			</div>
			<div class="kukie-stat-content">
				<span class="kukie-stat-label"><?php esc_html_e( 'Plan', 'kukie-cookie-consent' ); ?></span>
				<span class="kukie-stat-value" id="kukie-stat-plan">
					<span class="kukie-skeleton"></span>
				</span>
			</div>
		</a>

		<div class="kukie-stat-card">
			<div class="kukie-stat-icon kukie-stat-icon--verify">
				<span class="dashicons dashicons-yes-alt"></span>
			</div>
			<div class="kukie-stat-content">
				<span class="kukie-stat-label"><?php esc_html_e( 'Verification', 'kukie-cookie-consent' ); ?></span>
				<span class="kukie-stat-value" id="kukie-stat-verified">
					<span class="kukie-skeleton"></span>
				</span>
			</div>
		</div>
	</div>

	<!-- Two Column Section -->
	<div class="kukie-two-col">
		<!-- Consent Trends -->
		<div class="kukie-card">
			<div class="kukie-card-header">
				<h2 class="kukie-card-title"><?php esc_html_e( 'Consent Overview', 'kukie-cookie-consent' ); ?></h2>
				<a href="<?php echo esc_url( $kukie_app_url . '/sites/' . $kukie_site_id . '/analytics' ); ?>" target="_blank" rel="noopener noreferrer" class="kukie-external-link">
					<?php esc_html_e( 'View analytics', 'kukie-cookie-consent' ); ?>
					<span class="dashicons dashicons-external"></span>
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
					<span class="kukie-consent-label"><?php esc_html_e( 'This Week', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-consent-value" id="kukie-consent-week"><span class="kukie-skeleton"></span></span>
				</div>
				<div class="kukie-consent-row">
					<span class="kukie-consent-label"><?php esc_html_e( 'This Month', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-consent-value" id="kukie-consent-month"><span class="kukie-skeleton"></span></span>
				</div>
			</div>
		</div>

		<!-- Last Scan -->
		<div class="kukie-card">
			<div class="kukie-card-header">
				<h2 class="kukie-card-title"><?php esc_html_e( 'Cookie Scan', 'kukie-cookie-consent' ); ?></h2>
				<div class="kukie-card-actions">
					<a href="<?php echo esc_url( $kukie_app_url . '/sites/' . $kukie_site_id . '/cookies' ); ?>" target="_blank" rel="noopener noreferrer" class="kukie-external-link">
						<?php esc_html_e( 'View cookies', 'kukie-cookie-consent' ); ?>
						<span class="dashicons dashicons-external"></span>
					</a>
					<button type="button" id="kukie-trigger-scan" class="kukie-btn-secondary kukie-btn-sm">
						<span class="kukie-btn-text"><?php esc_html_e( 'Run New Scan', 'kukie-cookie-consent' ); ?></span>
						<span class="kukie-btn-loading" style="display:none;">
							<span class="kukie-spinner"></span>
						</span>
					</button>
				</div>
			</div>
			<div id="kukie-scan-info">
				<div class="kukie-scan-detail" id="kukie-scan-status-row">
					<span class="kukie-scan-label"><?php esc_html_e( 'Status', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-scan-value" id="kukie-scan-status"><span class="kukie-skeleton"></span></span>
				</div>
				<div class="kukie-scan-detail">
					<span class="kukie-scan-label"><?php esc_html_e( 'Last Scan', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-scan-value" id="kukie-scan-date"><span class="kukie-skeleton"></span></span>
				</div>
				<div class="kukie-scan-detail">
					<span class="kukie-scan-label"><?php esc_html_e( 'Cookies Found', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-scan-value" id="kukie-scan-cookies"><span class="kukie-skeleton"></span></span>
				</div>
				<div class="kukie-scan-detail">
					<span class="kukie-scan-label"><?php esc_html_e( 'Pages Scanned', 'kukie-cookie-consent' ); ?></span>
					<span class="kukie-scan-value" id="kukie-scan-pages"><span class="kukie-skeleton"></span></span>
				</div>
			</div>
		</div>
	</div>

	<!-- CTA Section -->
	<div class="kukie-card kukie-cta-card">
		<h2 class="kukie-card-title"><?php esc_html_e( 'Customise Your Banner', 'kukie-cookie-consent' ); ?></h2>
		<p><?php esc_html_e( 'Design your cookie banner, manage cookie categories, and configure consent rules in the Kukie.io dashboard.', 'kukie-cookie-consent' ); ?></p>
		<a href="<?php echo esc_url( $kukie_app_url . '/sites/' . $kukie_site_id . '/banner' ); ?>" target="_blank" rel="noopener noreferrer" class="kukie-btn-primary">
			<?php esc_html_e( 'Open Kukie.io Dashboard', 'kukie-cookie-consent' ); ?>
			<span class="dashicons dashicons-external"></span>
		</a>
	</div>
</div>
