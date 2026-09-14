<?php
/**
 * Stand-in for a WordPress post.
 *
 * Global namespace on purpose: ResourceHints type-checks against \WP_Post before reading the content it searches for
 * the short code.
 *
 * @package Spreadshop
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Stands in for a WordPress class.

/**
 * Class WP_Post
 */
class WP_Post {

	/**
	 * The post body.
	 *
	 * @var string
	 */
	public $post_content = '';
}
