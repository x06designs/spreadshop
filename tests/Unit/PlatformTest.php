<?php
/**
 * Which domain serves which platform.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Tests\Unit;

use Spreadshop\Platform;

/**
 * Covers Platform.
 *
 * This rule used to be written out at both call sites. These cases exist so the two cannot
 * drift apart again without something going red.
 */
class PlatformTest extends PluginTestCase {

	/**
	 * Each platform resolves to its own top level domain.
	 *
	 * @return void
	 */
	public function testEachPlatformHasItsOwnDomain() {
		$this->assertSame( 'net', Platform::tld( Platform::EU ) );
		$this->assertSame( 'com', Platform::tld( Platform::NA ) );
	}

	/**
	 * An unusable platform value still produces a shop url rather than nothing.
	 *
	 * An option that was never written, or that a failed migration corrupted, should leave the
	 * shop pointing somewhere real instead of at "https://shop.myspreadshop./".
	 *
	 * @dataProvider brokenPlatformProvider
	 * @param mixed $platform Whatever the option happened to contain.
	 * @return void
	 */
	public function testABrokenPlatformFallsBackRatherThanBreaking( $platform ) {
		$this->assertSame( 'com', Platform::tld( $platform ) );
	}

	/**
	 * Values an option might hold after a failed write.
	 *
	 * @return array<string, array{mixed}>
	 */
	public function brokenPlatformProvider() {
		return array(
			'never set'  => array( false ),
			'empty'      => array( '' ),
			'lower case' => array( 'eu' ),
			'nonsense'   => array( 'ZZ' ),
		);
	}

	/**
	 * The origin is built from the shop and its platform.
	 *
	 * @return void
	 */
	public function testTheOriginCombinesShopAndPlatform() {
		$this->assertSame( 'https://1376884.myspreadshop.net', Platform::shopOrigin( '1376884', Platform::EU ) );
		$this->assertSame( 'https://example.myspreadshop.com', Platform::shopOrigin( 'example', Platform::NA ) );
	}

	/**
	 * Only the two known platforms validate.
	 *
	 * @return void
	 */
	public function testOnlyKnownPlatformsAreValid() {
		$this->assertTrue( Platform::isValid( 'EU' ) );
		$this->assertTrue( Platform::isValid( 'NA' ) );
		$this->assertFalse( Platform::isValid( 'eu' ) );
		$this->assertFalse( Platform::isValid( '' ) );
		$this->assertFalse( Platform::isValid( 'EU; DROP TABLE' ) );
	}
}
