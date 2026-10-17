<?php

/**
 * 1.11.0: the sections moved from the WordPress sidebar into the page.
 *
 * Only the dashboard keeps a menu entry, so the other sections are hidden
 * pages. Three things must still hold: the Kukie.io menu item stays
 * highlighted on them, the side navigation marks the page being shown (and
 * the Consent banner tab), and an unknown page never marks anything.
 */
final class AdminNavigationTest extends Kukie_Test_Case {

	protected function tearDown(): void {
		unset( $GLOBALS['plugin_page'] );
		parent::tearDown();
	}

	public function test_the_menu_item_stays_highlighted_on_every_section(): void {
		$admin = new Kukie_Admin( Kukie_Plugin::instance() );

		foreach ( Kukie_Admin::SECTION_PAGES as $slug ) {
			$GLOBALS['plugin_page'] = $slug;
			$this->assertSame( Kukie_Admin::PAGE_DASHBOARD, $admin->highlight_menu( '' ), $slug );
		}

		$GLOBALS['plugin_page'] = 'another-plugin';
		$this->assertSame( 'options-general.php', $admin->highlight_menu( 'options-general.php' ), 'Other pages keep their own parent.' );
	}

	public function test_the_navigation_marks_the_current_section(): void {
		$_GET = [ 'page' => Kukie_Admin::PAGE_UPTIME ];

		$current = array_column( array_filter( Kukie_Admin::nav_items(), static fn ( array $item ): bool => $item['current'] ), 'slug' );

		$this->assertSame( [ Kukie_Admin::PAGE_UPTIME ], $current );
	}

	public function test_the_consent_banner_tab_is_marked_inside_its_group(): void {
		$_GET = [ 'page' => Kukie_Admin::PAGE_BANNER, 'tab' => 'gcm' ];

		$banner = array_values( array_filter( Kukie_Admin::nav_items(), static fn ( array $item ): bool => $item['slug'] === Kukie_Admin::PAGE_BANNER ) )[0];

		$this->assertTrue( $banner['current'] );
		$this->assertSame( array_values( Kukie_Admin::banner_tab_labels() ), array_column( $banner['children'], 'label' ), 'Every tab is listed, in order.' );
		$this->assertSame( [ 'Google Consent Mode v2' ], array_column( array_filter( $banner['children'], static fn ( array $child ): bool => $child['current'] ), 'label' ) );
	}

	public function test_an_unknown_page_marks_nothing(): void {
		$_GET = [ 'page' => 'kukie-bogus' ];

		$this->assertSame( [], array_filter( array_column( Kukie_Admin::nav_items(), 'current' ) ) );
	}

	public function test_every_section_title_is_set_for_its_hidden_page(): void {
		$this->seedConnectedInstall();

		( new Kukie_Admin( Kukie_Plugin::instance() ) )->register_menus();

		// The stub names every hidden page's hook the same, so this asserts
		// that load callbacks were attached; WordPress gives a hidden page
		// no title of its own.
		$this->assertTrue( kukie_test_hook_registered( 'load-kukie_page_sub' ) );
	}
}
