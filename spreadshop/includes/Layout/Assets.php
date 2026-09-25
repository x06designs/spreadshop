<?php
/**
 * The stylesheets behind the layout options.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Layout;

use Spreadshop\Constants;
use Spreadshop\Embed\EmbedDetector;
use Spreadshop\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class Assets
 * Loads one stylesheet per layout option that is on, plus the shared tokens they draw from, and
 * only on requests that embed the shop.
 */
class Assets {

	/**
	 * Handle of the shared tokens stylesheet; the option stylesheets depend on it.
	 *
	 * @var string
	 */
	const TOKENS_HANDLE = 'spreadshopLayoutTokens';

	/**
	 * Layout toggle, its stylesheet handle and its file under style/layout/.
	 *
	 * @var array<string, array{0: string, 1: string}>
	 */
	const STYLESHEETS = array(
		'spreadshopLayoutSidebar'       => array( 'spreadshopLayoutSidebar', 'sidebar.css' ),
		'spreadshopLayoutCompactFooter' => array( 'spreadshopLayoutCompactFooter', 'compact-footer.css' ),
		'spreadshopLayoutCards'         => array( 'spreadshopLayoutCards', 'cards.css' ),
		'spreadshopLayoutProductPage'   => array( 'spreadshopLayoutProductPage', 'product-page.css' ),
	);

	/**
	 * Handle of the observer every layout script registers with.
	 *
	 * @var string
	 */
	const OBSERVE_HANDLE = 'spreadshopLayoutObserve';

	/**
	 * Handle of the category-tree script the sidebar option loads.
	 *
	 * @var string
	 */
	const NAV_HANDLE = 'spreadshopLayoutNav';

	/**
	 * Handle of the card model the cards script reads the product list with.
	 *
	 * @var string
	 */
	const DATA_HANDLE = 'spreadshopLayoutData';

	/**
	 * Handle of the script that draws the product cards.
	 *
	 * @var string
	 */
	const CARDS_HANDLE = 'spreadshopLayoutCards';

	/**
	 * Handle of the product-page script (detail tabs, sold-out sizes).
	 *
	 * @var string
	 */
	const PRODUCT_HANDLE = 'spreadshopLayoutProduct';

	/**
	 * Whether the stylesheets were already enqueued on this request.
	 *
	 * @var bool
	 */
	private static $isEnqueued = false;

	/**
	 * Enqueues the stylesheets in the document head when the embed is known up front.
	 *
	 * @return void
	 */
	public static function enqueueForEmbed() {
		if ( EmbedDetector::willRender() ) {
			self::enqueue();
		}
	}

	/**
	 * Enqueues the stylesheets, at most once per request.
	 *
	 * Also called while the short code renders, for a short code the detector could not see in
	 * advance; WordPress then prints the stylesheets in the footer.
	 *
	 * @return void
	 */
	public static function enqueue() {
		if ( self::$isEnqueued || ! Settings::isAnyLayoutEnabled() ) {
			return;
		}
		self::$isEnqueued = true;

		wp_enqueue_style( self::TOKENS_HANDLE, self::url( 'tokens.css' ), array(), Constants::SPREADSHOP_VERSION );
		$tokens = Tokens::css();
		if ( $tokens !== '' ) {
			wp_add_inline_style( self::TOKENS_HANDLE, $tokens );
		}

		foreach ( self::STYLESHEETS as $option => list( $handle, $file ) ) {
			if ( Settings::isLayoutEnabled( $option ) ) {
				wp_enqueue_style( $handle, self::url( $file ), array( self::TOKENS_HANDLE ), Constants::SPREADSHOP_VERSION );
			}
		}

		self::enqueueScripts();
	}

	/**
	 * Loads the scripts behind the sidebar (category tree, header button names), the product
	 * cards and the product page, with their translations. The compact footer is CSS only.
	 *
	 * @return void
	 */
	private static function enqueueScripts() {
		$hasNavigation = Settings::isLayoutEnabled( 'spreadshopLayoutSidebar' );
		$hasCards      = Settings::isLayoutEnabled( 'spreadshopLayoutCards' );
		$hasProduct    = Settings::isLayoutEnabled( 'spreadshopLayoutProductPage' );
		if ( ! $hasNavigation && ! $hasCards && ! $hasProduct ) {
			return;
		}

		$js        = plugins_url( 'js/', SPREADSHOP_FILE );
		$languages = plugin_dir_path( SPREADSHOP_FILE ) . 'languages';
		wp_enqueue_script( self::OBSERVE_HANDLE, $js . 'layout-observe.js', array(), Constants::SPREADSHOP_VERSION, true );

		if ( $hasNavigation ) {
			wp_enqueue_script( self::NAV_HANDLE, $js . 'layout-nav.js', array( 'wp-i18n', self::OBSERVE_HANDLE ), Constants::SPREADSHOP_VERSION, true );
			wp_set_script_translations( self::NAV_HANDLE, 'spreadshop', $languages );
		}

		if ( $hasCards ) {
			wp_enqueue_script( self::DATA_HANDLE, $js . 'layout-data.js', array(), Constants::SPREADSHOP_VERSION, true );
			wp_enqueue_script( self::CARDS_HANDLE, $js . 'layout.js', array( 'wp-i18n', self::OBSERVE_HANDLE, self::DATA_HANDLE ), Constants::SPREADSHOP_VERSION, true );
			wp_add_inline_script(
				self::CARDS_HANDLE,
				'window.spreadshopLayoutConfig = ' . wp_json_encode( array( 'cardFields' => Settings::cardFields() ) ) . ';',
				'before'
			);
			wp_set_script_translations( self::CARDS_HANDLE, 'spreadshop', $languages );
		}

		if ( $hasProduct ) {
			wp_enqueue_script( self::PRODUCT_HANDLE, $js . 'layout-product.js', array( self::OBSERVE_HANDLE ), Constants::SPREADSHOP_VERSION, true );
		}
	}

	/**
	 * Public URL of a layout stylesheet.
	 *
	 * @param string $file File name under style/layout/.
	 * @return string
	 */
	private static function url( $file ) {
		return plugins_url( 'style/layout/' . $file, SPREADSHOP_FILE );
	}
}
