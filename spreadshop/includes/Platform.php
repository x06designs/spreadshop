<?php
/**
 * The two Spreadshop platforms.
 *
 * @package Spreadshop
 */

namespace Spreadshop;

defined( 'ABSPATH' ) || exit;

/**
 * Class Platform
 *
 * Which platform a shop lives on decides which domain serves it. That rule used to be
 * written out at both call sites, so a third platform would have meant finding both.
 */
class Platform {

	/**
	 * The European platform.
	 *
	 * @var string
	 */
	const EU = 'EU';

	/**
	 * The North American platform.
	 *
	 * @var string
	 */
	const NA = 'NA';

	/**
	 * Every platform a shop may be connected to.
	 *
	 * @return string[]
	 */
	public static function all() {
		return array( self::EU, self::NA );
	}

	/**
	 * Whether the given value names a platform.
	 *
	 * @param string $platform Platform code.
	 * @return bool
	 */
	public static function isValid( $platform ) {
		return in_array( $platform, self::all(), true );
	}

	/**
	 * The top level domain serving a platform.
	 *
	 * Anything that is not the European platform is treated as North American, which is how
	 * this has always behaved: an unset or corrupted option falls back to .com rather than
	 * producing no shop at all.
	 *
	 * @param string $platform Platform code.
	 * @return string Either 'net' or 'com'.
	 */
	public static function tld( $platform ) {
		return self::EU === $platform ? 'net' : 'com';
	}

	/**
	 * The origin a shop is served from.
	 *
	 * @param string $shopIdOrName Numeric shop id, or the shop's url name.
	 * @param string $platform     Platform code.
	 * @return string Origin, without a trailing slash.
	 */
	public static function shopOrigin( $shopIdOrName, $platform ) {
		return 'https://' . $shopIdOrName . '.myspreadshop.' . self::tld( $platform );
	}
}
