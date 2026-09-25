<?php
/**
 * Access to the options this plugin owns.
 *
 * @package Spreadshop
 */

namespace Spreadshop;

use Spreadshop\Layout\Schema;

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
			'spreadshopID'                  => array( __CLASS__, 'sanitizeShopId' ),
			'spreadshopToken'               => array( __CLASS__, 'sanitizeUrlFragment' ),
			'spreadshopPlatform'            => array( __CLASS__, 'sanitizePlatform' ),
			'spreadshopSlug'                => array( __CLASS__, 'sanitizeUrlFragment' ),
			'spreadshopOptimizeUrl'         => array( __CLASS__, 'sanitizeToggle' ),
			'spreadshopMetadata'            => array( __CLASS__, 'sanitizeToggle' ),
			'spreadshopSwipeMenu'           => array( __CLASS__, 'sanitizeToggle' ),
			'spreadshopLocale'              => array( __CLASS__, 'sanitizeLocale' ),
			'spreadshopLoadFonts'           => array( __CLASS__, 'sanitizeToggle' ),
			'spreadshopLayoutSidebar'       => array( __CLASS__, 'sanitizeToggle' ),
			'spreadshopLayoutCompactFooter' => array( __CLASS__, 'sanitizeToggle' ),
			'spreadshopLayoutCards'         => array( __CLASS__, 'sanitizeToggle' ),
			'spreadshopLayoutProductPage'   => array( __CLASS__, 'sanitizeToggle' ),
			'spreadshopCardFields'          => array( __CLASS__, 'sanitizeCardFields' ),
			'spreadshopStartList'           => array( __CLASS__, 'sanitizeStartList' ),
			'spreadshopColors'              => array( __CLASS__, 'sanitizeColors' ),
		);
	}

	/**
	 * Card fields are a unique, ordered list of known ids.
	 *
	 * Runs twice per save from the plugin form (the form sanitises, then update_option runs the
	 * registered callback again), so it has to be idempotent. The form posts an empty sentinel
	 * item so that "no fields" arrives as an array; only a missing value, which is what
	 * options.php sends for an absent field, falls back to the default.
	 *
	 * The fields drawn on the image rather than beside it are moved to the end: their position
	 * has no visible effect, and a canonical order keeps stored values comparable.
	 *
	 * @param mixed $value Candidate value.
	 * @return string[]
	 */
	public static function sanitizeCardFields( $value ) {
		if ( ! is_array( $value ) ) {
			$default = Schema::defaultValue( 'spreadshopCardFields' );
			return is_array( $default ) ? array_map( 'strval', $default ) : array();
		}

		$allowed = Schema::allowedValues( 'spreadshopCardFields' );
		$kept    = array();
		foreach ( $value as $field ) {
			if ( is_string( $field ) && in_array( $field, $allowed, true ) && ! in_array( $field, $kept, true ) ) {
				$kept[] = $field;
			}
		}

		$onImage = array( 'hoverImage' );
		$beside  = array_values( array_diff( $kept, $onImage ) );
		$onTop   = array_values( array_intersect( $kept, $onImage ) );
		return array_merge( $beside, $onTop );
	}

	/**
	 * The start list is one of the schema's values, or the default.
	 *
	 * @param mixed $value Candidate value.
	 * @return string
	 */
	public static function sanitizeStartList( $value ) {
		if ( is_string( $value ) && in_array( $value, Schema::allowedValues( 'spreadshopStartList' ), true ) ) {
			return $value;
		}
		return (string) Schema::defaultValue( 'spreadshopStartList' );
	}

	/**
	 * Colours are a map of known token names to #rrggbb, lower-cased.
	 *
	 * These values are printed into a stylesheet, so this is an injection boundary: an unknown
	 * key or anything but six hex digits is dropped, never escaped and kept. Tokens::render()
	 * applies the same filter again on output.
	 *
	 * @param mixed $value Candidate value.
	 * @return array<string, string>
	 */
	public static function sanitizeColors( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$kept = array();
		foreach ( Schema::keyPatterns( 'spreadshopColors' ) as $key => $pattern ) {
			if ( ! isset( $value[ $key ] ) || ! is_string( $value[ $key ] ) ) {
				continue;
			}
			$color = strtolower( trim( $value[ $key ] ) );
			if ( preg_match( '/' . str_replace( '/', '\/', $pattern ) . '/', $color ) === 1 ) {
				$kept[ $key ] = $color;
			}
		}
		return $kept;
	}

	/**
	 * Whether a layout toggle is on.
	 *
	 * Reads through the sanitiser rather than trusting the row: registered defaults only exist
	 * once admin_init has run, which the front end never does.
	 *
	 * @param string $option One of the three layout toggles.
	 * @return bool
	 */
	public static function isLayoutEnabled( $option ) {
		return self::sanitizeToggle( get_option( $option, Schema::defaultValue( $option ) ) ) === 1;
	}

	/**
	 * Whether any layout toggle is on.
	 *
	 * @return bool
	 */
	public static function isAnyLayoutEnabled() {
		foreach ( array( 'spreadshopLayoutSidebar', 'spreadshopLayoutCompactFooter', 'spreadshopLayoutCards', 'spreadshopLayoutProductPage' ) as $option ) {
			if ( self::isLayoutEnabled( $option ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * The card fields to show, in order; the default when nothing was saved yet.
	 *
	 * @return string[]
	 */
	public static function cardFields() {
		return self::sanitizeCardFields( get_option( 'spreadshopCardFields', null ) );
	}

	/**
	 * What the shop opens on when nothing more specific was asked for.
	 *
	 * @return string
	 */
	public static function startList() {
		return self::sanitizeStartList( get_option( 'spreadshopStartList', null ) );
	}

	/**
	 * The colour overrides that survive sanitising.
	 *
	 * @return array<string, string>
	 */
	public static function colors() {
		return self::sanitizeColors( get_option( 'spreadshopColors', array() ) );
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
	 * Drops the linked shop and its settings. Runs when the admin disconnects the shop.
	 *
	 * The layout options stay: they describe how this site presents a shop, and reconnecting
	 * (or connecting a different shop) should not undo that.
	 *
	 * @return void
	 */
	public static function deleteConnection() {
		foreach ( Constants::SPREADSHOP_OPTIONS as $option ) {
			delete_option( $option );
		}
	}

	/**
	 * Drops every stored setting. Runs on uninstall.
	 *
	 * Deliberately not bound to deactivation: that would discard the configuration whenever
	 * the plugin is switched off for maintenance or during an update.
	 *
	 * @return void
	 */
	public static function deleteAll() {
		foreach ( array_merge( Constants::SPREADSHOP_OPTIONS, Constants::SPREADSHOP_LAYOUT_OPTIONS ) as $option ) {
			delete_option( $option );
		}
	}
}
