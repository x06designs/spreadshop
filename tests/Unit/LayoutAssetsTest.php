<?php
/**
 * The layout stylesheets and the colour tokens printed with them.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Tests\Unit;

use Brain\Monkey\Functions;
use Spreadshop\Admin\LayoutSection;
use Spreadshop\Embed\Renderer;
use Spreadshop\Layout\Assets;
use Spreadshop\Layout\Tokens;

/**
 * Covers Layout\Assets and Layout\Tokens.
 *
 * Every option off must leave the page exactly as 1.8.1 left it, so most cases are about what
 * is NOT loaded. The token rule is printed into a stylesheet, so it must never carry a value
 * that is not a hex colour, whatever the stored row says.
 */
class LayoutAssetsTest extends PluginTestCase {

	/**
	 * Stylesheet handles enqueued, in order.
	 *
	 * @var string[]
	 */
	private $styles = array();

	/**
	 * Script handles enqueued, in order.
	 *
	 * @var string[]
	 */
	private $scripts = array();

	/**
	 * Source file and dependencies of every enqueued stylesheet and script, by handle.
	 *
	 * @var array<string, array{0: string, 1: string[]}>
	 */
	private $assets = array();

	/**
	 * Script handles translations were registered for.
	 *
	 * @var string[]
	 */
	private $translated = array();

	/**
	 * Where each inline script was placed, by handle.
	 *
	 * @var array<string, string[]>
	 */
	private $positions = array();

	/**
	 * Inline CSS added, by handle.
	 *
	 * @var array<string, string[]>
	 */
	private $inline = array();

	/**
	 * Records enqueues and stubs the WordPress functions the classes reach for.
	 *
	 * @return void
	 */
	protected function set_up() {
		parent::set_up();

		$this->styles     = array();
		$this->scripts    = array();
		$this->inline     = array();
		$this->assets     = array();
		$this->translated = array();
		$this->positions  = array();

		Functions\when( 'plugins_url' )->alias( static fn( $path ) => 'https://example.test/plugins/spreadshop/' . $path );
		Functions\when( 'wp_enqueue_style' )->alias(
			function ( $handle, $src = '', $deps = array() ) {
				$this->styles[]                     = $handle;
				$this->assets[ 'style:' . $handle ] = array( $src, $deps );
			}
		);
		Functions\when( 'wp_enqueue_script' )->alias(
			function ( $handle, $src = '', $deps = array() ) {
				$this->scripts[]                     = $handle;
				$this->assets[ 'script:' . $handle ] = array( $src, $deps );
			}
		);
		Functions\when( 'wp_set_script_translations' )->alias(
			function ( $handle ) {
				$this->translated[] = $handle;
				return true;
			}
		);
		Functions\when( 'wp_json_encode' )->alias( static fn( $d ) => json_encode( $d ) );
		Functions\when( 'wp_add_inline_script' )->alias(
			function ( $handle, $js, $position = 'after' ) {
				$this->inline[ $handle ][]    = $js;
				$this->positions[ $handle ][] = $position;
			}
		);
		Functions\when( 'plugin_dir_path' )->justReturn( '/plugins/spreadshop/' );
		Functions\when( 'wp_add_inline_style' )->alias(
			function ( $handle, $css ) {
				$this->inline[ $handle ][] = $css;
			}
		);
		Functions\when( 'sanitize_hex_color' )->alias(
			static function ( $color ) {
				if ( '' === $color ) {
					return '';
				}
				return preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', (string) $color ) ? $color : null;
			}
		);
		Functions\when( 'is_singular' )->justReturn( true );
		Functions\when( 'has_shortcode' )->justReturn( true );
		$post               = new \WP_Post();
		$post->post_content = '[spreadshop]';
		Functions\when( 'get_post' )->justReturn( $post );
	}

	/**
	 * Stubs get_option() with the given stored rows on a connected shop.
	 *
	 * @param array<string, mixed> $rows Stored option values.
	 * @return void
	 */
	private function givenStored( array $rows ) {
		$rows = array_merge(
			array(
				'spreadshopID'       => '1376884',
				'spreadshopPlatform' => 'EU',
				'spreadshopSlug'     => '',
			),
			$rows
		);
		Functions\when( 'get_option' )->alias(
			static function ( $name, $fallback = false ) use ( $rows ) {
				return array_key_exists( $name, $rows ) ? $rows[ $name ] : $fallback;
			}
		);
	}

