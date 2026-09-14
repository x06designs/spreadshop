<?php
/**
 * The plugin header and the values derived from it.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Tests\Unit;

use Spreadshop\Constants;

/**
 * Covers the plugin header.
 *
 * The version lives in two places -- the header WordPress reads, and the constant the code
 * reports to Spreadshirt and uses to bust the stylesheet cache. Nothing keeps them in step,
 * so this does.
 */
class PluginHeaderTest extends PluginTestCase {

	/**
	 * Reads one header field straight out of the bootstrap file.
	 *
	 * @param string $field Header field name.
	 * @return string|null The value, or null when the field is absent.
	 */
	private function header( $field ) {
		$contents = file_get_contents( dirname( __DIR__, 2 ) . '/spreadshop/spreadshop.php' );

		if ( preg_match( '/^\s*\*\s*' . preg_quote( $field, '/' ) . ':\s*(.+)$/mi', $contents, $m ) ) {
			return trim( $m[1] );
		}

		return null;
	}

	/**
	 * The header version and the constant cannot drift apart unnoticed.
	 *
	 * @return void
	 */
	public function testTheHeaderVersionMatchesTheConstant() {
		$this->assertSame( $this->header( 'Version' ), Constants::SPREADSHOP_VERSION );
	}

	/**
	 * The text domain has to equal the folder slug or WordPress loads no translations.
	 *
	 * @return void
	 */
	public function testTheTextDomainMatchesTheFolderSlug() {
		$this->assertSame( 'spreadshop', $this->header( 'Text Domain' ) );
	}

	/**
	 * The declared PHP floor is the one the connection actually needs.
	 *
	 * Spreadshirt's edge refuses older TLS stacks, so anything below 8.1 cannot complete
	 * setup however correct the rest of the request is.
	 *
	 * @return void
	 */
	public function testThePhpFloorIsDeclared() {
		$this->assertSame( '8.1', $this->header( 'Requires PHP' ) );
	}

	/**
	 * Attribution required by the GPL has to stay in the file it is shipped in.
	 *
	 * @return void
	 */
	public function testTheOriginalAuthorsAreStillCredited() {
		$contents = file_get_contents( dirname( __DIR__, 2 ) . '/spreadshop/spreadshop.php' );

		$this->assertStringContainsString( 'sprd.net AG', $contents );
		$this->assertStringContainsString( 'IronShark GmbH', $contents );
		$this->assertStringContainsString( 'GNU General Public License', $contents );
	}

	/**
	 * Both places the languages directory is named have to agree.
	 *
	 * @return void
	 */
	public function testTheDomainPathMatchesTheShippedDirectory() {
		$this->assertSame( '/languages', $this->header( 'Domain Path' ) );
		$this->assertDirectoryExists( dirname( __DIR__, 2 ) . '/spreadshop/languages' );
	}
}
