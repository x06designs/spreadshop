<?php
/**
 * Whether the current request embeds the shop.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Embed;

use Spreadshop\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class EmbedDetector
 * Answers, before the content renders, whether this request is going to embed the shop.
 * Both the connection hints and the layout assets hang off this answer.
 */
class EmbedDetector {

	/**
	 * Whether this request is going to embed the shop.
	 *
	 * Guessing wrong in either direction has a cost: a miss gives up the head start of work done
	 * in the document head, and a false hit spends it on a page with no shop.
	 *
	 * The answer is only knowable up front for the slug route and for a singular post whose
	 * content carries the short code. A short code placed elsewhere (a widget, a block template)
	 * is only discovered when it renders, which is why Shortcode::render() has a late fallback.
	 *
	 * @return bool
	 */
	public static function willRender() {
		if ( ! Settings::isConnected() ) {
			return false;
		}

		if ( SlugRoute::ownsCurrentRequest() ) {
			return true;
		}

		if ( ! is_singular() ) {
			return false;
		}

		$post = get_post();

		return $post instanceof \WP_Post && has_shortcode( $post->post_content, 'spreadshop' );
	}
}
