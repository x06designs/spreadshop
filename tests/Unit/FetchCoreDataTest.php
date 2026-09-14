<?php
/**
 * How a shop lookup handles what Spreadshirt sends back.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Tests\Unit;

use Brain\Monkey\Functions;
use ReflectionMethod;
use Spreadshop\Admin\ConnectTab;
use Spreadshop\Tests\Doubles\FakeWpError;

/**
 * Covers fetchCoreData().
 *
 * The HTTP call is stubbed. What is under test is everything around it: which url each
 * platform gets, how a WP_Error is turned into a reportable failure, and whether a payload
 * that is not shaped as expected is caught instead of warning its way through.
 */
class FetchCoreDataTest extends PluginTestCase {

	/**
	 * A realistic core-data payload.
	 *
	 * @param bool $international Whether the shop serves more than one locale.
	 * @return string JSON body.
	 */
	private function payload( $international = false ) {
		return (string) wp_json_encode(
			array(
				'shopData'  => array(
					'shopId'      => 1376884,
					'shopUrlName' => 'Stechmuecke',
					'baseLocale'  => 'de_DE',
				),
				'shopProps' => array( 'international' => $international ),
				'locale'    => array( 'id' => 'de_DE' ),
			)
		);
	}

	/**
	 * Stubs the HTTP layer with one canned response.
	 *
	 * @param mixed       $response What wp_remote_get returns.
	 * @param int|null    $status   Status code to report, or null when the response is an error.
	 * @param string|null $body     Body to report.
	 * @return void
	 */
	private function httpReturns( $response, $status = null, $body = null ) {
		Functions\when( 'get_locale' )->justReturn( 'de_DE' );
		Functions\when( 'home_url' )->justReturn( 'https://example.test/' );
		Functions\when( 'wp_remote_get' )->justReturn( $response );
		Functions\when( 'is_wp_error' )->justReturn( null === $status );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( $status );
		Functions\when( 'wp_remote_retrieve_body' )->justReturn( $body );
	}

	/**
	 * Calls the private lookup.
	 *
	 * @param string $shopIdOrName Shop name or id.
	 * @param string $platform     'EU' or 'NA'.
	 * @return array<string, mixed>
	 */
	private function fetch( $shopIdOrName, $platform ) {
		$method = new ReflectionMethod( ConnectTab::class, 'fetchCoreData' );
		$method->setAccessible( true );
		return $method->invoke( null, $shopIdOrName, $platform );
	}

	/**
	 * A good response is parsed into the fields the confirmation screen shows.
	 *
	 * @return void
	 */
	public function testParsesAGoodResponse() {
		$this->httpReturns( array( 'ok' ), 200, $this->payload() );

		$result = $this->fetch( 'stechmuecke', 'EU' );

		$this->assertSame( 200, $result['status'] );
		$this->assertSame( 1376884, $result['shopId'] );
		$this->assertSame( 'Stechmuecke', $result['shopName'] );
		$this->assertSame( 'de_DE', $result['baseLocale'] );
	}

	/**
	 * A shop tied to one locale offers exactly that locale.
	 *
	 * @return void
	 */
	public function testASingleLocaleShopOffersOnlyItsOwnLocale() {
		$this->httpReturns( array( 'ok' ), 200, $this->payload( false ) );

		$result = $this->fetch( 'stechmuecke', 'EU' );

		$this->assertSame( array( 'de_DE' => 'de_DE' ), $result['locales'] );
	}

	/**
	 * An international shop offers the whole platform list to choose from.
	 *
	 * @return void
	 */
	public function testAnInternationalShopOffersThePlatformLocales() {
		$this->httpReturns( array( 'ok' ), 200, $this->payload( true ) );

		$result = $this->fetch( 'stechmuecke', 'EU' );

		$this->assertGreaterThan( 1, count( $result['locales'] ) );
		$this->assertContains( 'de_DE', $result['locales'] );
	}

	/**
	 * A shop missing from a platform is a clean answer, not an error.
	 *
	 * @return void
	 */
	public function testNotFoundIsReportedAsSuch() {
		$this->httpReturns( array( 'ok' ), 404, '' );

		$result = $this->fetch( 'stechmuecke', 'NA' );

		$this->assertSame( 404, $result['status'] );
		$this->assertNull( $result['shopId'] );
		$this->assertArrayNotHasKey( 'errorDetail', $result );
	}

	/**
	 * A transport failure carries the reason with it.
	 *
	 * @return void
	 */
	public function testTransportFailureIsCapturedWithItsMessage() {
		$error = new FakeWpError( 'cURL error 28: Operation timed out' );
		$this->httpReturns( $error, null, null );

		$result = $this->fetch( 'stechmuecke', 'EU' );

		$this->assertSame( -1, $result['status'] );
		$this->assertNull( $result['shopId'] );
		$this->assertStringContainsString( 'timed out', $result['errorDetail'] );
	}

