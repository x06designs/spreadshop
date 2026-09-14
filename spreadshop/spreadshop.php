<?php

/*

Plugin Name: Spreadshop
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: This plugin integrates a Spreadshirt Shop into Wordpress.
Version: 1.6.6
Author: Robert Schulz (sprd.net AG)
Co-Author: Stefan Drehmann (IronShark GmbH)
Author URI: https://www.spreadshop.com
Co-Author URI: https://www.ironshark.de
License: GPL2
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

The Spreadshop plugin is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

The Spreadshop plugin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with the Spreadshop plugin. If not, see https://www.gnu.org/licenses/old-licenses/gpl-2.0.html.

*/

include 'spreadshop-admin.php';
add_action('admin_menu', 'spreadshopAdminMenu');
add_action('admin_init', 'spreadshopRegisterSettings');
add_action('activated_plugin', 'spreadshopActivationRedirect');
add_filter('template_include', 'spreadshopPageTemplate', 99);
register_deactivation_hook(__FILE__, 'spreadshopDeactivate');
add_shortcode('spreadshop', 'spreadshopShortcode');


class SpreadshopConstants {
    const SPREADSHOP_SETTINGS_GROUP = 'spreadshop-settings-group';
}
function spreadshopRegisterSettings() {
    register_setting(SpreadshopConstants::SPREADSHOP_SETTINGS_GROUP, 'spreadshopID');
    register_setting(SpreadshopConstants::SPREADSHOP_SETTINGS_GROUP, 'spreadshopToken');
    register_setting(SpreadshopConstants::SPREADSHOP_SETTINGS_GROUP, 'spreadshopPlatform');
    register_setting(SpreadshopConstants::SPREADSHOP_SETTINGS_GROUP, 'spreadshopSlug');
    register_setting(SpreadshopConstants::SPREADSHOP_SETTINGS_GROUP, 'spreadshopOptimizeUrl');
    register_setting(SpreadshopConstants::SPREADSHOP_SETTINGS_GROUP, 'spreadshopMetadata');
    register_setting(SpreadshopConstants::SPREADSHOP_SETTINGS_GROUP, 'spreadshopSwipeMenu');
    register_setting(SpreadshopConstants::SPREADSHOP_SETTINGS_GROUP, 'spreadshopLocale');
    register_setting(SpreadshopConstants::SPREADSHOP_SETTINGS_GROUP, 'spreadshopNaviEntry');
    register_setting(SpreadshopConstants::SPREADSHOP_SETTINGS_GROUP, 'spreadshopLoadFonts');
}

function spreadshopAdminMenu() {
    add_menu_page('Spreadshop',
        'Spreadshop',
        'manage_options',
        'Spreadshop',
        'spreadshopAdminHandler',
        plugin_dir_url(__FILE__) . 'style/images/sprd_icon.png',
        99);
}

function spreadshopActivationRedirect($fileName) {
    // $fileName.endsWith('spreadshop.php')
    // this is called every time any plugin is activated. from the filename, we guess whether it was ours and redirect in that case only.
    if ($fileName && substr_compare($fileName, 'spreadshop.php', -strlen('spreadshop.php')) === 0) {
        exit(wp_redirect(admin_url('admin.php?page=Spreadshop')));
    }
}

function spreadshopDeactivate() {
    delete_option('spreadshopID');
    delete_option('spreadshopToken');
    delete_option('spreadshopPlatform');
    delete_option('spreadshopSlug');
    delete_option('spreadshopOptimizeUrl');
    delete_option('spreadshopMetadata');
    delete_option('spreadshopSwipeMenu');
    delete_option('spreadshopLocale');
    delete_option('spreadshopLoadFonts');
}

/**
 * Renders Spreadshop into each page having the [spreadshop] short code.
 */
function spreadshopShortcode($atts) {
    // Shortcode may only run one time
    static $already_run = false;
    if (!$already_run) {
        require_once plugin_dir_path(__FILE__) . 'spreadshop-embed.php';
        $startToken = isset($atts['deeplink']) ? $atts['deeplink'] : null;
        return spreadshopEmbed(null, $startToken);
    }
    $already_run = true;
}

/**
 * Renders Spreadshop to the page matching the configured slug.
 */
function spreadshopPageTemplate($template) {
    $ourSlug = get_option('spreadshopSlug');
    if ($ourSlug) {
        $homeUrl = wp_parse_url(get_home_url());
        $ourBasePath = (isset($homeUrl['path']) ? $homeUrl['path'] : '') . '/' . trim($ourSlug, " \t\n\r\0\x0B/");
        $requestPath = strtok(strtok($_SERVER['REQUEST_URI'], '#'), '?'); // remove query and hashbang parts
        // We render our template if we either:
        // (A) got an exact match with the request path or
        // (B) pushState urls are enabled and the request path starts with the path configured to be spreadshop-embedding.
        if ($ourBasePath === $requestPath || $ourBasePath . '/' === $requestPath || (get_option('spreadshopOptimizeUrl') && strpos($requestPath, $ourBasePath) === 0)) {
            return plugin_dir_path(__FILE__) . 'spreadshop-embed-as-template.php';
        }
    }

    return $template;
}
