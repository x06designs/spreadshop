<?php
/**
 * The configuration object handed to Spreadshirt's shop client.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Tests\Unit;

use Brain\Monkey\Functions;
use Spreadshop\Embed\Renderer;

/**
 * Covers Renderer::render().
 *
 * The markup itself is not asserted -- it is glue around a third-party widget and snapshotting
 * it would only ever catch itself changing. What matters is the config object: every setting
 * has to arrive in the shape the shop client expects, because a wrong type there fails
 * silently at the far end.
 */
class EmbedConfigTest extends PluginTestCase {

	/**
	 * Stubs the options the renderer reads.
	 *
	 * @param array<string, mixed> $options Option overrides.
	 * @return void
	 */
	private function givenOptions( array $options = array() ) {
		$defaults = array(
			'spreadshopID'        => '1376884',
			'spreadshopPlatform'  => 'EU',
			'spreadshopLocale'    => 'de_DE',
			'spreadshopToken'     => '',
			'spreadshopMetadata'  => 0,
			'spreadshopSwipeMenu' => 0,
			'spreadshopLoadFonts' => 0,
		);
		$values   = array_merge( $defaults, $options );

		Functions\when( 'get_option' )->alias(
			static function ( $name, $fallback = false ) use ( $values ) {
				return array_key_exists( $name, $values ) ? $values[ $name ] : $fallback;
			}
		);
		Functions\when( 'wp_json_encode' )->alias( static fn( $d ) => json_encode( $d ) );
	}

	/**
	 * Pulls the config object back out of the rendered markup.
	 *
	 * @param string|null $pushStateBaseUrl   Push state base url.
	 * @param string|null $startTokenOverride Deeplink override.
	 * @return array<string, mixed>
	 */
	private function config( $pushStateBaseUrl = null, $startTokenOverride = null ) {
		$html = Renderer::render( $pushStateBaseUrl, $startTokenOverride );
		preg_match( '/var spread_shop_config = (\{.*?\});/', $html, $m );
		return json_decode( $m[1], true );
	}

	/**
	 * The European platform is served from myspreadshop.net.
	 *
	 * @return void
	 */
	public function testEuropeanShopsUseTheNetDomain() {
		$this->givenOptions( array( 'spreadshopPlatform' => 'EU' ) );

		$this->assertSame( 'https://1376884.myspreadshop.net', $this->config()['prefix'] );
	}

	/**
	 * Anything that is not EU is treated as the North American platform.
	 *
	 * @return void
	 */
	public function testNorthAmericanShopsUseTheComDomain() {
		$this->givenOptions( array( 'spreadshopPlatform' => 'NA' ) );

		$this->assertSame( 'https://1376884.myspreadshop.com', $this->config()['prefix'] );
	}

	/**
	 * The toggles reach the client as real booleans, not the strings options store.
	 *
	 * @return void
	 */
	public function testTogglesAreBooleans() {
		$this->givenOptions(
			array(
				'spreadshopMetadata'  => '1',
				'spreadshopSwipeMenu' => '1',
				'spreadshopLoadFonts' => '1',
			)
		);

		$config = $this->config();

		$this->assertTrue( $config['updateMetadata'] );
		$this->assertTrue( $config['swipeMenu'] );
		$this->assertTrue( $config['loadFonts'] );
	}

	/**
	 * An unset toggle is false rather than absent or an empty string.
	 *
	 * @return void
	 */
	public function testUnsetTogglesAreFalse() {
		$this->givenOptions();

		$config = $this->config();

		$this->assertFalse( $config['updateMetadata'] );
		$this->assertFalse( $config['swipeMenu'] );
		$this->assertFalse( $config['loadFonts'] );
	}

	/**
	 * Push state is off, and its base url absent, unless one is passed in.
	 *
	 * @return void
	 */
	public function testPushStateIsOffWithoutABaseUrl() {
		$this->givenOptions();

		$config = $this->config( null );

		$this->assertFalse( $config['usePushState'] );
		$this->assertNull( $config['pushStateBaseUrl'] );
	}

	/**
	 * Passing a base url turns push state on.
	 *
	 * @return void
	 */
	public function testPushStateFollowsTheBaseUrl() {
		$this->givenOptions();

		$config = $this->config( 'https://example.test/meinshop' );

		$this->assertTrue( $config['usePushState'] );
		$this->assertSame( 'https://example.test/meinshop', $config['pushStateBaseUrl'] );
	}

	/**
	 * A shortcode deeplink wins over the configured start token.
	 *
	 * @return void
	 */
	public function testADeeplinkOverridesTheConfiguredStartToken() {
		$this->givenOptions( array( 'spreadshopToken' => 'configured-token' ) );

		$this->assertSame( 'from-shortcode', $this->config( null, 'from-shortcode' )['startToken'] );
	}

	/**
	 * Without an override the configured start token is used.
	 *
	 * @return void
	 */
	public function testTheConfiguredStartTokenIsUsedOtherwise() {
		$this->givenOptions( array( 'spreadshopToken' => 'configured-token' ) );

		$this->assertSame( 'configured-token', $this->config()['startToken'] );
	}

	/**
	 * Percent-encoded deeplinks survive into the config.
	 *
	 * @return void
	 */
	public function testEncodedDeeplinksSurvive() {
		$this->givenOptions();

		$this->assertSame( 'design%20name?idea=abc', $this->config( null, 'design%20name?idea=abc' )['startToken'] );
	}

	/**
	 * The version reported to Spreadshirt tracks the plugin's own constant.
	 *
	 * @return void
	 */
	public function testTheReportedVersionTracksTheConstant() {
		$this->givenOptions();

		$this->assertStringContainsString(
			\Spreadshop\Constants::SPREADSHOP_VERSION,
			$this->config()['integrationProvider']
		);
	}

	/**
	 * The shop client script is loaded from the shop's own domain.
	 *
	 * @return void
	 */
	public function testTheClientScriptComesFromTheShopDomain() {
		$this->givenOptions();

		$html = Renderer::render( null, null );

		$this->assertStringContainsString(
			'https://1376884.myspreadshop.net/shopfiles/shopclient/shopclient.nocache.js',
			$html
		);
	}
}
