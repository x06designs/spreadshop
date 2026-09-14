<?php
/**
 * The [spreadshop] shortcode.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Embed;

defined( 'ABSPATH' ) || exit;

/**
 * Class Shortcode
 * Renders the shop into any post or page carrying the [spreadshop] short code.
 */
class Shortcode {

	/**
	 * Whether the shop has already been rendered on this request.
	 *
	 * @var bool
	 */
	private static $alreadyRun = false;

	/**
	 * Renders the shop, at most once per request.
	 *
	 * The shop client can only drive one embed per document, so a second short code on the
	 * same page renders nothing rather than emitting a config object that fights the first.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes; 'deeplink' opens a specific shop page.
	 * @return string The embed markup, or an empty string if the shop was already rendered.
	 */
	public static function render( $atts ) {
		if ( self::$alreadyRun ) {
			return '';
		}
		self::$alreadyRun = true;

		$startToken = is_array( $atts ) && isset( $atts['deeplink'] ) ? $atts['deeplink'] : null;
		return Renderer::render( null, $startToken );
	}
}
