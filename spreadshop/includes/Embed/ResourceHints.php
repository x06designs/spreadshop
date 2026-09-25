<?php
/**
 * Connection hints for the origins the shop pulls from.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Embed;

use Spreadshop\Platform;

defined( 'ABSPATH' ) || exit;

/**
 * Class ResourceHints
 *
 * The shop is drawn by a client that fetches from three origins, and the browser cannot
 * discover two of them until that client has downloaded, booted and started laying out
 * products. Measured cold, the image CDN alone costs about 300ms to reach -- all of it
 * before the first product picture can begin downloading. Announcing the origins in the
 * document head lets those handshakes happen in parallel with the client instead.
 */
class ResourceHints {

	/**
	 * Origins the shop client fetches product and design images from.
	 *
	 * @var string
	 */
	const IMAGE_HOST = 'https://image.spreadshirtmedia.net';

	/**
	 * Origin serving Spreadshirt's own consent script.
	 *
	 * @var string
	 */
	const CONSENT_HOST = 'https://www.spreadshirt.net';

	/**
	 * Adds the hints, but only for requests that will actually render a shop.
	 *
	 * @param string[] $hints        Hints already registered for this relation.
	 * @param string   $relationType The relation being filtered.
	 * @return string[]
	 */
	public static function filter( $hints, $relationType ) {
		if ( ! EmbedDetector::willRender() ) {
			return $hints;
		}

		if ( 'preconnect' === $relationType ) {
			$hints[] = self::shopOrigin();
			$hints[] = self::IMAGE_HOST;
		}

		if ( 'dns-prefetch' === $relationType ) {
			// Reached once, late, and only to decide what the consent banner shows. Resolving
			// the name early is worth it; holding a connection open for it is not.
			$hints[] = self::CONSENT_HOST;
		}

		return $hints;
	}

	/**
	 * The origin the connected shop is served from.
	 *
	 * @return string
	 */
	private static function shopOrigin() {
		return Platform::shopOrigin( get_option( 'spreadshopID' ), get_option( 'spreadshopPlatform' ) );
	}
}
