<?php
/**
 * What the admin is told when a lookup fails.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Tests\Unit;

use ReflectionMethod;
use Spreadshop\Admin\ConnectTab;

/**
 * Covers describeFailure().
 *
 * The original plugin reported every failure as "Could not reach Spreadshirt", which is how a
 * plain 403 looked like a network outage for however long it took someone to packet-trace it.
 */
class FailureMessageTest extends PluginTestCase {

	/**
	 * A lookup that succeeded.
	 *
	 * @var array<string, mixed>
	 */
	private $ok = array(
		'status' => 200,
		'shopId' => 1,
	);

	/**
	 * Calls the private message builder.
	 *
	 * @param array<string, mixed> $eu European platform result.
	 * @param array<string, mixed> $na North American platform result.
	 * @return string
	 */
	private function describe( $eu, $na ) {
		$method = new ReflectionMethod( ConnectTab::class, 'describeFailure' );
		$method->setAccessible( true );
		return $method->invoke( null, $eu, $na );
	}

	/**
	 * A 403 names the cause rather than blaming the network.
	 *
	 * @return void
	 */
	public function testForbiddenNamesTheTlsCause() {
		$message = $this->describe(
			array(
				'status' => 403,
				'shopId' => null,
			),
			$this->ok
		);

		$this->assertStringContainsString( '403', $message );
		$this->assertStringContainsString( 'TLS', $message );
		$this->assertStringContainsString( '8.1', $message );
	}

	/**
	 * The regression that started all of this: a 403 must not read as unreachable.
	 *
	 * @return void
	 */
	public function testForbiddenIsNotReportedAsUnreachable() {
		$message = $this->describe(
			array(
				'status' => 403,
				'shopId' => null,
			),
			$this->ok
		);

		$this->assertStringNotContainsStringIgnoringCase( 'could not reach', $message );
	}

	/**
	 * A failure on either platform is reported, not just the first.
	 *
	 * @return void
	 */
	public function testAFailureOnTheSecondPlatformIsAlsoReported() {
		$message = $this->describe(
			$this->ok,
			array(
				'status' => 403,
				'shopId' => null,
			)
		);

		$this->assertStringContainsString( '403', $message );
	}

	/**
	 * A transport error surfaces whatever WordPress said went wrong.
	 *
	 * @return void
	 */
	public function testTransportErrorsSurfaceTheUnderlyingMessage() {
		$message = $this->describe(
			array(
				'status'      => -1,
				'shopId'      => null,
				'errorDetail' => 'cURL error 28: timed out',
			),
			$this->ok
		);

		$this->assertStringContainsString( 'cURL error 28: timed out', $message );
	}

	/**
	 * Any other status is reported with its actual code.
	 *
	 * @return void
	 */
	public function testOtherStatusesAreReportedVerbatim() {
		$message = $this->describe(
			array(
				'status' => 500,
				'shopId' => null,
			),
			array(
				'status' => 404,
				'shopId' => null,
			)
		);

		$this->assertStringContainsString( '500', $message );
	}

	/**
	 * A 404 is a clean answer, not a failure, so it never reaches this message.
	 *
	 * @return void
	 */
	public function testNotFoundOnBothPlatformsIsNotTreatedAsAFailure() {
		$message = $this->describe(
			array(
				'status' => 404,
				'shopId' => null,
			),
			array(
				'status' => 404,
				'shopId' => null,
			)
		);

		$this->assertStringNotContainsString( '404', $message );
	}
}
