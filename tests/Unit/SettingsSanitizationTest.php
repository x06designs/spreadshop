<?php
/**
 * Cleaning options however they were written.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Tests\Unit;

use Brain\Monkey\Functions;
use ReflectionMethod;
use Spreadshop\Settings;

/**
 * Covers the sanitisers registered with the settings API.
 *
 * The plugin's own forms validate before writing, but registering an option also whitelists it
 * for core's options.php handler, which never passes through those forms. A shop id is the
 * one that matters: it becomes the subdomain the shop is fetched from, so a value carrying a
 * host would repoint both the lookup and the embedded client.
 */
class SettingsSanitizationTest extends PluginTestCase {

	/**
	 * A numeric shop id survives untouched.
	 *
	 * @return void
	 */
	public function testAValidShopIdIsKept() {
		$this->assertSame( '1376884', Settings::sanitizeShopId( '1376884' ) );
	}

	/**
	 * Anything that is not digits is discarded rather than stored.
	 *
	 * @dataProvider badShopIdProvider
	 * @param mixed $value What was submitted.
	 * @return void
	 */
	public function testAShopIdThatCouldCarryAHostIsRejected( $value ) {
		$this->assertSame( '', Settings::sanitizeShopId( $value ) );
	}

	/**
	 * Values that must never reach Platform::shopOrigin().
	 *
	 * @return array<string, array{mixed}>
	 */
	public function badShopIdProvider() {
		return array(
			'a hostname'        => array( 'evil.example.com' ),
			'host with a path'  => array( 'evil.example.com/x?' ),
			'an at-sign'        => array( 'evil@example.com' ),
			'a scheme'          => array( 'https://evil.example' ),
			'digits then a dot' => array( '1376884.evil.example' ),
			'empty'             => array( '' ),
			'an array'          => array( array( '1376884' ) ),
			'a boolean'         => array( false ),
		);
	}

	/**
	 * Only the two platforms survive.
	 *
	 * @return void
	 */
	public function testOnlyAKnownPlatformIsKept() {
		$this->assertSame( 'EU', Settings::sanitizePlatform( 'EU' ) );
		$this->assertSame( 'NA', Settings::sanitizePlatform( 'NA' ) );
		$this->assertSame( '', Settings::sanitizePlatform( 'eu' ) );
		$this->assertSame( '', Settings::sanitizePlatform( 'evil.example' ) );
	}

	/**
	 * A locale has to look like one.
	 *
	 * @return void
	 */
	public function testOnlyAWellFormedLocaleIsKept() {
		$this->assertSame( 'de_DE', Settings::sanitizeLocale( 'de_DE' ) );
		$this->assertSame( '', Settings::sanitizeLocale( 'de-DE' ) );
		$this->assertSame( '', Settings::sanitizeLocale( '../../etc/passwd' ) );
	}

	/**
	 * Url fragments keep their percent-encoding but lose markup.
	 *
	 * @return void
	 */
	public function testUrlFragmentsKeepEncodingAndLoseMarkup() {
		Functions\when( 'wp_strip_all_tags' )->alias(
			static function ( $text ) {
				$withoutBodies = preg_replace( '@<(script|style)[^>]*?>.*?</\1>@si', '', (string) $text );

				return trim( strip_tags( $withoutBodies ) );
			}
		);

		$this->assertSame( 'design%20name?idea=abc', Settings::sanitizeUrlFragment( 'design%20name?idea=abc' ) );
		$this->assertSame( 'ok', Settings::sanitizeUrlFragment( '<script>bad()</script>ok' ) );
	}

	/**
	 * A posted array cannot reach strip_tags() and raise a TypeError.
	 *
	 * @return void
	 */
	public function testAnArrayFragmentBecomesAnEmptyString() {
		$this->assertSame( '', Settings::sanitizeUrlFragment( array( 'x' ) ) );
	}

	/**
	 * Toggles are stored as 1 or 0, never as what was posted.
	 *
	 * @return void
	 */
	public function testTogglesAreStoredAsOneOrZero() {
		$this->assertSame( 1, Settings::sanitizeToggle( '1' ) );
		$this->assertSame( 1, Settings::sanitizeToggle( 'yes' ) );
		$this->assertSame( 0, Settings::sanitizeToggle( '0' ) );
		$this->assertSame( 0, Settings::sanitizeToggle( '' ) );
		$this->assertSame( 0, Settings::sanitizeToggle( null ) );
	}

	/**
	 * Every registered option carries a sanitiser; none is registered bare.
	 *
	 * @return void
	 */
	public function testEveryOptionIsRegisteredWithASanitizer() {
		$method = new ReflectionMethod( Settings::class, 'sanitizers' );
		$method->setAccessible( true );
		$sanitizers = $method->invoke( null );

		$this->assertSame(
			array_merge( \Spreadshop\Constants::SPREADSHOP_OPTIONS, \Spreadshop\Constants::SPREADSHOP_LAYOUT_OPTIONS ),
			array_keys( $sanitizers ),
			'an option without a sanitiser can be written raw through options.php'
		);

		foreach ( $sanitizers as $option => $callback ) {
			$this->assertIsCallable( $callback, $option . ' has no usable sanitiser' );
		}
	}
}
