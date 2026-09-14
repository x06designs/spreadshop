<?php
/**
 * Shared setup for the unit suite.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Tests\Unit;

use Brain\Monkey\Functions;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

/**
 * Stubs the WordPress functions every part of the plugin reaches for.
 *
 * Brain Monkey's own helpers cover translation and escaping, but not esc_url_raw or
 * wp_unslash, and the plugin leans on both. Stubbing them centrally keeps the individual
 * tests about behaviour rather than about scaffolding.
 */
abstract class PluginTestCase extends TestCase {

	/**
	 * Registers the shared stubs.
	 *
	 * @return void
	 */
	protected function set_up() {
		parent::set_up();

		Functions\stubTranslationFunctions();
		Functions\stubEscapeFunctions();

		Functions\when( 'esc_url_raw' )->returnArg();
		Functions\when( 'wp_unslash' )->returnArg();
	}
}
