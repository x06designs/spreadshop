<?php
/**
 * The layout options: cleaning, defaults and how the embed applies them.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Tests\Unit;

use Brain\Monkey\Functions;
use Spreadshop\Embed\Renderer;
use Spreadshop\Layout\Schema;
use Spreadshop\Settings;

/**
 * Covers the layout sanitisers, their read accessors, and the Renderer's use of them.
 *
 * The colour map is the one to be strict about: its values are printed into a stylesheet, so
 * anything a sanitiser lets through is a CSS injection.
 */
class LayoutSettingsTest extends PluginTestCase {

	/**
	 * Stubs get_option() with the given stored rows; anything else is absent.
	 *
	 * @param array<string, mixed> $rows Stored option values.
	 * @return void
	 */
	private function givenStored( array $rows ) {
		$rows = array_merge(
			array(
				'spreadshopID'       => '1376884',
				'spreadshopPlatform' => 'EU',
			),
			$rows
		);
		Functions\when( 'get_option' )->alias(
			static function ( $name, $fallback = false ) use ( $rows ) {
				return array_key_exists( $name, $rows ) ? $rows[ $name ] : $fallback;
			}
		);
		Functions\when( 'wp_json_encode' )->alias( static fn( $d ) => json_encode( $d ) );
	}

	/**
	 * Known ids survive in the order given.
	 *
	 * @return void
	 */
	public function testCardFieldsKeepTheirOrder() {
		$this->assertSame(
			array( 'sizes', 'price', 'productType' ),
			Settings::sanitizeCardFields( array( 'sizes', 'price', 'productType' ) )
		);
	}

	/**
	 * Fields drawn on the image always move to the end.
	 *
	 * @return void
	 */
	public function testFieldsOnTheImageAreKeptLast() {
		$this->assertSame(
			array( 'price', 'swatches', 'hoverImage' ),
			Settings::sanitizeCardFields( array( 'hoverImage', 'price', 'swatches' ) )
		);
	}

	/**
	 * The embroidery badge was a field before 1.9.0 shipped; a stored value is dropped on read.
	 *
	 * @return void
	 */
	public function testTheRetiredBadgeFieldIsDropped() {
		$this->assertSame(
			array( 'price', 'hoverImage' ),
			Settings::sanitizeCardFields( array( 'stickBadge', 'price', 'hoverImage' ) )
		);
	}

	/**
	 * Unknown ids, duplicates, non-strings and the form's empty sentinel are dropped.
	 *
	 * @return void
	 */
	public function testCardFieldsDropWhatIsNotAKnownField() {
		$this->assertSame(
			array( 'price', 'sizes' ),
			Settings::sanitizeCardFields(
				array( '', 'price', 'price', 'name', 'evil', 3, array( 'sizes' ), null, 'sizes' )
			)
		);
	}

	/**
	 * An empty selection is a real choice and is kept as one.
	 *
	 * @return void
	 */
	public function testAnEmptySelectionStaysEmpty() {
		$this->assertSame( array(), Settings::sanitizeCardFields( array( '' ) ) );
		$this->assertSame( array(), Settings::sanitizeCardFields( array() ) );
	}

	/**
	 * Something that is not a list means nothing was submitted: the default applies.
	 *
	 * @dataProvider notAListProvider
	 * @param mixed $value What arrived.
	 * @return void
	 */
	public function testCardFieldsFallBackToTheDefaultWhenAbsent( $value ) {
		$this->assertSame( array( 'price', 'swatches', 'hoverImage' ), Settings::sanitizeCardFields( $value ) );
	}

	/**
	 * Values that are not a list.
	 *
	 * @return array<string, array{mixed}>
	 */
	public function notAListProvider() {
		return array(
			'null, as options.php sends an absent field' => array( null ),
			'a string'                                   => array( 'price' ),
			'false, as get_option returns for no row'    => array( false ),
		);
	}

	/**
	 * Every sanitiser gives the same answer for its own output, since update_option re-runs it.
	 *
	 * @return void
	 */
	public function testTheLayoutSanitisersAreIdempotent() {
		$fields = Settings::sanitizeCardFields( array( 'hoverImage', 'sizes', 'bogus', 'sizes' ) );
		$this->assertSame( $fields, Settings::sanitizeCardFields( $fields ) );

		$colors = Settings::sanitizeColors( array( 'accent' => ' #039D9F ' ) );
		$this->assertSame( $colors, Settings::sanitizeColors( $colors ) );

		$list = Settings::sanitizeStartList( 'products' );
		$this->assertSame( $list, Settings::sanitizeStartList( $list ) );
	}

	/**
	 * Only the two start lists are accepted; anything else is the default.
	 *
	 * @return void
	 */
	public function testTheStartListIsOneOfTwo() {
		$this->assertSame( 'products', Settings::sanitizeStartList( 'products' ) );
		$this->assertSame( 'designs', Settings::sanitizeStartList( 'designs' ) );
		$this->assertSame( 'designs', Settings::sanitizeStartList( 'PRODUCTS' ) );
		$this->assertSame( 'designs', Settings::sanitizeStartList( array( 'products' ) ) );
		$this->assertSame( 'designs', Settings::sanitizeStartList( null ) );
	}

	/**
	 * A well-formed colour for a known token is kept, normalised to lower case.
	 *
	 * @return void
	 */
	public function testAValidColourIsKept() {
		$this->assertSame(
			array(
				'accent' => '#039d9f',
				'text'   => '#0d130f',
			),
			Settings::sanitizeColors(
				array(
					'accent' => '#039D9F',
					'text'   => '#0d130f',
				)
			)
		);
	}

