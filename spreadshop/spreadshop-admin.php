<?php

function isSpreadshopConnected() {
    $shopId = get_option('spreadshopID', 'undefined');
    return !empty($shopId) && $shopId !== 'undefined'; // backwards compatibility
}

/**
 * Entry point for the admin section.
 */
function spreadshopAdminHandler() {
    require_once plugin_dir_path(__FILE__) . 'admin/spreadshop-admin-connect.php';
    require_once plugin_dir_path(__FILE__) . 'admin/spreadshop-admin-advanced.php';
    require_once plugin_dir_path(__FILE__) . 'admin/spreadshop-admin-frame.php';

    $inAdvancedTab = array_key_exists('tab', $_GET) && $_GET['tab'] === 'advanced';
    $isConnected = isSpreadshopConnected();
    $renderData = $inAdvancedTab ? SpreadshopAdminAdvanced::handle($isConnected) : SpreadshopAdminConnect::handle($isConnected);
    $isConnected = isSpreadshopConnected(); // refreshing this after possible change

    wp_enqueue_style('spreadShopOptionsStyle', plugins_url('style/style.css', __FILE__));
    SpreadshopAdminFrame::renderTop($inAdvancedTab, $isConnected);
    $inAdvancedTab ? SpreadshopAdminAdvanced::render($renderData) : SpreadshopAdminConnect::render($renderData);
    SpreadshopAdminFrame::renderBottom($isConnected);
}
