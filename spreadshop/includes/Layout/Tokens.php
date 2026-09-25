<?php
/**
 * The colours chosen in the settings, as CSS custom properties.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Layout;

use Spreadshop\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class Tokens
 * Turns the saved colour map into one CSS rule on #myShop. Printed there, rather than on :root,
 * so a colour chosen in the plugin beats the theme's mapping of the same property.
 */
class Tokens {

	/**
	 * Setting key and the custom property it sets.
	 *
	 * @var array<string, string>
	 */
	const PROPERTIES = array(
		'accent'     => '--spreadshop-accent',
		'accentText' => '--spreadshop-accent-text',
		'background' => '--spreadshop-background',
		'text'       => '--spreadshop-text',
		'muted'      => '--spreadshop-muted',
		'border'     => '--spreadshop-border',
	);

	/**
	 * The rule, or an empty string when no colour was chosen.
	 *
	 * Every value is checked again here although it was checked on save: the row can be written
	 * by anything with database access, and this string ends up in a stylesheet.
	 *
	 * @return string
	 */
	public static function css() {
		$declarations = array();
		foreach ( Settings::colors() as $key => $value ) {
			$color = sanitize_hex_color( $value );
			if ( ! isset( self::PROPERTIES[ $key ] ) || ! is_string( $color ) || $color === '' ) {
				continue;
			}
			$declarations[] = self::PROPERTIES[ $key ] . ':' . $color;
		}

		return $declarations === array() ? '' : '#myShop{' . implode( ';', $declarations ) . '}';
	}
}
