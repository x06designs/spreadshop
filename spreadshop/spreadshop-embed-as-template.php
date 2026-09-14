<?php
/**
 * Page template used by the slug based integration. Never loaded for shortcode embeds.
 *
 * @package Spreadshop
 */

defined( 'ABSPATH' ) || exit;

require_once plugin_dir_path( __FILE__ ) . 'spreadshop-embed.php';

/**
 * Titles the generated page after the configured slug.
 *
 * The incoming parts are replaced rather than merged, so the title is the slug alone with
 * no site name appended. That is the behaviour this integration has always had; preserving
 * it here keeps existing pages' titles stable.
 *
 * @param array<string, string> $titleParts Existing title parts, deliberately discarded.
 * @return array<string, string> Title parts for the document_title_parts filter.
 */
function spreadshopSetTitle( $titleParts ) {
	unset( $titleParts );
	return array( 'title' => get_option( 'spreadshopSlug' ) );
}

/**
 * Keeps Yoast SEO from marking the generated page as noindex.
 *
 * Yoast sets "noindex, follow" on every page where is_404() is true, which a slug based
 * integration always is, because no WordPress post backs the url.
 *
 * @return bool Always false.
 */
function spreadshopAvoidYoastNoindex() {
	return false;
}

add_filter( 'document_title_parts', 'spreadshopSetTitle', 10, 1 );
add_filter( 'wpseo_robots', 'spreadshopAvoidYoastNoindex' );
status_header( 200 );

$spreadshopPushStateBaseUrl = null;
if ( get_option( 'spreadshopOptimizeUrl' ) ) {
	$spreadshopPushStateBaseUrl = rtrim( get_home_url(), '/' ) . '/' . trim( get_option( 'spreadshopSlug' ), " \t\n\r\0\x0B/" );
}

/*
 * A block theme has no header.php or footer.php. get_header() then emits a deprecation
 * notice into the page and produces no document at all -- no doctype, no <head> -- and the
 * shop client refuses to render into a quirks-mode document, replacing the shop with a
 * full-width error. So the document is assembled here and the theme's header and footer
 * template parts are rendered in place of the classic includes.
 */
$spreadshopIsBlockTheme = function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();

if ( $spreadshopIsBlockTheme ) {
	?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>"/>
		<meta name="viewport" content="width=device-width, initial-scale=1"/>
		<?php wp_head(); ?>
	</head>
	<body <?php body_class(); ?>>
	<?php
	wp_body_open();
	block_header_area();
} else {
	get_header();
}

// spreadshopEmbed() builds its own markup and escapes every dynamic part it interpolates;
// wp_kses_post() here would strip the shop client script tag and break the embed entirely.
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped at construction, see above.
echo spreadshopEmbed( $spreadshopPushStateBaseUrl, null );

if ( $spreadshopIsBlockTheme ) {
	block_footer_area();
	wp_footer();
	?>
	</body>
	</html>
	<?php
} else {
	get_footer();
}
