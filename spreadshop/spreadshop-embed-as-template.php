<?php
require_once plugin_dir_path(__FILE__) . 'spreadshop-embed.php';

// This is a template file that gets executed in slug based integrations only.

function setSpreadshopTitle() {
    $title['title'] = get_option('spreadshopSlug');
    return $title;
}

// the yoast seo plugin sets "noindex, follow" on every page where is_404() is true.
// this also affects our slug based pages. this hook disabled that behavior.
function yoast_seo_plugin_avoid_noindex() {
    return false;
}

add_filter('document_title_parts', 'setSpreadshopTitle', 10, 2);
add_filter('wpseo_robots', 'yoast_seo_plugin_avoid_noindex');
status_header(200);

get_header();
$pushStateBaseUrl = null;
if (get_option('spreadshopOptimizeUrl')) {
    $pushStateBaseUrl = rtrim(get_home_url(), '/') . '/' . trim(get_option('spreadshopSlug'), " \t\n\r\0\x0B/");
}
echo spreadshopEmbed($pushStateBaseUrl, null);

get_footer();

