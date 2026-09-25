<?php
/**
 * Plugin Name: Spreadshop (Maintained Fork)
 * Plugin URI: https://github.com/x06designs/spreadshop
 * Description: This plugin integrates a Spreadshirt Shop into WordPress. Community-maintained fork of the discontinued official plugin.
 * Version: 1.9.0
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Text Domain: spreadshop
 * Domain Path: /languages
 * Author: X-06 Designs
 * Author URI: https://github.com/x06designs
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * Originally written by Robert Schulz (sprd.net AG, https://www.spreadshop.com) and
 * Stefan Drehmann (IronShark GmbH, https://www.ironshark.de).
 * Copyright (C) sprd.net AG and IronShark GmbH.
 *
 * This is an independent fork of that plugin, which its original authors last released
 * in February 2024. Modified from version 1.7.0 onward by X-06 Designs, 2026: the shop
 * connection, request handling, setting persistence, admin markup and url routing were
 * changed. See the changelog in README.md for the full list.
 *
 * Spreadshop and Spreadshirt are trademarks of sprd.net AG. This fork is not endorsed
 * by, affiliated with, or supported by sprd.net AG or IronShark GmbH.
 *
 * The Spreadshop plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * The Spreadshop plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with the Spreadshop plugin. If not, see https://www.gnu.org/licenses/old-licenses/gpl-2.0.html.
 *
 * @package Spreadshop
 */

defined( 'ABSPATH' ) || exit;

define( 'SPREADSHOP_FILE', __FILE__ );

require_once __DIR__ . '/includes/Autoloader.php';
\Spreadshop\Autoloader::register( __DIR__ . '/includes' );

\Spreadshop\Plugin::boot( __FILE__ );

/**
 * Removes every stored setting on uninstall.
 *
 * WordPress persists the callback NAME: register_uninstall_hook() writes it into the
 * uninstall_plugins option, and calls whatever name it finds there. A site last active on an older
 * version still has this exact name stored, so it has to keep existing as a global function
 * even though the work now lives in Settings::deleteAll(). Renaming it would leave those
 * sites unable to uninstall cleanly.
 *
 * @return void
 */
function spreadshopDeleteSettings() {
	\Spreadshop\Settings::deleteAll();
}
