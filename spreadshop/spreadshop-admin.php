<?php
/**
 * Entry point and shared helpers for the plugin's admin screen.
 *
 * @package Spreadshop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether a shop has been linked.
 *
 * Versions before 1.6 stored the literal string "undefined" instead of clearing the option,
 * so an install upgraded from one of those still has to be read as disconnected.
 *
 * @return bool True when a shop id is stored.
 */
function spreadshopIsConnected() {
	$shopId = get_option( 'spreadshopID', 'undefined' );
	return ! empty( $shopId ) && $shopId !== 'undefined';
}

/**
 * Entry point for the admin section.
 *
 * @return void
 */
function spreadshopAdminHandler() {
	require_once plugin_dir_path( __FILE__ ) . 'admin/spreadshop-admin-connect.php';
	require_once plugin_dir_path( __FILE__ ) . 'admin/spreadshop-admin-advanced.php';
	require_once plugin_dir_path( __FILE__ ) . 'admin/spreadshop-admin-frame.php';

	// The tab is a navigation hint only; nothing is written before the nonce check inside handle().
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab selection.
	$inAdvancedTab = isset( $_GET['tab'] ) && $_GET['tab'] === 'advanced';
	$isConnected   = spreadshopIsConnected();
	$renderData    = $inAdvancedTab ? SpreadshopAdminAdvanced::handle( $isConnected ) : SpreadshopAdminConnect::handle( $isConnected );
	$isConnected   = spreadshopIsConnected(); // Refreshed, because handle() may have connected or disconnected the shop.

	wp_enqueue_style(
		'spreadShopOptionsStyle',
		plugins_url( 'style/style.css', __FILE__ ),
		array(),
		SpreadshopConstants::SPREADSHOP_VERSION
	);
	SpreadshopAdminFrame::renderTop( $inAdvancedTab, $isConnected );
	$inAdvancedTab ? SpreadshopAdminAdvanced::render( $renderData ) : SpreadshopAdminConnect::render( $renderData );
	SpreadshopAdminFrame::renderBottom( $isConnected );
}
