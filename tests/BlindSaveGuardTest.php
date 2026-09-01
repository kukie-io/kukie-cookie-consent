<?php

/**
 * KUK-QA-2026-358: a settings page must not stay saveable after a failed load.
 *
 * An unpopulated form is not empty - it holds the template's defaults, which
 * mean banner_enabled unchecked and an empty language grid. Posting those is a
 * last-write-wins overwrite that disables the live banner and wipes the
 * configured language set, off a single transient 5xx followed by a click.
 *
 * HONEST SCOPE: this fix lives in assets/js/admin.js and runs in a browser,
 * which this suite cannot drive. These are STATIC assertions that the guards
 * are present and structurally load-bearing - materially weaker than the
 * behavioural coverage the PHP tests give the other findings. They are here
 * because the alternative for the highest-consequence JS path in the plugin
 * was nothing at all. Treat a failure as real; do not read a pass as proof the
 * browser behaviour is correct.
 */
final class BlindSaveGuardTest extends Kukie_Test_Case {

	private function adminJs(): string {
		$path = KUKIE_PLUGIN_DIR . 'assets/js/admin.js';
		$this->assertFileExists( $path );

		return (string) file_get_contents( $path );
	}

	public function test_saving_is_refused_while_no_settings_load_has_succeeded(): void {
		$js = $this->adminJs();

		$this->assertMatchesRegularExpression(
			'/if\s*\(\s*currentConfigVersion\s*===\s*null\s*\)/',
			$js,
			'Without this guard a blind save posts template defaults with no optimistic lock to catch it.'
		);
	}

	public function test_every_save_path_routes_through_the_guarded_wrapper(): void {
		$js = $this->adminJs();

		// The guard lives in kukieSaveSettings(). A page calling kukieAjax()
		// directly with a save action would bypass it entirely, which is what
		// makes this the structural half of the fix rather than a detail.
		$this->assertDoesNotMatchRegularExpression(
			"/kukieAjax\(\s*'kukie_save_/",
			$js,
			'A save action reaching kukieAjax() directly skips the blind-save and conflict handling.'
		);

		// [a-z0-9_]: kukie_save_a11y carries digits (the pre-1.8.0 class
		// silently dropped it from the guarded set).
		preg_match_all( "/kukieSaveSettings\(\s*'(kukie_save_[a-z0-9_]+)'/", $js, $matches );

		$guarded = array_unique( $matches[1] );
		sort( $guarded );

		// Five since 1.8.0: the Accessibility widget page saves through the
		// same wrapper (its whole block is server-owned, so a blind save
		// there would post template defaults over the dashboard's values).
		$this->assertSame(
			[ 'kukie_save_a11y', 'kukie_save_banner_design', 'kukie_save_gcm', 'kukie_save_settings', 'kukie_save_uet' ],
			$guarded,
			'All five save handlers must go through the guarded wrapper.'
		);
	}

	public function test_the_settings_form_is_withheld_when_its_load_fails(): void {
		$js = $this->adminJs();

		$this->assertMatchesRegularExpression(
			"/if\s*\(\s*!result\.success\s*\)\s*\{[^}]*form\.style\.display\s*=\s*'none'/s",
			$js,
			'A revealed but unpopulated form is the thing the user clicks Save on.'
		);
	}

	public function test_the_php_handler_accepts_the_optimistic_lock_version(): void {
		// The JS guard is the first line; the version it sends is what lets the
		// server refuse a stale write. 0 is a real version, so the handler must
		// distinguish "absent" from "zero".
		$posted = new ReflectionMethod( Kukie_Admin::class, 'posted_config_version' );
		$posted->setAccessible( true );
		$admin = new Kukie_Admin( Kukie_Plugin::instance() );

		$_POST = [];
		$this->assertNull( $posted->invoke( $admin ), 'Absent must be null so the PUT omits the key entirely.' );

		$_POST = [ 'config_version' => '0' ];
		$this->assertSame( 0, $posted->invoke( $admin ), 'Zero is lockable and must not be dropped as falsy.' );

		$_POST = [ 'config_version' => '12' ];
		$this->assertSame( 12, $posted->invoke( $admin ) );
	}
}
