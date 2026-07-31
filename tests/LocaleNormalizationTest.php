<?php

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * KUK-QA-2026-388: pt_BR must not collapse to pt.
 *
 * Kukie seeds pt-br as a locale distinct from European pt
 * (DefaultBannerTranslationsSeeder in the Laravel repo), the plugin offers
 * "Portugues (Brasil)" in the Banner language dropdown, and the whitelist
 * accepts it - so a normaliser that strips the region makes a shipped,
 * selectable option unreachable.
 *
 * The complement matters as much as the fix: everything that is NOT a
 * distinct seeded locale must still strip its region, or every regional
 * variant would miss the translation it should have matched.
 */
final class LocaleNormalizationTest extends Kukie_Test_Case {

	public static function preservedProvider(): array {
		return [
			'WordPress pt_BR'  => [ 'pt_BR', 'pt-br' ],
			'BCP47 pt-BR'      => [ 'pt-BR', 'pt-br' ],
			'already short'    => [ 'pt-br', 'pt-br' ],
			'zh_CN'            => [ 'zh_CN', 'zh-cn' ],
			'zh_TW'            => [ 'zh_TW', 'zh-tw' ],
			'zh_Hans'          => [ 'zh_Hans', 'zh-cn' ],
			'zh_Hant'          => [ 'zh_Hant', 'zh-tw' ],
			'bare zh'          => [ 'zh', 'zh-cn' ],
		];
	}

	#[DataProvider( 'preservedProvider' )]
	public function test_distinct_seeded_locales_keep_their_region( string $input, string $expected ): void {
		$this->assertSame( $expected, Kukie_Language_Detector::normalize( $input ) );
	}

	public static function strippedProvider(): array {
		return [
			'bare pt stays pt' => [ 'pt', 'pt' ],
			'de_DE'            => [ 'de_DE', 'de' ],
			'en-GB'            => [ 'en-GB', 'en' ],
			'fr_CA'            => [ 'fr_CA', 'fr' ],
		];
	}

	#[DataProvider( 'strippedProvider' )]
	public function test_every_other_locale_strips_its_region( string $input, string $expected ): void {
		$this->assertSame( $expected, Kukie_Language_Detector::normalize( $input ) );
	}

	public function test_the_forced_language_override_is_normalised_too(): void {
		// The dropdown stores pt-br; detect() must not re-collapse it.
		kukie_test_seed_settings( [ 'force_language' => 'pt_BR' ] );

		$this->assertSame( 'pt-br', Kukie_Language_Detector::detect() );
	}

	public function test_auto_detect_falls_through_to_the_wordpress_locale(): void {
		kukie_test_seed_settings( [ 'force_language' => 'auto' ] );
		$GLOBALS['kukie_test_locale'] = 'pt_BR';

		$this->assertSame( 'pt-br', Kukie_Language_Detector::detect() );
	}

	public function test_every_dropdown_option_survives_normalisation_unchanged(): void {
		// The whitelist is already in Kukie short-code form, so normalising a
		// value from it must be a no-op. If it is not, that entry is
		// unreachable exactly the way pt-br was.
		$whitelist = new ReflectionMethod( Kukie_Admin::class, 'allowed_force_languages' );
		$whitelist->setAccessible( true );
		$codes = $whitelist->invoke( new Kukie_Admin( Kukie_Plugin::instance() ) );

		foreach ( $codes as $code ) {
			if ( $code === 'auto' ) {
				continue;
			}
			$this->assertSame( $code, Kukie_Language_Detector::normalize( $code ), "Selecting '{$code}' would not produce '{$code}'." );
		}
	}
}
