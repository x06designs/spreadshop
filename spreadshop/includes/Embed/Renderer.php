<?php
/**
 * Renders the Spreadshop embed markup.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Embed;

use Spreadshop\Constants;
use Spreadshop\Platform;
use Spreadshop\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Returns render output for the spread_shop_config object, a placeholder div and links the shopclient.nocache.js script, thus performing the actual integration.
 * This function is used in short code as well as slug based integrations.
 *
 * @param string|null $pushStateBaseUrl   Base url for pushState routing, or null to use hashbang urls.
 * @param string|null $startTokenOverride Deeplink to open instead of the configured start token.
 * @return string The embed markup.
 */
class Renderer {

	/**
	 * Start token that opens the shop on its product list instead of its design list.
	 *
	 * The shop client routes a token beginning with "?" to its front page with that query,
	 * and its list endpoint answers listModeOverride=PRODUCT with products.
	 *
	 * @var string
	 */
	const PRODUCT_LIST_TOKEN = '?listModeOverride=PRODUCT';

	/**
	 * Layout toggle options and the wrapper class each one sets.
	 *
	 * @var array<string, string>
	 */
	const LAYOUT_CLASSES = array(
		'spreadshopLayoutSidebar'       => 'spreadshop-layout--sidebar',
		'spreadshopLayoutCompactFooter' => 'spreadshop-layout--compact-footer',
		'spreadshopLayoutCards'         => 'spreadshop-layout--cards',
		'spreadshopLayoutProductPage'   => 'spreadshop-layout--product-page',
	);

	/**
	 * Returns the embed markup.
	 *
	 * @param string|null $pushStateBaseUrl   Base url for pushState routing, or null to use hashbang urls.
	 * @param string|null $startTokenOverride Deeplink to open instead of the configured start token.
	 * @return string The embed markup.
	 */
	public static function render( $pushStateBaseUrl, $startTokenOverride ) {
		$shopId       = get_option( 'spreadshopID' );
		$shopBaseUrl  = Platform::shopOrigin( $shopId, get_option( 'spreadshopPlatform' ) );
		$config_array = array(
			'shopName'            => $shopId,
			'prefix'              => $shopBaseUrl,
			'baseId'              => 'myShop',
			'locale'              => get_option( 'spreadshopLocale', '' ),
			'startToken'          => self::startToken( $startTokenOverride ),
			'usePushState'        => (bool) $pushStateBaseUrl,
			'pushStateBaseUrl'    => $pushStateBaseUrl,
			'updateMetadata'      => (bool) get_option( 'spreadshopMetadata', false ),
			'swipeMenu'           => (bool) get_option( 'spreadshopSwipeMenu', false ),
			'loadFonts'           => (bool) get_option( 'spreadshopLoadFonts', false ),
			'integrationProvider' => 'Spreadshirt Wordpress plugin v' . Constants::SPREADSHOP_VERSION,
		);

		$output  = '';
		$output .= '<script type="text/javascript">';
		$output .= '    var spread_shop_config = ' . wp_json_encode( $config_array ) . ';';
		$output .= '</script>';
		$output .= '<div id="primary" class="' . esc_attr( implode( ' ', self::wrapperClasses() ) ) . '">';
		$output .= '    <div id="main" class="site-main">';
		$output .= '        <div id="myShop"></div>';
		$output .= '    </div>';
		$output .= '</div>';
		// The shop client is served by Spreadshirt and must load in the document that embeds it;
		// enqueueing it would detach it from this markup and from the config object above.
		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Third-party embed, see above.
		$output .= '<script src="' . esc_url( $shopBaseUrl . '/shopfiles/shopclient/shopclient.nocache.js' ) . '"></script>';
		return $output;
	}

	/**
	 * The page the shop opens on.
	 *
	 * A deeplink from the short code wins over the configured Start Token, which wins over the
	 * start list: each is a more specific request than the one after it.
	 *
	 * @param string|null $startTokenOverride Deeplink to open instead of the configured start token.
	 * @return string
	 */
	private static function startToken( $startTokenOverride ) {
		if ( $startTokenOverride ) {
			return $startTokenOverride;
		}

		$configured = get_option( 'spreadshopToken', '' );
		if ( is_string( $configured ) && $configured !== '' ) {
			return $configured;
		}

		return Settings::startList() === 'products' ? self::PRODUCT_LIST_TOKEN : '';
	}

	/**
	 * Classes for the embed wrapper: the theme hook it always had, plus one per layout toggle.
	 *
	 * @return string[]
	 */
	private static function wrapperClasses() {
		$classes = array( 'content-area' );
		foreach ( self::LAYOUT_CLASSES as $option => $class ) {
			if ( Settings::isLayoutEnabled( $option ) ) {
				$classes[] = $class;
			}
		}
		return $classes;
	}
}
