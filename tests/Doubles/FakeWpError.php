<?php
/**
 * Stand-in for WP_Error.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Tests\Doubles;

/**
 * Class FakeWpError
 *
 * The unit suite runs without WordPress, so WP_Error does not exist. Only the one method the
 * plugin calls on it is needed.
 */
class FakeWpError {

	/**
	 * The error message.
	 *
	 * @var string
	 */
	private $message;

	/**
	 * Constructor.
	 *
	 * @param string $message The error message.
	 */
	public function __construct( $message ) {
		$this->message = $message;
	}

	/**
	 * Returns the error message.
	 *
	 * @return string
	 */
	public function get_error_message() {
		return $this->message;
	}
}
