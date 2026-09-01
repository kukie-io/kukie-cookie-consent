<?php

/**
 * 1.8.0 feature lock: the menu restructure must not break a single
 * pre-1.8.0 bookmark or inter-page link.
 *
 * Banner Design (kukie-design), Google Consent Mode v2 (kukie-gcm) and
 * Microsoft UET (kukie-uet) became tabs of one Consent banner page. The old
 * slugs stay registered as hidden pages (so WordPress's page-access check
 * passes) and redirect to the matching tab before any output.
 */
final class LegacyPageRedirectTest extends Kukie_Test_Case {

	public function test_every_legacy_slug_redirects_to_its_cookie_banner_tab(): void {
		$admin = new Kukie_Admin( Kukie_Plugin::instance() );

		foreach ( Kukie_Admin::LEGACY_PAGES as $slug => $tab ) {
			$_GET = [ 'page' => $slug ];

			try {
				$admin->redirect_legacy_page();
				$this->fail( "No redirect for {$slug}." );
			} catch ( Kukie_Redirect $redirect ) {
				$this->assertSame( Kukie_Admin::banner_tab_url( $tab ), $redirect->location );
				$this->assertStringContainsString( 'page=kukie-banner', $redirect->location );
				if ( $tab !== 'design' ) {
					$this->assertStringContainsString( 'tab=' . $tab, $redirect->location );
				}
			}
		}
	}

	public function test_the_design_tab_is_the_bare_page_url(): void {
		// The default tab carries no ?tab= so the menu link and the redirect
		// target are the same URL (one bookmark, one history entry).
		$this->assertStringNotContainsString( 'tab=', Kukie_Admin::banner_tab_url( 'design' ) );
		$this->assertStringContainsString( 'tab=gcm', Kukie_Admin::banner_tab_url( 'gcm' ) );
		$this->assertStringNotContainsString( 'tab=', Kukie_Admin::banner_tab_url( 'bogus' ), 'An unknown tab never leaks into a URL.' );
	}

	public function test_the_tab_selector_is_whitelisted(): void {
		$_GET = [];
		$this->assertSame( 'design', Kukie_Admin::current_banner_tab() );

		$_GET = [ 'tab' => 'gcm' ];
		$this->assertSame( 'gcm', Kukie_Admin::current_banner_tab() );

		$_GET = [ 'tab' => 'UET' ];
		$this->assertSame( 'uet', Kukie_Admin::current_banner_tab(), 'sanitize_key lowercases.' );

		$_GET = [ 'tab' => '../../etc/passwd' ];
		$this->assertSame( 'design', Kukie_Admin::current_banner_tab() );
	}

	public function test_a_connected_install_registers_the_legacy_pages_with_a_load_hook(): void {
		$this->seedConnectedInstall();

		$admin = new Kukie_Admin( Kukie_Plugin::instance() );
		$admin->register_menus();

		// The stub returns one hook name for every submenu page, so this can
		// only assert that SOME load-hook was attached; the redirect itself
		// is covered above.
		$this->assertTrue( kukie_test_hook_registered( 'load-kukie_page_sub' ) );
	}

	public function test_the_legacy_slugs_are_never_visible_menu_slugs(): void {
		foreach ( array_keys( Kukie_Admin::LEGACY_PAGES ) as $slug ) {
			$this->assertNotContains( $slug, [
				Kukie_Admin::PAGE_DASHBOARD,
				Kukie_Admin::PAGE_BANNER,
				Kukie_Admin::PAGE_ACCESSIBILITY,
				Kukie_Admin::PAGE_SETTINGS,
				Kukie_Admin::PAGE_CONNECT,
			] );
		}
	}
}
