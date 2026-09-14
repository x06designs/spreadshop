<?php
/**
 * Plugin Name: Spreadshop (Maintained Fork)
 * Plugin URI: https://github.com/x-06-designs/spreadshop
 * Description: This plugin integrates a Spreadshirt Shop into WordPress. Community-maintained fork of the discontinued official plugin.
 * Version: 1.7.0
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Text Domain: spreadshop
 * Domain Path: /languages
 * Author: X-06 Designs
 * Author URI: https://github.com/x-06-designs
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

require 'spreadshop-constants.php';
require 'spreadshop-admin.php';
add_action( 'admin_menu', 'spreadshopAdminMenu' );
add_action( 'admin_init', 'spreadshopRegisterSettings' );
add_action( 'activated_plugin', 'spreadshopActivationRedirect' );
add_filter( 'template_include', 'spreadshopPageTemplate', 99 );
register_uninstall_hook( __FILE__, 'spreadshopDeleteSettings' );
add_shortcode( 'spreadshop', 'spreadshopShortcode' );
add_action( 'init', 'spreadshopLoadTextdomain' );

/**
 * Loads the plugin's translations.
 *
 * Bound to init rather than called at file load: translating earlier than that triggers
 * WordPress 6.7's _load_textdomain_just_in_time notice.
 *
 * @return void
 */
function spreadshopLoadTextdomain() {
	load_plugin_textdomain( 'spreadshop', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/**
 * Registers every option with the settings API, so the admin forms can nonce against them.
 *
 * @return void
 */
function spreadshopRegisterSettings() {
	foreach ( SpreadshopConstants::SPREADSHOP_OPTIONS as $option ) {
		register_setting( SpreadshopConstants::SPREADSHOP_SETTINGS_GROUP, $option );
	}
}

/**
 * Adds the Spreadshop entry to the admin menu.
 *
 * @return void
 */
function spreadshopAdminMenu() {
	add_menu_page(
		'Spreadshop',
		'Spreadshop',
		'manage_options',
		'Spreadshop',
		'spreadshopAdminHandler',
		plugin_dir_url( __FILE__ ) . 'style/images/sprd_icon.png',
		99
	);
}

/**
 * Sends the admin to the setup screen after this plugin is activated.
 *
 * @param string $fileName Plugin file that was activated; this hook fires for every plugin.
 * @return void
 */
function spreadshopActivationRedirect( $fileName ) {
	// $fileName.endsWith('spreadshop.php')
	// this is called every time any plugin is activated. from the filename, we guess whether it was ours and redirect in that case only.
	if ( ! $fileName || substr_compare( $fileName, 'spreadshop.php', -strlen( 'spreadshop.php' ) ) !== 0 ) {
		return;
	}
	// A redirect is only meaningful for a single activation performed by a human in wp-admin.
	// Bulk activation would abandon the remaining plugins, and WP-CLI or cron have nowhere to go.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Presence check only, nothing is written.
	if ( isset( $_GET['activate-multi'] ) || ( defined( 'WP_CLI' ) && WP_CLI ) || wp_doing_cron() ) {
		return;
	}
	wp_safe_redirect( admin_url( 'admin.php?page=Spreadshop' ) );
	exit;
}

/**
 * Drops every stored setting. Runs on uninstall, and when the admin disconnects the shop.
 * Deliberately not bound to deactivation: that would discard the configuration whenever
 * the plugin is switched off for maintenance or during an update.
 *
 * @return void
 */
function spreadshopDeleteSettings() {
	foreach ( SpreadshopConstants::SPREADSHOP_OPTIONS as $option ) {
		delete_option( $option );
	}
}

/**
 * Renders Spreadshop into each page having the [spreadshop] short code.
 *
 * @param array<string, string>|string $atts Shortcode attributes; 'deeplink' opens a specific shop page.
 * @return string The embed markup, or an empty string if the shop was already rendered on this page.
 */
function spreadshopShortcode( $atts ) {
	// Shortcode may only run one time.
	static $alreadyRun = false;
	if ( $alreadyRun ) {
		return '';
	}
	$alreadyRun = true;
	require_once plugin_dir_path( __FILE__ ) . 'spreadshop-embed.php';
	$startToken = isset( $atts['deeplink'] ) ? $atts['deeplink'] : null;
	return spreadshopEmbed( null, $startToken );
}

/**
 * Renders Spreadshop to the page matching the configured slug.
 *
 * @param string $template Template WordPress resolved for this request.
 * @return string Our embed template when the request matches the configured slug, else $template.
 */
function spreadshopPageTemplate( $template ) {
	$ourSlug = get_option( 'spreadshopSlug' );
	if ( $ourSlug && isset( $_SERVER['REQUEST_URI'] ) ) {
		$homeUrl     = wp_parse_url( get_home_url() );
		$ourBasePath = ( isset( $homeUrl['path'] ) ? $homeUrl['path'] : '' ) . '/' . trim( $ourSlug, " \t\n\r\0\x0B/" );
		// esc_url_raw, not sanitize_text_field: the latter strips percent-encoded octets,
		// which would break the comparison for any slug containing encoded characters.
		$requestUri  = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$requestPath = strtok( strtok( $requestUri, '#' ), '?' ); // remove query and hashbang parts
		// We render our template if we either:
		// (A) got an exact match with the request path or
		// (B) pushState urls are enabled and the request path starts with the path configured to be spreadshop-embedding.
		// The pushState clause matches on a whole path segment. A bare prefix test would let a
		// slug of "shop" also swallow "/shopping-cart" and every other page that merely starts
		// with those letters.
		if ( $ourBasePath === $requestPath || $ourBasePath . '/' === $requestPath || ( get_option( 'spreadshopOptimizeUrl' ) && strpos( $requestPath, $ourBasePath . '/' ) === 0 ) ) {
			return plugin_dir_path( __FILE__ ) . 'spreadshop-embed-as-template.php';
		}
	}

	return $template;
}
