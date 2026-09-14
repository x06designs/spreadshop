<?php
/**
 * Stand-in for WordPress's main query object.
 *
 * Global namespace on purpose: SlugRoute type-checks against \WP_Query, and without a class of that name the check could
 * never pass and the claim would be untestable.
 *
 * @package Spreadshop
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Stands in for a WordPress class.

/**
 * Class WP_Query
 */
class WP_Query {

	/**
	 * Whether the request resolved to a missing page.
	 *
	 * @var bool
	 */
	public $is_404 = true;
}