	/**
	 * A 200 carrying something other than the expected shape is caught, not walked into.
	 *
	 * @dataProvider malformedBodyProvider
	 * @param string $body What the server sent.
	 * @return void
	 */
	public function testMalformedPayloadsAreRejected( $body ) {
		$this->httpReturns( array( 'ok' ), 200, $body );

		$result = $this->fetch( 'stechmuecke', 'EU' );

		$this->assertSame( -1, $result['status'] );
		$this->assertNull( $result['shopId'] );
		$this->assertArrayHasKey( 'errorDetail', $result );
	}

	/**
	 * Bodies a 200 might carry that the plugin cannot use.
	 *
	 * @return array<string, array{string}>
	 */
	public function malformedBodyProvider() {
		return array(
			'empty'               => array( '' ),
			'html error page'     => array( '<!DOCTYPE html><html><body>maintenance</body></html>' ),
			'json but not a shop' => array( '{"error":"Forbidden","status":403}' ),
			'json null'           => array( 'null' ),
			'shopData without id' => array( '{"shopData":{"shopUrlName":"x"}}' ),
		);
	}

	/**
	 * A one-off empty body is retried rather than reported as a broken shop.
	 *
	 * Spreadshirt occasionally answers 200 with nothing in it; the next request succeeds.
	 *
	 * @return void
	 */
	public function testATransientEmptyBodyIsRetried() {
		Functions\when( 'get_locale' )->justReturn( 'de_DE' );
		Functions\when( 'home_url' )->justReturn( 'https://example.test/' );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_get' )->justReturn( array( 'ok' ) );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 200 );

		$bodies = array( '', $this->payload() );
		Functions\when( 'wp_remote_retrieve_body' )->alias(
			static function () use ( &$bodies ) {
				return array_shift( $bodies );
			}
		);

		$result = $this->fetch( 'stechmuecke', 'EU' );

		$this->assertSame( 200, $result['status'] );
		$this->assertSame( 1376884, $result['shopId'] );
	}

	/**
	 * A body that is persistently unusable is reported, not retried forever.
	 *
	 * @return void
	 */
	public function testAPersistentlyBadBodyGivesUp() {
		$calls = 0;
		Functions\when( 'get_locale' )->justReturn( 'de_DE' );
		Functions\when( 'home_url' )->justReturn( 'https://example.test/' );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 200 );
		Functions\when( 'wp_remote_retrieve_body' )->justReturn( '' );
		Functions\when( 'wp_remote_get' )->alias(
			static function () use ( &$calls ) {
				++$calls;
				return array( 'ok' );
			}
		);

		$result = $this->fetch( 'stechmuecke', 'EU' );

		$this->assertSame( -1, $result['status'] );
		$this->assertSame( ConnectTab::MAX_LOOKUP_ATTEMPTS, $calls, 'should stop at the attempt cap' );
	}

	/**
	 * Each platform is asked on its own domain.
	 *
	 * @dataProvider platformProvider
	 * @param string $platform Platform code.
	 * @param string $expected The tld it must resolve to.
	 * @return void
	 */
	public function testPlatformDecidesTheDomain( $platform, $expected ) {
		Functions\when( 'get_locale' )->justReturn( 'de_DE' );
		Functions\when( 'home_url' )->justReturn( 'https://example.test/' );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 404 );
		Functions\when( 'wp_remote_retrieve_body' )->justReturn( '' );

		$seen = null;
		Functions\when( 'wp_remote_get' )->alias(
			static function ( $url ) use ( &$seen ) {
				$seen = $url;
				return array();
			}
		);

		$this->fetch( 'stechmuecke', $platform );

		$this->assertStringContainsString( 'stechmuecke.myspreadshop.' . $expected . '/', $seen );
	}

	/**
	 * Platform to tld.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function platformProvider() {
		return array(
			'europe'        => array( 'EU', 'net' ),
			'north america' => array( 'NA', 'com' ),
		);
	}

	/**
	 * The request carries a timeout; the network is not assumed to answer.
	 *
	 * @return void
	 */
	public function testTheRequestIsGivenATimeout() {
		Functions\when( 'get_locale' )->justReturn( 'de_DE' );
		Functions\when( 'home_url' )->justReturn( 'https://example.test/' );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 404 );
		Functions\when( 'wp_remote_retrieve_body' )->justReturn( '' );

		$args = null;
		Functions\when( 'wp_remote_get' )->alias(
			static function ( $url, $passed ) use ( &$args ) {
				$args = $passed;
				return array();
			}
		);

		$this->fetch( 'stechmuecke', 'EU' );

		$this->assertArrayHasKey( 'timeout', $args );
		$this->assertGreaterThan( 0, $args['timeout'] );
		$this->assertArrayHasKey( 'Accept-Language', $args['headers'] );
		$this->assertNotEmpty( $args['user-agent'] );
	}
}
