<?php
/**
 * Renders the Spreadshop embed markup.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Embed;

use Spreadshop\Constants;

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
	 * Returns the embed markup.
	 *
	 * @param string|null $pushStateBaseUrl   Base url for pushState routing, or null to use hashbang urls.
	 * @param string|null $startTokenOverride Deeplink to open instead of the configured start token.
	 * @return string The embed markup.
	 */
	public static function render( $pushStateBaseUrl, $startTokenOverride ) {
		$tld          = get_option( 'spreadshopPlatform' ) === 'EU' ? 'net' : 'com';
		$shopId       = get_option( 'spreadshopID' );
		$shopBaseUrl  = 'https://' . $shopId . '.myspreadshop.' . $tld;
		$config_array = array(
			'shopName'            => $shopId,
			'prefix'              => $shopBaseUrl,
			'baseId'              => 'myShop',
			'locale'              => get_option( 'spreadshopLocale', '' ),
			'startToken'          => $startTokenOverride ? $startTokenOverride : get_option( 'spreadshopToken', '' ),
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
		$output .= '<div id="primary" class="content-area">';
		$output .= '    <main id="main" class="site-main">';
		$output .= '        <div id="myShop"></div>';
		$output .= '    </main>';
		$output .= '</div>';
		// The shop client is served by Spreadshirt and must load in the document that embeds it;
		// enqueueing it would detach it from this markup and from the config object above.
		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Third-party embed, see above.
		$output .= '<script src="' . esc_url( $shopBaseUrl . '/shopfiles/shopclient/shopclient.nocache.js' ) . '"></script>';
		return $output;
	}
}
