<?php
/**
 * The plugin's admin screen.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Admin;

use Spreadshop\Constants;
use Spreadshop\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdminPage
 * Owns the menu entry and decides which tab handles the request.
 */
class AdminPage {

	/**
	 * Adds the Spreadshop entry to the admin menu.
	 *
	 * @return void
	 */
	public static function registerMenu() {
		add_menu_page(
			'Spreadshop',
			'Spreadshop',
			'manage_options',
			'Spreadshop',
			array( __CLASS__, 'render' ),
			plugins_url( 'style/images/sprd_icon.png', SPREADSHOP_FILE ),
			99
		);
	}

	/**
	 * Entry point for the admin section.
	 *
	 * @return void
	 */
	public static function render() {
		// The tab is a navigation hint only; nothing is written before the nonce check inside handle().
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab selection.
		$inAdvancedTab = isset( $_GET['tab'] ) && $_GET['tab'] === 'advanced';
		$isConnected   = Settings::isConnected();
		$renderData    = $inAdvancedTab ? AdvancedTab::handle( $isConnected ) : ConnectTab::handle( $isConnected );
		$isConnected   = Settings::isConnected(); // Refreshed, because handle() may have connected or disconnected the shop.

		wp_enqueue_style(
			'spreadShopOptionsStyle',
			plugins_url( 'style/style.css', SPREADSHOP_FILE ),
			array(),
			Constants::SPREADSHOP_VERSION
		);
		Frame::renderTop( $inAdvancedTab, $isConnected );
		$inAdvancedTab ? AdvancedTab::render( $renderData ) : ConnectTab::render( $renderData );
		Frame::renderBottom( $isConnected );
	}
}
