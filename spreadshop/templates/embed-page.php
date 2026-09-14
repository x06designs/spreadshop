<?php
/**
 * Page template used by the slug based integration. Never loaded for shortcode embeds.
 *
 * @package Spreadshop
 */

use Spreadshop\Embed\Renderer;
use Spreadshop\Embed\SlugRoute;

defined( 'ABSPATH' ) || exit;

/**
 * Titles the generated page after the configured slug.
 *
 * Only the title part is replaced. The site name and tagline the theme supplies are kept, so
 * the shop page is titled like every other page on the site rather than standing alone.
 *
 * @param array<string, string> $titleParts Existing title parts.
 * @return array<string, string> Title parts for the document_title_parts filter.
 */
function spreadshopSetTitle( $titleParts ) {
	$titleParts['title'] = get_option( 'spreadshopSlug' );

	return $titleParts;
}

add_filter( 'document_title_parts', 'spreadshopSetTitle', 10, 1 );

// The 404 state and the status header are dealt with by SlugRoute::claimRequest(), which
// runs early enough for SEO plugins to see the corrected request.

$spreadshopPushStateBaseUrl = SlugRoute::pushStateBaseUrl();

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

// Renderer::render() builds its own markup and escapes every dynamic part it interpolates;
// wp_kses_post() here would strip the shop client script tag and break the embed entirely.
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped at construction, see above.
echo Renderer::render( $spreadshopPushStateBaseUrl, null );

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
