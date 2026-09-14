<?php
/**
 * Which requests the slug based integration claims.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Tests\Unit;

use Brain\Monkey\Functions;
use Spreadshop\Embed\SlugRoute;

/**
 * Covers SlugRoute::filterTemplate().
 *
 * This is where the worst bug lived: the pushState branch tested a bare string prefix, so a
 * slug of "shop" also swallowed /shopping-cart and every other page beginning with it. These
 * cases exist to keep that from coming back.
 */
class SlugRouteTest extends PluginTestCase {

	/**
	 * Sets up the WordPress functions the route reads.
	 *
	 * @param string $slug        Configured slug.
	 * @param bool   $pushState   Whether pushState urls are on.
	 * @param string $requestUri  The incoming request path.
	 * @param string $homeUrl     Site home url.
	 * @return void
	 */
	private function given( $slug, $pushState, $requestUri, $homeUrl = 'https://example.test' ) {
		Functions\when( 'get_option' )->alias(
			static function ( $name ) use ( $slug, $pushState ) {
				if ( 'spreadshopSlug' === $name ) {
					return $slug;
				}
				if ( 'spreadshopOptimizeUrl' === $name ) {
					return $pushState ? 1 : 0;
				}
				return false;
			}
		);
		Functions\when( 'get_home_url' )->justReturn( $homeUrl );
		Functions\when( 'wp_parse_url' )->alias( static fn( $url ) => parse_url( $url ) );

		$_SERVER['REQUEST_URI'] = $requestUri;
	}

	/**
	 * Whether the route took over this request.
	 *
	 * @return bool
	 */
	private function claimed() {
		$result = SlugRoute::filterTemplate( 'theme/original.php' );
		return 'theme/original.php' !== $result;
	}

	/**
	 * Requests the route must serve.
	 *
	 * @dataProvider matchingProvider
	 * @param string $slug       Configured slug.
	 * @param bool   $pushState  Whether pushState urls are on.
	 * @param string $requestUri Incoming request.
	 * @return void
	 */
	public function testClaimsMatchingRequests( $slug, $pushState, $requestUri ) {
		$this->given( $slug, $pushState, $requestUri );

		$this->assertTrue( $this->claimed(), $requestUri . ' should be served the shop' );
	}

	/**
	 * Requests that belong to the shop.
	 *
	 * @return array<string, array{string, bool, string}>
	 */
	public function matchingProvider() {
		return array(
			'exact path'                   => array( 'meinshop', false, '/meinshop' ),
			'trailing slash'               => array( 'meinshop', false, '/meinshop/' ),
			'query string is ignored'      => array( 'meinshop', false, '/meinshop?utm_source=x' ),
			'hashbang is ignored'          => array( 'meinshop', false, '/meinshop#!/product' ),
			'slug stored with slashes'     => array( '/meinshop/', false, '/meinshop' ),
			'sub path with pushState'      => array( 'meinshop', true, '/meinshop/some-design' ),
			'deep sub path with pushState' => array( 'meinshop', true, '/meinshop/a/b/c' ),
			'percent-encoded slug'         => array( 'caf%C3%A9', false, '/caf%C3%A9' ),
		);
	}

	/**
	 * Requests the route must leave alone.
	 *
	 * @dataProvider nonMatchingProvider
	 * @param string $slug       Configured slug.
	 * @param bool   $pushState  Whether pushState urls are on.
	 * @param string $requestUri Incoming request.
	 * @return void
	 */
	public function testLeavesOtherRequestsAlone( $slug, $pushState, $requestUri ) {
		$this->given( $slug, $pushState, $requestUri );

		$this->assertFalse( $this->claimed(), $requestUri . ' should not be served the shop' );
	}

	/**
	 * Requests that belong to the rest of the site.
	 *
	 * @return array<string, array{string, bool, string}>
	 */
	public function nonMatchingProvider() {
		return array(
			'unrelated page'                 => array( 'meinshop', false, '/impressum' ),
			'sub path without pushState'     => array( 'meinshop', false, '/meinshop/some-design' ),
			// The prefix-swallowing regression, in both the forms it took.
			'longer word starting with slug' => array( 'meinshop', true, '/meinshopXYZ' ),
			'hyphenated sibling'             => array( 'meinshop', true, '/meinshop-gallery' ),
			'sibling of a short slug'        => array( 'shop', true, '/shopping-cart' ),
			'no slug configured'             => array( '', true, '/meinshop' ),
		);
	}

	/**
	 * A site installed in a subdirectory has that prefix in every path.
	 *
	 * @return void
	 */
	public function testRespectsASubdirectoryInstall() {
		$this->given( 'meinshop', false, '/blog/meinshop', 'https://example.test/blog' );
		$this->assertTrue( $this->claimed() );

		$this->given( 'meinshop', false, '/meinshop', 'https://example.test/blog' );
		$this->assertFalse( $this->claimed(), 'the un-prefixed path is not ours' );
	}

	/**
	 * The template it hands back is the one that ships with the plugin.
	 *
	 * @return void
	 */
	public function testServesThePluginTemplate() {
		$this->given( 'meinshop', false, '/meinshop' );

		$this->assertStringEndsWith(
			'templates/embed-page.php',
			str_replace( '\\', '/', SlugRoute::filterTemplate( 'theme/original.php' ) )
		);
	}

	/**
	 * A request with no REQUEST_URI at all must not fatal.
	 *
	 * @return void
	 */
	public function testSurvivesAMissingRequestUri() {
		$this->given( 'meinshop', true, '/meinshop' );
		unset( $_SERVER['REQUEST_URI'] );

		$this->assertFalse( $this->claimed() );
	}

	/**
	 * The push state base url is null unless the setting is on.
	 *
	 * @return void
	 */
	public function testPushStateBaseUrlFollowsTheSetting() {
		$this->given( 'meinshop', false, '/meinshop' );
		$this->assertNull( SlugRoute::pushStateBaseUrl() );

		$this->given( 'meinshop', true, '/meinshop' );
		$this->assertSame( 'https://example.test/meinshop', SlugRoute::pushStateBaseUrl() );
	}
}
