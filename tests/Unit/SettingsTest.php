<?php
/**
 * Reading and clearing the plugin's options.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Tests\Unit;

use Brain\Monkey\Functions;
use Spreadshop\Constants;
use Spreadshop\Settings;

/**
 * Covers Settings.
 */
class SettingsTest extends PluginTestCase {

	/**
	 * A stored shop id means connected.
	 *
	 * @return void
	 */
	public function testAStoredShopIdMeansConnected() {
		Functions\when( 'get_option' )->justReturn( '1376884' );

		$this->assertTrue( Settings::isConnected() );
	}

	/**
	 * Values that must all read as not connected.
	 *
	 * @dataProvider disconnectedProvider
	 * @param mixed $stored What get_option returns.
	 * @return void
	 */
	public function testTheseCountAsDisconnected( $stored ) {
		Functions\when( 'get_option' )->justReturn( $stored );

		$this->assertFalse( Settings::isConnected() );
	}

	/**
	 * Option values that mean "no shop".
	 *
	 * The literal string "undefined" is the one that matters: versions before 1.6 wrote it
	 * instead of clearing the option, so a site upgraded from one of those still carries it.
	 *
	 * @return array<string, array{mixed}>
	 */
	public function disconnectedProvider() {
		return array(
			'never set'                 => array( false ),
			'empty string'              => array( '' ),
			'legacy undefined sentinel' => array( 'undefined' ),
			'zero'                      => array( '0' ),
		);
	}

	/**
	 * Every option the plugin owns is removed, not just the obvious ones.
	 *
	 * @return void
	 */
	public function testDeleteAllRemovesEveryOwnedOption() {
		$deleted = array();
		Functions\when( 'delete_option' )->alias(
			static function ( $name ) use ( &$deleted ) {
				$deleted[] = $name;
				return true;
			}
		);

		Settings::deleteAll();

		$this->assertSame( Constants::SPREADSHOP_OPTIONS, $deleted );
	}

	/**
	 * Every option is registered against the settings group the admin forms nonce with.
	 *
	 * @return void
	 */
	public function testRegisterAllCoversEveryOwnedOption() {
		$registered = array();
		Functions\when( 'register_setting' )->alias(
			static function ( $group, $name ) use ( &$registered ) {
				$registered[ $name ] = $group;
				return true;
			}
		);

		Settings::registerAll();

		$this->assertSame( Constants::SPREADSHOP_OPTIONS, array_keys( $registered ) );
		$this->assertSame(
			array( Constants::SPREADSHOP_SETTINGS_GROUP ),
			array_values( array_unique( $registered ) )
		);
	}

	/**
	 * The option names are a compatibility contract with older installs.
	 *
	 * Renaming any of these silently disconnects every site that upgrades in place, which is
	 * why they are pinned here rather than left to whatever the constant happens to say.
	 *
	 * @return void
	 */
	public function testTheOptionNamesAreFrozen() {
		$this->assertSame(
			array(
				'spreadshopID',
				'spreadshopToken',
				'spreadshopPlatform',
				'spreadshopSlug',
				'spreadshopOptimizeUrl',
				'spreadshopMetadata',
				'spreadshopSwipeMenu',
				'spreadshopLocale',
				'spreadshopLoadFonts',
			),
			Constants::SPREADSHOP_OPTIONS
		);
	}
}
