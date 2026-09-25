<?php
/**
 * The layout settings contract.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Layout;

defined( 'ABSPATH' ) || exit;

/**
 * Class Schema
 * Reads schema/layout.schema.json, the single source for the layout options' allowed values
 * and defaults. The admin form, the sanitisers and the front-end types all derive from it.
 */
class Schema {

	/**
	 * The schema's "properties", decoded once per request.
	 *
	 * @var array<string, mixed>|null
	 */
	private static $properties = null;

	/**
	 * Returns one property's schema.
	 *
	 * @param string $option Option name, as it appears under "properties".
	 * @return array<string, mixed>
	 * @throws \RuntimeException When the option is not part of the contract.
	 */
	public static function property( $option ) {
		$properties = self::properties();
		if ( ! isset( $properties[ $option ] ) || ! is_array( $properties[ $option ] ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Never rendered; a developer-facing message.
			throw new \RuntimeException( 'Spreadshop layout schema has no property ' . $option );
		}
		return $properties[ $option ];
	}

	/**
	 * Returns an option's default value.
	 *
	 * @param string $option Option name.
	 * @return mixed
	 */
	public static function defaultValue( $option ) {
		return self::property( $option )['default'] ?? null;
	}

	/**
	 * Returns the allowed values of a scalar option, or of an array option's items.
	 *
	 * @param string $option Option name.
	 * @return array<int, mixed>
	 */
	public static function allowedValues( $option ) {
		$property = self::property( $option );
		$items    = isset( $property['items'] ) && is_array( $property['items'] ) ? $property['items'] : $property;
		return isset( $items['enum'] ) && is_array( $items['enum'] ) ? array_values( $items['enum'] ) : array();
	}

	/**
	 * Returns an object option's keys mapped to the pattern each value must match.
	 *
	 * @param string $option Option name.
	 * @return array<string, string>
	 */
	public static function keyPatterns( $option ) {
		$property = self::property( $option );
		$keys     = isset( $property['properties'] ) && is_array( $property['properties'] ) ? $property['properties'] : array();
		$patterns = array();
		foreach ( $keys as $key => $spec ) {
			if ( is_string( $key ) && is_array( $spec ) && isset( $spec['pattern'] ) && is_string( $spec['pattern'] ) ) {
				$patterns[ $key ] = $spec['pattern'];
			}
		}
		return $patterns;
	}

	/**
	 * Every option the contract defines, in schema order.
	 *
	 * @return string[]
	 */
	public static function options() {
		return array_map( 'strval', array_keys( self::properties() ) );
	}

	/**
	 * Decodes the schema file.
	 *
	 * A missing or unreadable schema is a broken install, not a state to recover from: every
	 * sanitiser and default hangs off it, so carrying on would store unvalidated values.
	 *
	 * @return array<string, mixed>
	 * @throws \RuntimeException When the file is missing or is not a schema.
	 */
	private static function properties() {
		if ( self::$properties !== null ) {
			return self::$properties;
		}

		$path = dirname( __DIR__, 2 ) . '/schema/layout.schema.json';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- A local file shipped with the plugin, not a remote url.
		$json    = is_readable( $path ) ? file_get_contents( $path ) : false;
		$decoded = is_string( $json ) ? json_decode( $json, true ) : null;
		if ( ! is_array( $decoded ) || ! isset( $decoded['properties'] ) || ! is_array( $decoded['properties'] ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Never rendered; a developer-facing message.
			throw new \RuntimeException( 'Spreadshop layout schema missing or invalid at ' . $path );
		}

		self::$properties = $decoded['properties'];
		return self::$properties;
	}
}