	/**
	 * Every toggle the admin offers has a wrapper class and a stylesheet, and nothing else does.
	 *
	 * @return void
	 */
	public function testEveryToggleHasAClassAndAStylesheet() {
		$this->assertSame( LayoutSection::TOGGLES, array_keys( Renderer::LAYOUT_CLASSES ) );
		$this->assertSame( LayoutSection::TOGGLES, array_keys( Assets::STYLESHEETS ) );
		foreach ( Assets::STYLESHEETS as list( , $file ) ) {
			$this->assertFileExists( dirname( __DIR__, 2 ) . '/spreadshop/style/layout/' . $file );
		}
	}

	/**
	 * With every option off, nothing is loaded.
	 *
	 * @return void
	 */
	public function testNothingLoadsWhenEveryOptionIsOff() {
		$this->givenStored( array() );

		Assets::enqueueForEmbed();

		$this->assertSame( array(), $this->styles );
	}

	/**
	 * Only the stylesheets of the options that are on load, after the shared tokens.
	 *
	 * @return void
	 */
	public function testOnlyTheOptionsThatAreOnLoadTheirStylesheet() {
		$this->givenStored(
			array(
				'spreadshopLayoutSidebar' => 1,
				'spreadshopLayoutCards'   => 1,
			)
		);

		Assets::enqueueForEmbed();

		$this->assertSame( array( 'spreadshopLayoutTokens', 'spreadshopLayoutSidebar', 'spreadshopLayoutCards' ), $this->styles );
	}

	/**
	 * The category-tree script comes with the sidebar, after the observer it registers with.
	 *
	 * @return void
	 */
	public function testTheSidebarLoadsTheNavigationScript() {
		$this->givenStored( array( 'spreadshopLayoutSidebar' => 1 ) );

		Assets::enqueueForEmbed();

		$this->assertSame( array( 'spreadshopLayoutObserve', 'spreadshopLayoutNav' ), $this->scripts );
	}

	/**
	 * The cards bring the card model and their script, configured with the chosen fields.
	 *
	 * @return void
	 */
	public function testTheCardsLoadTheirScriptWithTheChosenFields() {
		$this->givenStored(
			array(
				'spreadshopLayoutCards' => 1,
				'spreadshopCardFields'  => array( 'sizes', 'price' ),
			)
		);

		Assets::enqueueForEmbed();

		$this->assertSame( array( 'spreadshopLayoutObserve', 'spreadshopLayoutData', 'spreadshopLayoutCards' ), $this->scripts );
		$this->assertSame(
			array( 'window.spreadshopLayoutConfig = {"cardFields":["sizes","price"]};' ),
			$this->inline['spreadshopLayoutCards']
		);
	}

	/**
	 * The product page brings its tabs script.
	 *
	 * @return void
	 */
	public function testTheProductPageLoadsItsScript() {
		$this->givenStored( array( 'spreadshopLayoutProductPage' => 1 ) );

		Assets::enqueueForEmbed();

		$this->assertSame( array( 'spreadshopLayoutObserve', 'spreadshopLayoutProduct' ), $this->scripts );
	}

	/**
	 * Each script and stylesheet loads its own file after what it depends on, and the cards
	 * read their configuration before they run.
	 *
	 * @return void
	 */
	public function testEveryAssetDeclaresItsFileAndDependencies() {
		$this->givenStored(
			array(
				'spreadshopLayoutSidebar'       => 1,
				'spreadshopLayoutCompactFooter' => 1,
				'spreadshopLayoutCards'         => 1,
				'spreadshopLayoutProductPage'   => 1,
			)
		);

		Assets::enqueueForEmbed();

		$expected = array(
			'style:spreadshopLayoutTokens'        => array( 'style/layout/tokens.css', array() ),
			'style:spreadshopLayoutSidebar'       => array( 'style/layout/sidebar.css', array( 'spreadshopLayoutTokens' ) ),
			'style:spreadshopLayoutCompactFooter' => array( 'style/layout/compact-footer.css', array( 'spreadshopLayoutTokens' ) ),
			'style:spreadshopLayoutCards'         => array( 'style/layout/cards.css', array( 'spreadshopLayoutTokens' ) ),
			'style:spreadshopLayoutProductPage'   => array( 'style/layout/product-page.css', array( 'spreadshopLayoutTokens' ) ),
			'script:spreadshopLayoutObserve'      => array( 'js/layout-observe.js', array() ),
			'script:spreadshopLayoutNav'          => array( 'js/layout-nav.js', array( 'wp-i18n', 'spreadshopLayoutObserve' ) ),
			'script:spreadshopLayoutData'         => array( 'js/layout-data.js', array() ),
			'script:spreadshopLayoutCards'        => array( 'js/layout.js', array( 'wp-i18n', 'spreadshopLayoutObserve', 'spreadshopLayoutData' ) ),
			'script:spreadshopLayoutProduct'      => array( 'js/layout-product.js', array( 'spreadshopLayoutObserve' ) ),
		);
		foreach ( $expected as $handle => list( $file, $deps ) ) {
			$this->assertArrayHasKey( $handle, $this->assets, $handle );
			$this->assertStringEndsWith( '/' . $file, $this->assets[ $handle ][0], $handle );
			$this->assertSame( $deps, $this->assets[ $handle ][1], $handle );
		}
		$this->assertSame( array( 'before' ), $this->positions['spreadshopLayoutCards'] );
		$this->assertSame( array( 'spreadshopLayoutNav', 'spreadshopLayoutCards' ), $this->translated );
	}

