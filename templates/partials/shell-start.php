<?php
/**
 * Page chrome shared by every connected admin page: the brand bar and the
 * side navigation. The page's own header and content render after it, and
 * templates/partials/shell-end.php closes it. The WordPress sidebar keeps a
 * single Kukie.io entry; the sections live in this navigation.
 *
 * On narrow screens the navigation becomes a row of sections above the page,
 * and the Consent banner page shows its own tab strip instead of the nested
 * list (admin.css).
 *
 * @since 1.11.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$kukie_shell_plugin    = Kukie_Plugin::instance();
$kukie_shell_domain    = (string) $kukie_shell_plugin->get_option( 'domain', '' );
$kukie_shell_plan      = (string) $kukie_shell_plugin->get_option( 'plan_name', '' );
$kukie_shell_dashboard = (string) $kukie_shell_plugin->get_option( 'dashboard_url', 'https://app.kukie.io' );
?>
<div class="wrap kukie-wrap kukie-shell">
	<header class="kukie-appbar">
		<a class="kukie-appbar-brand" href="<?php echo esc_url( admin_url( 'admin.php?page=' . Kukie_Admin::PAGE_DASHBOARD ) ); ?>">
			<img src="<?php echo esc_url( KUKIE_PLUGIN_URL . 'assets/images/kukie-logo-dark.svg' ); ?>" width="100" height="20" alt="<?php esc_attr_e( 'Kukie.io', 'kukie-cookie-consent' ); ?>" />
		</a>
		<p class="kukie-appbar-site">
			<?php if ( $kukie_shell_domain !== '' ) : ?>
				<span class="kukie-appbar-domain"><?php echo esc_html( $kukie_shell_domain ); ?></span>
			<?php endif; ?>
			<?php // Rendered even when empty, so admin.js can fill it from /status. ?>
			<span class="kukie-appbar-plan" id="kukie-appbar-plan"<?php echo $kukie_shell_plan === '' ? ' hidden' : ''; ?>><?php echo esc_html( $kukie_shell_plan ); ?></span>
		</p>
		<a class="kukie-appbar-link" href="<?php echo esc_url( $kukie_shell_dashboard ); ?>" target="_blank" rel="noopener noreferrer">
			<?php esc_html_e( 'Open Kukie.io', 'kukie-cookie-consent' ); ?>
			<?php echo Kukie_Admin::new_tab_marker(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses-sanitised in the helper ?>
		</a>
	</header>

	<div class="kukie-layout">
		<nav class="kukie-sidenav" aria-label="<?php esc_attr_e( 'Kukie.io', 'kukie-cookie-consent' ); ?>">
			<ul class="kukie-nav">
				<?php foreach ( Kukie_Admin::nav_items() as $kukie_nav_item ) : ?>
					<?php $kukie_nav_open = $kukie_nav_item['current'] && $kukie_nav_item['children'] !== []; ?>
					<li class="kukie-nav-entry<?php echo $kukie_nav_open ? ' is-open' : ''; ?>">
						<a href="<?php echo esc_url( $kukie_nav_item['url'] ); ?>" class="kukie-nav-link<?php echo $kukie_nav_item['current'] ? ' is-current' : ''; ?>"<?php echo $kukie_nav_item['current'] && $kukie_nav_item['children'] === [] ? ' aria-current="page"' : ''; ?>>
							<span class="dashicons dashicons-<?php echo esc_attr( $kukie_nav_item['icon'] ); ?>" aria-hidden="true"></span>
							<span class="kukie-nav-label"><?php echo esc_html( $kukie_nav_item['label'] ); ?></span>
						</a>
						<?php if ( $kukie_nav_item['children'] !== [] ) : ?>
							<ul class="kukie-nav-sub">
								<?php foreach ( $kukie_nav_item['children'] as $kukie_nav_child ) : ?>
									<li>
										<a href="<?php echo esc_url( $kukie_nav_child['url'] ); ?>" class="kukie-nav-sublink<?php echo $kukie_nav_child['current'] ? ' is-current' : ''; ?>"<?php echo $kukie_nav_child['current'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $kukie_nav_child['label'] ); ?></a>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<div class="kukie-main">
