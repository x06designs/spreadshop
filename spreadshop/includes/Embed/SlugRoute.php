<?php
/**
 * The alternative slug based integration.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Embed;

defined( 'ABSPATH' ) || exit;

/**
 * Class SlugRoute
 * Serves the shop at a configured url path, for sites that would rather not use the short code.
 */
class SlugRoute {

	/**
	 * Renders the shop to the page matching the configured slug.
	 *
	 * @param string $template Template WordPress resolved for this request.
	 * @return string Our embed template when the request matches the configured slug, else $template.
	 */
	public static function filterTemplate( $template ) {
		if ( ! self::matchesRequest() ) {
			return $template;
		}

		return dirname( __DIR__, 2 ) . '/templates/embed-page.php';
	}

	/**
	 * Whether the current request should be served the shop.
	 *
	 * @return bool True when the request path matches the configured slug.
	 */
	private static function matchesRequest() {
		$ourSlug = get_option( 'spreadshopSlug' );
		if ( ! $ourSlug || ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$basePath = self::basePath( $ourSlug );

		// esc_url_raw, not sanitize_text_field: the latter strips percent-encoded octets,
		// which would break the comparison for any slug containing encoded characters.
		$requestUri  = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$requestPath = strtok( strtok( $requestUri, '#' ), '?' ); // Remove query and hashbang parts.

		if ( $basePath === $requestPath || $basePath . '/' === $requestPath ) {
			return true;
		}

		// With pushState urls the shop also owns everything below its base path. The test is on
		// a whole path segment: a bare prefix would let a slug of "shop" swallow "/shopping-cart"
		// and every other page that merely starts with those letters.
		return (bool) get_option( 'spreadshopOptimizeUrl' )
			&& strpos( $requestPath, $basePath . '/' ) === 0;
	}

	/**
	 * The site-relative path the shop is served from.
	 *
	 * @param string $slug Configured slug.
	 * @return string Path beginning with a slash.
	 */
	private static function basePath( $slug ) {
		$homeUrl = wp_parse_url( get_home_url() );
		$prefix  = isset( $homeUrl['path'] ) ? $homeUrl['path'] : '';

		return $prefix . '/' . trim( $slug, " \t\n\r\0\x0B/" );
	}

	/**
	 * Base url for pushState routing, or null when hashbang urls are in use.
	 *
	 * @return string|null
	 */
	public static function pushStateBaseUrl() {
		if ( ! get_option( 'spreadshopOptimizeUrl' ) ) {
			return null;
		}

		return rtrim( get_home_url(), '/' ) . '/' . trim( get_option( 'spreadshopSlug' ), " \t\n\r\0\x0B/" );
	}
}