	/**
	 * The compact footer is CSS only.
	 *
	 * @return void
	 */
	public function testTheCompactFooterLoadsNoScript() {
		$this->givenStored( array( 'spreadshopLayoutCompactFooter' => 1 ) );

		Assets::enqueueForEmbed();

		$this->assertSame( array(), $this->scripts );
	}

	/**
	 * A page without the shop gets nothing, even with every option on.
	 *
	 * @return void
	 */
	public function testAPageWithoutTheShopLoadsNothing() {
		$this->givenStored( array( 'spreadshopLayoutCompactFooter' => 1 ) );
		Functions\when( 'has_shortcode' )->justReturn( false );

		Assets::enqueueForEmbed();

		$this->assertSame( array(), $this->styles );
	}

	/**
	 * A disconnected shop renders nothing, so nothing is loaded for it.
	 *
	 * @return void
	 */
	public function testADisconnectedShopLoadsNothing() {
		$this->givenStored(
			array(
				'spreadshopID'                  => '',
				'spreadshopLayoutCompactFooter' => 1,
			)
		);

		Assets::enqueueForEmbed();

		$this->assertSame( array(), $this->styles );
	}

	/**
	 * The head enqueue and the short code's late one together load everything once.
	 *
	 * @return void
	 */
	public function testTheLateEnqueueDoesNotRepeatTheHeadOne() {
		$this->givenStored(
			array(
				'spreadshopLayoutCompactFooter' => 1,
				'spreadshopColors'              => array( 'accent' => '#fc71f0' ),
			)
		);

		Assets::enqueueForEmbed();
		Assets::enqueue();

		$this->assertSame( array( 'spreadshopLayoutTokens', 'spreadshopLayoutCompactFooter' ), $this->styles );
		$this->assertCount( 1, $this->inline['spreadshopLayoutTokens'] );
	}

	/**
	 * Chosen colours are printed on #myShop, under their custom property names.
	 *
	 * @return void
	 */
	public function testChosenColoursArePrintedOnTheShop() {
		$this->givenStored(
			array(
				'spreadshopLayoutCards' => 1,
				'spreadshopColors'      => array(
					'accent'     => '#fc71f0',
					'accentText' => '#0d130f',
				),
			)
		);

		Assets::enqueue();

		$this->assertSame(
			array( '#myShop{--spreadshop-accent:#fc71f0;--spreadshop-accent-text:#0d130f}' ),
			$this->inline['spreadshopLayoutTokens']
		);
	}

	/**
	 * No colour chosen means no inline rule at all, so the theme's mapping stays in charge.
	 *
	 * @return void
	 */
	public function testNoColoursAddNoInlineRule() {
		$this->givenStored( array( 'spreadshopLayoutCards' => 1 ) );

		Assets::enqueue();

		$this->assertArrayNotHasKey( 'spreadshopLayoutTokens', $this->inline );
	}

	/**
	 * A tampered row never reaches the stylesheet: bad values and unknown keys are dropped.
	 *
	 * @return void
	 */
	public function testATamperedColourRowIsFilteredOnOutput() {
		$this->givenStored(
			array(
				'spreadshopColors' => array(
					'accent'     => 'red;}body{display:none',
					'text'       => '#0d130f',
					'background' => array( '#ffffff' ),
					'x}body{'    => '#ffffff',
				),
			)
		);

		$this->assertSame( '#myShop{--spreadshop-text:#0d130f}', Tokens::css() );
	}
}
