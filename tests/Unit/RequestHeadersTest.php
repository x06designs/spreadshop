<?php
/**
 * The headers Spreadshirt's edge insists on.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Tests\Unit;

use Brain\Monkey\Functions;
use ReflectionMethod;
use Spreadshop\Admin\ConnectTab;

/**
 * Covers the two headers that decide whether setup works at all.
 *
 * Spreadshirt answers 403 to anything that does not look like a conventional web client.
 * Both values below are load-bearing; weakening either brings the "Could not reach
 * Spreadshirt" bug straight back, and no amount of integration testing would localise it.
 */
class RequestHeadersTest extends PluginTestCase {

	/**
	 * Calls one of the private header builders.
	 *
	 * @param string $name Method name.
	 * @return string
	 */
	private function callBuilder( $name ) {
		$method = new ReflectionMethod( ConnectTab::class, $name );
		$method->setAccessible( true );
		return $method->invoke( null );
	}

	/**
	 * The site locale drives Accept-Language.
	 *
	 * @dataProvider localeProvider
	 * @param string $locale   What get_locale() returns.
	 * @param string $expected The header value.
	 * @return void
	 */
	public function testAcceptLanguageIsDerivedFromTheSiteLocale( $locale, $expected ) {
		Functions\when( 'get_locale' )->justReturn( $locale );

		$this->assertSame( $expected, $this->callBuilder( 'acceptLanguage' ) );
	}

	/**
	 * Locales and the header each should produce.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function localeProvider() {
		return array(
			'regional locale'         => array( 'de_DE', 'de-DE,de;q=0.9,en;q=0.8' ),
			'english regional locale' => array( 'en_US', 'en-US,en;q=0.9' ),
			'bare language'           => array( 'de', 'de,en;q=0.8' ),
			'bare english'            => array( 'en', 'en' ),
		);
	}

	/**
	 * Accept-Language must never be empty; its absence is what triggers the 403.
	 *
	 * @return void
	 */
	public function testAcceptLanguageIsNeverEmpty() {
		Functions\when( 'get_locale' )->justReturn( '' );

		$this->assertNotSame( '', $this->callBuilder( 'acceptLanguage' ) );
	}

	/**
	 * The agent must be in the parenthesised comment form a browser uses.
	 *
	 * A bare product token such as "SpreadshopWP/1.7.0" is refused with 403, which is why
	 * WordPress's own "WordPress/6.x; https://site" default never worked.
	 *
	 * @return void
	 */
	public function testUserAgentUsesTheBrowserCommentForm() {
		Functions\when( 'home_url' )->justReturn( 'https://example.test/' );

		$agent = $this->callBuilder( 'userAgent' );

		$this->assertStringStartsWith( 'Mozilla/5.0 (', $agent );
		$this->assertMatchesRegularExpression( '/^Mozilla\/5\.0 \(compatible; [^)]+\)$/', $agent );
	}

	/**
	 * The agent identifies the plugin and the site honestly rather than mimicking a browser.
	 *
	 * @return void
	 */
	public function testUserAgentIdentifiesThePluginAndSite() {
		Functions\when( 'home_url' )->justReturn( 'https://example.test/' );

		$agent = $this->callBuilder( 'userAgent' );

		$this->assertStringContainsString( 'SpreadshopWP/', $agent );
		$this->assertStringContainsString( 'https://example.test/', $agent );
		$this->assertStringNotContainsString( 'Chrome', $agent );
		$this->assertStringNotContainsString( 'Safari', $agent );
	}
}
