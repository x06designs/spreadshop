<?php
/**
 * Bootstrap for the unit suite.
 *
 * These tests run without WordPress: Brain Monkey stands in for the functions the plugin
 * calls. The plugin's own autoloader is used rather than a test-only one, so a class that
 * cannot be autoloaded in production fails here too.
 *
 * @package Spreadshop
 */

// The plugin files refuse to load without it; every one of them guards on it.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/spreadshop/' );
}

// Normally defined by the bootstrap in spreadshop.php.
if ( ! defined( 'SPREADSHOP_FILE' ) ) {
	define( 'SPREADSHOP_FILE', dirname( __DIR__ ) . '/spreadshop/spreadshop.php' );
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once dirname( __DIR__ ) . '/spreadshop/includes/Autoloader.php';

Spreadshop\Autoloader::register( dirname( __DIR__ ) . '/spreadshop/includes' );
