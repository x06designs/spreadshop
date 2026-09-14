<?php
/**
 * Hook wiring.
 *
 * @package Spreadshop
 */

namespace Spreadshop;

use Spreadshop\Admin\AdminPage;
use Spreadshop\Embed\Shortcode;
use Spreadshop\Embed\SlugRoute;

defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 * The only place hooks are registered.
 */
class Plugin {

	/**
	 * Registers everything the plugin does.
	 *
	 * @param string $pluginFile Absolute path to the main plugin file.
	 * @return void
	 */
	public static function boot( $pluginFile ) {
		add_action( 'init', array( __CLASS__, 'loadTextdomain' ) );
		add_action( 'admin_menu', array( AdminPage::class, 'registerMenu' ) );
		add_action( 'admin_init', array( Settings::class, 'registerAll' ) );
		add_action( 'activated_plugin', array( __CLASS__, 'redirectAfterActivation' ) );
		// Priority 1: the request has to stop being a 404 before any SEO plugin reads it.
		add_action( 'wp', array( SlugRoute::class, 'claimRequest' ), 1 );
		add_filter( 'template_include', array( SlugRoute::class, 'filterTemplate' ), 99 );
		add_shortcode( 'spreadshop', array( Shortcode::class, 'render' ) );

		/*
		 * The callback NAME is persisted in the uninstall_plugins option, so it has to stay a
		 * global function that exists under that exact name. See the shim in spreadshop.php.
		 */
		register_uninstall_hook( $pluginFile, 'spreadshopDeleteSettings' );
	}

	/**
	 * Loads the plugin's translations.
	 *
	 * Bound to init rather than called at file load: translating earlier than that triggers
	 * WordPress 6.7's _load_textdomain_just_in_time notice.
	 *
	 * @return void
	 */
	public static function loadTextdomain() {
		load_plugin_textdomain( 'spreadshop', false, dirname( plugin_basename( SPREADSHOP_FILE ) ) . '/languages' );
	}

	/**
	 * Sends the admin to the setup screen after this plugin is activated.
	 *
	 * @param string $fileName Plugin file that was activated; this hook fires for every plugin.
	 * @return void
	 */
	public static function redirectAfterActivation( $fileName ) {
		// This fires for every plugin, so the filename is what identifies ours.
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
}
