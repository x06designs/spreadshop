<?php
/**
 * Returns render output for the spread_shop_config object, a placeholder div and links the shopclient.nocache.js script, thus performing the actual integration.
 * This function is used in short code as well as slug based integrations.
 */
function spreadshopEmbed($pushStateBaseUrl, $startTokenOverride) {
    $tld = get_option('spreadshopPlatform') == 'EU' ? 'net' : 'com';
    $shopId = get_option('spreadshopID');
    $config_array = array(
        'shopName' => $shopId,
        'prefix' => 'https://' . $shopId . '.myspreadshop.' . $tld,
        'baseId' => 'myShop',
        'locale' => get_option('spreadshopLocale', ''),
        'startToken' => $startTokenOverride ? $startTokenOverride : get_option('spreadshopToken', ''),
        'usePushState' => (bool)$pushStateBaseUrl,
        'pushStateBaseUrl' => $pushStateBaseUrl,
        'updateMetadata' => (bool)get_option('spreadshopMetadata', false),
        'swipeMenu' => (bool)get_option('spreadshopSwipeMenu', false),
        'loadFonts' => (bool)get_option('spreadshopLoadFonts', false),
        'integrationProvider' => 'Spreadshirt Wordpress plugin v1.6.6',
    );

    $output = '';
    $output .= '<script type="text/javascript">';
    $output .= '    var spread_shop_config = ' . wp_json_encode($config_array) . ';';
    $output .= '</script>';
    $output .= '<div id="primary" class="content-area">';
    $output .= '    <main id="main" class="site-main">';
    $output .= '        <div id="myShop"></div>';
    $output .= '    </main>';
    $output .= '</div>';
    $output .= '<script src="' . 'https://' . $shopId . '.myspreadshop.' . $tld . '/shopfiles/shopclient/shopclient.nocache.js"></script>';
    return $output;
}
