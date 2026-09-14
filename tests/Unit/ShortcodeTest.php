<?php
/**
 * The [spreadshop] short code.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Tests\Unit;

use Brain\Monkey\Functions;
use ReflectionProperty;
use Spreadshop\Embed\Shortcode;

/**
 * Covers Shortcode::render().
 *
 * The guard that stops a second short code rendering was dead code in the original: the flag
 * was set after the return, so it never took effect and a page with two tags emitted two
 * competing shop configurations.
 */
class ShortcodeTest extends PluginTestCase {

	/**
	 * Clears the once-per-request flag between tests.
	 *
	 * @return void
	 */
	protected function set_up() {
		parent::set_up();

		$flag = new ReflectionProperty( Shortcode::class, 'alreadyRun' );
		$flag->setAccessible( true );
		$flag->setValue( null, false );

		Functions\when( 'get_option' )->alias(
			static function ( $name, $fallback = false ) {
				$values = array(
					'spreadshopID'       => '1376884',
					'spreadshopPlatform' => 'EU',
					'spreadshopLocale'   => 'de_DE',
				);
				return array_key_exists( $name, $values ) ? $values[ $name ] : $fallback;
			}
		);
		Functions\when( 'wp_json_encode' )->alias( static fn( $d ) => json_encode( $d ) );
	}

	/**
	 * The first short code on a page renders the shop.
	 *
	 * @return void
	 */
	public function testTheFirstShortcodeRenders() {
		$this->assertStringContainsString( 'spread_shop_config', Shortcode::render( array() ) );
	}

	/**
	 * A second short code renders nothing at all.
	 *
	 * @return void
	 */
	public function testASecondShortcodeRendersNothing() {
		Shortcode::render( array() );

		$this->assertSame( '', Shortcode::render( array() ) );
	}

	/**
	 * Three tags still produce exactly one embed between them.
	 *
	 * @return void
	 */
	public function testOnlyOneEmbedIsEverProduced() {
		$combined = Shortcode::render( array() ) . Shortcode::render( array() ) . Shortcode::render( array() );

		$this->assertSame( 1, substr_count( $combined, 'spread_shop_config' ) );
		$this->assertSame( 1, substr_count( $combined, 'id="myShop"' ) );
	}

	/**
	 * The deeplink attribute reaches the shop client.
	 *
	 * @return void
	 */
	public function testTheDeeplinkAttributeIsPassedThrough() {
		$html = Shortcode::render( array( 'deeplink' => 'cafe+koenji+logo?idea=abc' ) );

		$this->assertStringContainsString( 'cafe+koenji+logo?idea=abc', $html );
	}

	/**
	 * WordPress passes an empty string, not an array, when a short code has no attributes.
	 *
	 * @return void
	 */
	public function testSurvivesWordPressPassingAnEmptyString() {
		$this->assertStringContainsString( 'spread_shop_config', Shortcode::render( '' ) );
	}
}
