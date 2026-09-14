<?php
/**
 * Access to the options this plugin owns.
 *
 * @package Spreadshop
 */

namespace Spreadshop;

defined( 'ABSPATH' ) || exit;

/**
 * Class Settings
 * The single place that reads, registers and removes the plugin's options.
 */
class Settings {

	/**
	 * Whether a shop has been linked.
	 *
	 * Versions before 1.6 stored the literal string "undefined" instead of clearing the option,
	 * so an install upgraded from one of those still has to be read as disconnected.
	 *
	 * @return bool True when a shop id is stored.
	 */
	public static function isConnected() {
		$shopId = get_option( 'spreadshopID', 'undefined' );
		return ! empty( $shopId ) && $shopId !== 'undefined';
	}

	/**
	 * Registers every option with the settings API, so the admin forms can nonce against them.
	 *
	 * @return void
	 */
	public static function registerAll() {
		foreach ( Constants::SPREADSHOP_OPTIONS as $option ) {
			register_setting( Constants::SPREADSHOP_SETTINGS_GROUP, $option );
		}
	}

	/**
	 * Drops every stored setting. Runs on uninstall, and when the admin disconnects the shop.
	 *
	 * Deliberately not bound to deactivation: that would discard the configuration whenever
	 * the plugin is switched off for maintenance or during an update.
	 *
	 * @return void
	 */
	public static function deleteAll() {
		foreach ( Constants::SPREADSHOP_OPTIONS as $option ) {
			delete_option( $option );
		}
	}
}
