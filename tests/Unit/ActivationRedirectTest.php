<?php
/**
 * When the post-activation redirect is allowed to fire.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Tests\Unit;

use Brain\Monkey\Functions;
use Spreadshop\Plugin;

/**
 * Covers Plugin::redirectAfterActivation().
 *
 * Only the cases that must NOT redirect are asserted. The happy path ends in exit(), which a
 * unit test cannot survive, and driving it would prove little that the browser run has not.
 * The guards are the part with the bug potential: this hook fires for every plugin activation
 * on the site, not just this one.
 */
class ActivationRedirectTest extends PluginTestCase {

	/**
	 * Asserts the redirect was not attempted for a given set of conditions.
	 *
	 * @param string $fileName Plugin file passed by the hook.
	 * @return void
	 */
	private function assertNoRedirectFor( $fileName ) {
		Functions\when( 'wp_doing_cron' )->justReturn( false );
		Functions\when( 'admin_url' )->returnArg();
		Functions\expect( 'wp_safe_redirect' )->never();

		Plugin::redirectAfterActivation( $fileName );

		// The assertion is the never() expectation above: Brain Monkey throws the moment
		// wp_safe_redirect is called, which also stops the exit() behind it killing the run.
		$this->addToAssertionCount( 1 );
	}

	/**
	 * Another plugin being activated must not drag the admin to our screen.
	 *
	 * @return void
	 */
	public function testAnotherPluginActivatingIsIgnored() {
		$this->assertNoRedirectFor( 'akismet/akismet.php' );
	}

	/**
	 * A plugin whose name merely ends similarly is still not ours.
	 *
	 * @return void
	 */
	public function testAConfusinglyNamedPluginIsIgnored() {
		$this->assertNoRedirectFor( 'other/not-spreadshop.php.bak' );
	}

	/**
	 * An empty filename must not fatal.
	 *
	 * @return void
	 */
	public function testAnEmptyFilenameIsIgnored() {
		$this->assertNoRedirectFor( '' );
	}

	/**
	 * Bulk activation must not abandon the remaining plugins mid-loop.
	 *
	 * @return void
	 */
	public function testBulkActivationDoesNotRedirect() {
		$_GET['activate-multi'] = '1';

		try {
			$this->assertNoRedirectFor( 'spreadshop/spreadshop.php' );
		} finally {
			unset( $_GET['activate-multi'] );
		}
	}

	/**
	 * WP-CLI has nowhere to be redirected to, and says so loudly if you try.
	 *
	 * @return void
	 */
	public function testWpCliDoesNotRedirect() {
		if ( ! defined( 'WP_CLI' ) ) {
			define( 'WP_CLI', true );
		}

		$this->assertNoRedirectFor( 'spreadshop/spreadshop.php' );
	}
}
