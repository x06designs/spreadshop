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
			self::menuIcon(),
			99
		);
	}

	/**
	 * The menu icon, inlined as a data uri.
	 *
	 * Inlined rather than linked so it costs no request, and SVG rather than the 20x20 png it
	 * replaces so it stays sharp on a high-density display. WordPress does not recolour a
	 * data-uri icon to match the admin colour scheme, so this keeps the brand orange the png
	 * always had.
	 *
	 * @return string
	 */
	private static function menuIcon() {
		$svg = file_get_contents( plugin_dir_path( SPREADSHOP_FILE ) . 'style/images/sprd_icon.svg' );

		if ( false === $svg ) {
			return 'dashicons-cart';
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Data uri, not obfuscation.
		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
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
		wp_enqueue_script(
			'spreadshopAdminScript',
			plugins_url( 'js/admin.js', SPREADSHOP_FILE ),
			array(),
			Constants::SPREADSHOP_VERSION,
			true
		);
		if ( $inAdvancedTab && $isConnected ) {
			self::enqueueLayoutAssets();
		}
		Frame::renderTop( $inAdvancedTab, $isConnected );
		$inAdvancedTab ? AdvancedTab::render( $renderData ) : ConnectTab::render( $renderData );
		Frame::renderBottom( $isConnected );
	}

	/**
	 * Loads what the Layout section needs: core's colour picker and the card-field chip list.
	 *
	 * @return void
	 */
	private static function enqueueLayoutAssets() {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script(
			'spreadshopAdminColors',
			plugins_url( 'js/admin-colors.js', SPREADSHOP_FILE ),
			array( 'wp-color-picker' ),
			Constants::SPREADSHOP_VERSION,
			true
		);
		wp_enqueue_script(
			'spreadshopAdminCardFields',
			plugins_url( 'js/admin-card-fields.js', SPREADSHOP_FILE ),
			array( 'wp-i18n' ),
			Constants::SPREADSHOP_VERSION,
			true
		);
		wp_set_script_translations(
			'spreadshopAdminCardFields',
			'spreadshop',
			plugin_dir_path( SPREADSHOP_FILE ) . 'languages'
		);
	}
}
