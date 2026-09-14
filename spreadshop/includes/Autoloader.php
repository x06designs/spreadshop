<?php
/**
 * PSR-4 autoloading for the plugin's own classes.
 *
 * @package Spreadshop
 */

namespace Spreadshop;

defined( 'ABSPATH' ) || exit;

/**
 * Class Autoloader
 *
 * Hand-rolled rather than Composer's, deliberately: the plugin has no runtime dependencies,
 * and keeping it that way means the shipped directory needs no vendor/ and a plain download
 * of this repository activates without a build step.
 */
class Autoloader {

	/**
	 * Namespace prefix this autoloader answers for, including the trailing separator.
	 *
	 * @var string
	 */
	const PREFIX = 'Spreadshop\\';

	/**
	 * Registers the autoloader.
	 *
	 * @param string $baseDir Directory the namespace root maps onto.
	 * @return void
	 */
	public static function register( $baseDir ) {
		$baseDir = rtrim( $baseDir, '/\\' ) . '/';

		spl_autoload_register(
			static function ( $className ) use ( $baseDir ) {
				if ( strpos( $className, self::PREFIX ) !== 0 ) {
					return;
				}

				$relative = substr( $className, strlen( self::PREFIX ) );
				$path     = $baseDir . str_replace( '\\', '/', $relative ) . '.php';

				// Never let a crafted class name walk out of the plugin directory.
				if ( strpos( $relative, '..' ) !== false ) {
					return;
				}

				if ( is_readable( $path ) ) {
					require_once $path;
				}
			}
		);
	}
}
