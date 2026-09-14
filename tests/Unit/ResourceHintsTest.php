<?php
/**
 * Connection hints for the shop's origins.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Tests\Unit;

use Brain\Monkey\Functions;
use Spreadshop\Embed\ResourceHints;
use Spreadshop\Embed\SlugRoute;

/**
 * Covers ResourceHints::filter().
 *
 * Getting this wrong is cheap in one direction and wasteful in the other: a missing hint
 * gives up the head start, an unnecessary one opens a connection nothing uses. So the cases
 * below are mostly about when NOT to emit them.
 */
class ResourceHintsTest extends PluginTestCase {

	/**
	 * Sets up a page that will or will not carry the shop.
	 *
	 * @param bool   $connected Whether a shop is linked.
	 * @param bool   $ownsRoute Whether the slug route claimed this request.
	 * @param bool   $singular  Whether this is a single post or page.
	 * @param string $content   The post content.
	 * @return void
	 */
	private function given( $connected, $ownsRoute, $singular = false, $content = '' ) {
		Functions\when( 'get_option' )->alias(
			static function ( $name, $fallback = false ) use ( $connected ) {
				$values = array(
					'spreadshopID'       => $connected ? '1376884' : '',
					'spreadshopPlatform' => 'EU',
					'spreadshopSlug'     => '',
				);
				return array_key_exists( $name, $values ) ? $values[ $name ] : $fallback;
			}
		);
		Functions\when( 'is_singular' )->justReturn( $singular );
		Functions\when( 'has_shortcode' )->alias(
			static fn( $haystack, $tag ) => false !== strpos( (string) $haystack, '[' . $tag )
		);

		if ( $singular ) {
			$post               = new \WP_Post();
			$post->post_content = $content;
			Functions\when( 'get_post' )->justReturn( $post );
		} else {
			Functions\when( 'get_post' )->justReturn( null );
		}

		$owns = new \ReflectionProperty( SlugRoute::class, 'ownsRequest' );
		$owns->setAccessible( true );
		$owns->setValue( null, $ownsRoute );
	}

	/**
	 * A page carrying the short code gets the shop origins announced early.
	 *
	 * @return void
	 */
	public function testAPageWithTheShortcodeGetsPreconnects() {
		$this->given( true, false, true, 'Welcome [spreadshop] enjoy' );

		$hints = ResourceHints::filter( array(), 'preconnect' );

		$this->assertContains( 'https://1376884.myspreadshop.net', $hints );
		$this->assertContains( ResourceHints::IMAGE_HOST, $hints );
	}

	/**
	 * The slug route's own pages get them too, where there is no short code to find.
	 *
	 * @return void
	 */
	public function testTheSlugRoutePageGetsPreconnects() {
		$this->given( true, true );

		$this->assertContains( 'https://1376884.myspreadshop.net', ResourceHints::filter( array(), 'preconnect' ) );
	}

	/**
	 * The consent host is only worth a name lookup, not a held connection.
	 *
	 * @return void
	 */
	public function testTheConsentHostIsOnlyPrefetched() {
		$this->given( true, true );

		$this->assertContains( ResourceHints::CONSENT_HOST, ResourceHints::filter( array(), 'dns-prefetch' ) );
		$this->assertNotContains( ResourceHints::CONSENT_HOST, ResourceHints::filter( array(), 'preconnect' ) );
	}

	/**
	 * Pages that will not show a shop are left alone.
	 *
	 * @dataProvider noShopProvider
	 * @param bool   $connected Whether a shop is linked.
	 * @param bool   $ownsRoute Whether the slug route claimed this request.
	 * @param bool   $singular  Whether this is a single post or page.
	 * @param string $content   The post content.
	 * @return void
	 */
	public function testPagesWithoutAShopGetNoHints( $connected, $ownsRoute, $singular, $content ) {
		$this->given( $connected, $ownsRoute, $singular, $content );

		$this->assertSame( array(), ResourceHints::filter( array(), 'preconnect' ) );
		$this->assertSame( array(), ResourceHints::filter( array(), 'dns-prefetch' ) );
	}

	/**
	 * Situations where announcing the origins would be wasted.
	 *
	 * @return array<string, array{bool, bool, bool, string}>
	 */
	public function noShopProvider() {
		return array(
			'no shop connected'        => array( false, false, true, '[spreadshop]' ),
			'an archive listing'       => array( true, false, false, '' ),
			'a page without the code'  => array( true, false, true, 'Just some words.' ),
			'the word without the tag' => array( true, false, true, 'I love my spreadshop.' ),
		);
	}

	/**
	 * Hints another plugin registered are preserved, not replaced.
	 *
	 * @return void
	 */
	public function testExistingHintsSurvive() {
		$this->given( true, true );

		$hints = ResourceHints::filter( array( 'https://fonts.example' ), 'preconnect' );

		$this->assertContains( 'https://fonts.example', $hints );
	}
}
