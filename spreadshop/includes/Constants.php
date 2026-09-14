<?php
/**
 * Shared constants.
 *
 * @package Spreadshop
 */

namespace Spreadshop;

defined( 'ABSPATH' ) || exit;

/**
 * Class Constants
 * Values shared across the admin screen, the embed and the uninstall routine.
 */
class Constants {

	/**
	 * Settings group the admin forms nonce against.
	 *
	 * @var string
	 */
	const SPREADSHOP_SETTINGS_GROUP = 'spreadshop-settings-group';

	/**
	 * Plugin version. Kept in step with the Version header in spreadshop.php.
	 *
	 * @var string
	 */
	const SPREADSHOP_VERSION = '1.7.0';

	/**
	 * Every option this plugin owns.
	 *
	 * These names are a compatibility contract with installs upgrading from earlier
	 * versions: renaming one silently disconnects the shop it belongs to.
	 *
	 * @var string[]
	 */
	const SPREADSHOP_OPTIONS = array(
		'spreadshopID',
		'spreadshopToken',
		'spreadshopPlatform',
		'spreadshopSlug',
		'spreadshopOptimizeUrl',
		'spreadshopMetadata',
		'spreadshopSwipeMenu',
		'spreadshopLocale',
		'spreadshopLoadFonts',
	);
}