	/**
	 * Surrounding whitespace, as a pasted value carries, does not cost the colour.
	 *
	 * @return void
	 */
	public function testAPaddedColourIsTrimmed() {
		$this->assertSame(
			array( 'accent' => '#aabbcc' ),
			Settings::sanitizeColors( array( 'accent' => " #AABBCC \n" ) )
		);
	}

	/**
	 * Asking the schema for an option it does not define is a developer error, not a default.
	 *
	 * @return void
	 */
	public function testAnUnknownSchemaPropertyThrows() {
		$this->expectException( \RuntimeException::class );
		Schema::property( 'spreadshopNoSuchOption' );
	}

	/**
	 * Anything that could close the declaration, the rule or the style tag is dropped.
	 *
	 * @dataProvider hostileColourProvider
	 * @param mixed $value The submitted colour map.
	 * @return void
	 */
	public function testAColourThatCouldEscapeTheStylesheetIsDropped( $value ) {
		$this->assertSame( array(), Settings::sanitizeColors( $value ) );
	}

	/**
	 * Colour maps that must never reach Tokens::render().
	 *
	 * @return array<string, array{mixed}>
	 */
	public function hostileColourProvider() {
		return array(
			'closing the rule'      => array( array( 'accent' => '#fff;}body{display:none' ) ),
			'closing the style tag' => array( array( 'accent' => '</style><script>alert(1)</script>' ) ),
			'a trailing newline'    => array( array( 'accent' => "#039d9f\n}" ) ),
			'three digits'          => array( array( 'accent' => '#fff' ) ),
			'a named colour'        => array( array( 'accent' => 'red' ) ),
			'a css function'        => array( array( 'accent' => 'url(https://evil.example)' ) ),
			'an unknown token'      => array( array( 'accent;}body{x:y' => '#039d9f' ) ),
			'a nested array'        => array( array( 'accent' => array( '#039d9f' ) ) ),
			'not an array at all'   => array( '#039d9f' ),
			'null from options.php' => array( null ),
		);
	}

	/**
	 * A 1.8.1 install has none of the new rows: every accessor answers with the default.
	 *
	 * @return void
	 */
	public function testAnUpgradedInstallReadsTheDefaults() {
		$this->givenStored( array() );

		$this->assertFalse( Settings::isAnyLayoutEnabled() );
		$this->assertSame( array( 'price', 'swatches', 'hoverImage' ), Settings::cardFields() );
		$this->assertSame( 'designs', Settings::startList() );
		$this->assertSame( array(), Settings::colors() );
	}

	/**
	 * A tampered row is cleaned on the way out as well as on the way in.
	 *
	 * @return void
	 */
	public function testAccessorsCleanWhatIsStored() {
		$this->givenStored(
			array(
				'spreadshopCardFields' => array( 'evil', 'price' ),
				'spreadshopStartList'  => 'evil',
				'spreadshopColors'     => array( 'accent' => '#fff;}' ),
			)
		);

		$this->assertSame( array( 'price' ), Settings::cardFields() );
		$this->assertSame( 'designs', Settings::startList() );
		$this->assertSame( array(), Settings::colors() );
	}

	/**
	 * With every toggle off the wrapper is exactly what 1.8.1 rendered.
	 *
	 * @return void
	 */
	public function testTheWrapperIsUnchangedWithEveryToggleOff() {
		$this->givenStored( array() );

		$this->assertStringContainsString( '<div id="primary" class="content-area">', Renderer::render( null, null ) );
	}

	/**
	 * Each toggle adds its own class, and only its own.
	 *
	 * @return void
	 */
	public function testEachToggleAddsItsWrapperClass() {
		$this->givenStored(
			array(
				'spreadshopLayoutSidebar' => 1,
				'spreadshopLayoutCards'   => 1,
			)
		);

		$this->assertStringContainsString(
			'<div id="primary" class="content-area spreadshop-layout--sidebar spreadshop-layout--cards">',
			Renderer::render( null, null )
		);
	}

	/**
	 * Pulls the start token back out of the rendered config object.
	 *
	 * @param string|null $deeplink Short code deeplink.
	 * @return string
	 */
	private function startToken( $deeplink = null ) {
		preg_match( '/var spread_shop_config = (\{.*?\});/', Renderer::render( null, $deeplink ), $m );
		return json_decode( $m[1], true )['startToken'];
	}

	/**
	 * The product start list opens the product list when nothing more specific is set.
	 *
	 * @return void
	 */
	public function testTheProductStartListOpensTheProductList() {
		$this->givenStored( array( 'spreadshopStartList' => 'products' ) );

		$this->assertSame( '?listModeOverride=PRODUCT', $this->startToken() );
	}

	/**
	 * A configured Start Token wins over the start list.
	 *
	 * @return void
	 */
	public function testAStartTokenWinsOverTheStartList() {
		$this->givenStored(
			array(
				'spreadshopStartList' => 'products',
				'spreadshopToken'     => 'cafe+koenji+logo?idea=5d5534fd2051766bd5973b60',
			)
		);

		$this->assertSame( 'cafe+koenji+logo?idea=5d5534fd2051766bd5973b60', $this->startToken() );
	}

	/**
	 * A short code deeplink wins over both.
	 *
	 * @return void
	 */
	public function testADeeplinkWinsOverEverything() {
		$this->givenStored(
			array(
				'spreadshopStartList' => 'products',
				'spreadshopToken'     => 'configured',
			)
		);

		$this->assertSame( 'from-shortcode', $this->startToken( 'from-shortcode' ) );
	}

	/**
	 * The design list is the shop's own default, so it sends no token at all.
	 *
	 * @return void
	 */
	public function testTheDesignStartListSendsNoToken() {
		$this->givenStored( array( 'spreadshopStartList' => 'designs' ) );

		$this->assertSame( '', $this->startToken() );
	}
}
