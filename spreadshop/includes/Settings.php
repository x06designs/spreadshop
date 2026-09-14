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
	 * Registering an option also whitelists it for core's wp-admin/options.php handler, which
	 * is a second way in that never passes through this plugin's own forms. Without a
	 * sanitize_callback the validation in ConnectTab and AdvancedTab simply would not run on
	 * that path, and a stored shop id could then carry a host rather than a number.
	 *
	 * @return void
	 */
	public static function registerAll() {
		foreach ( self::sanitizers() as $option => $sanitizer ) {
			register_setting(
				Constants::SPREADSHOP_SETTINGS_GROUP,
				$option,
				array( 'sanitize_callback' => $sanitizer )
			);
		}
	}

	/**
	 * How each option is cleaned, whichever route wrote it.
	 *
	 * @return array<string, callable> Option name to sanitiser.
	 */
	private static function sanitizers() {
		return array(
			'spreadshopID'          => array( __CLASS__, 'sanitizeShopId' ),
			'spreadshopToken'       => array( __CLASS__, 'sanitizeUrlFragment' ),
			'spreadshopPlatform'    => array( __CLASS__, 'sanitizePlatform' ),
			'spreadshopSlug'        => array( __CLASS__, 'sanitizeUrlFragment' ),
			'spreadshopOptimizeUrl' => array( __CLASS__, 'sanitizeToggle' ),
			'spreadshopMetadata'    => array( __CLASS__, 'sanitizeToggle' ),
			'spreadshopSwipeMenu'   => array( __CLASS__, 'sanitizeToggle' ),
			'spreadshopLocale'      => array( __CLASS__, 'sanitizeLocale' ),
			'spreadshopLoadFonts'   => array( __CLASS__, 'sanitizeToggle' ),
		);
	}

	/**
	 * A shop id is digits, or nothing.
	 *
	 * Anything else ends up as a hostname: the id becomes the subdomain the shop is fetched
	 * from, so "evil.example/x?" would repoint both the lookup and the embedded client.
	 *
	 * @param mixed $value Candidate value.
	 * @return string
	 */
	public static function sanitizeShopId( $value ) {
		return is_string( $value ) && preg_match( '/^[0-9]+$/', $value ) ? $value : '';
	}

	/**
	 * A platform is one of the two we know, or nothing.
	 *
	 * @param mixed $value Candidate value.
	 * @return string
	 */
	public static function sanitizePlatform( $value ) {
		return Platform::isValid( $value ) ? $value : '';
	}

	/**
	 * A locale looks like de_DE, or nothing.
	 *
	 * @param mixed $value Candidate value.
	 * @return string
	 */
	public static function sanitizeLocale( $value ) {
		return is_string( $value ) && preg_match( '/^[a-z]{2}_[A-Z]{2}$/', $value ) ? $value : '';
	}

	/**
	 * A slug or deeplink keeps its percent-encoding but loses any markup.
	 *
	 * @param mixed $value Candidate value.
	 * @return string
	 */
	public static function sanitizeUrlFragment( $value ) {
		return is_string( $value ) ? wp_strip_all_tags( $value ) : '';
	}

	/**
	 * A toggle is stored as 1 or 0, never as whatever was posted.
	 *
	 * @param mixed $value Candidate value.
	 * @return int
	 */
	public static function sanitizeToggle( $value ) {
		return empty( $value ) ? 0 : 1;
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
