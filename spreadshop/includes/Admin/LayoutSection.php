<?php
/**
 * The Layout section of the Advanced tab.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Admin;

use Spreadshop\Layout\Schema;
use Spreadshop\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class LayoutSection
 * Renders the layout options and writes them back. Part of the Advanced form: it shares that
 * form's nonce and submit button.
 */
class LayoutSection {

	/**
	 * Layout toggles, in display order.
	 *
	 * @var string[]
	 */
	const TOGGLES = array( 'spreadshopLayoutSidebar', 'spreadshopLayoutCompactFooter', 'spreadshopLayoutCards', 'spreadshopLayoutProductPage' );

	/**
	 * Renders the section.
	 *
	 * @return void
	 */
	public static function render() {
		?>
		<h2><?php esc_html_e( 'Layout', 'spreadshop' ); ?></h2>
		<p><?php esc_html_e( "Restyle the shop's own navigation, footer, product tiles and product page so the shop takes on your theme's fonts and colours, unless Load Spreadshop Fonts is on, in which case the shop keeps its own fonts. Every option is off by default and each works on its own.", 'spreadshop' ); ?></p>

		<table class="form-table">
			<tbody>
			<?php self::renderToggleRow( 'spreadshopLayoutSidebar', __( 'Sidebar navigation', 'spreadshop' ), __( "Show the shop's navigation, search and basket as a sidebar", 'spreadshop' ), __( 'On small screens the sidebar becomes a bar with a categories button.', 'spreadshop' ) ); ?>
			<?php self::renderToggleRow( 'spreadshopLayoutCompactFooter', __( 'Compact footer', 'spreadshop' ), __( "Collapse the shop's footer into a single row", 'spreadshop' ), __( 'The service and legal links stay visible: Spreadshirt is the seller, and those pages are theirs.', 'spreadshop' ) ); ?>
			<?php self::renderToggleRow( 'spreadshopLayoutCards', __( 'Product cards', 'spreadshop' ), __( 'Add product details to the product tiles', 'spreadshop' ), __( "Only lists of products get cards. The design list, which is the shop's default start page, shows designs rather than products and stays as it is.", 'spreadshop' ) ); ?>
			<?php self::renderToggleRow( 'spreadshopLayoutProductPage', __( 'Product page', 'spreadshop' ), __( 'Restyle the product page', 'spreadshop' ), __( 'Separates the sections of the product page, shows description and size guide as tabs, and tidies the design, tags and sharing block.', 'spreadshop' ) ); ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Card fields', 'spreadshop' ); ?></th>
				<td><?php self::renderCardFields(); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Start page', 'spreadshop' ); ?></th>
				<td><?php self::renderStartList(); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Colours', 'spreadshop' ); ?></th>
				<td><?php self::renderColors(); ?></td>
			</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Writes the submitted section.
	 *
	 * The nonce is verified by AdvancedTab::handle() before this is reached, which the sniff
	 * cannot see across the call boundary.
	 *
	 * @return void
	 */
	public static function handleUpdate() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified in AdvancedTab::handle().
		foreach ( self::TOGGLES as $option ) {
			update_option( $option, isset( $_POST[ $option ] ) ? 1 : 0 );
		}

		// Sanitised by the Settings callbacks, which update_option also runs; unslashed here
		// because WordPress adds slashes to all of $_POST.
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$cardFields = isset( $_POST['spreadshopCardFields'] ) ? wp_unslash( $_POST['spreadshopCardFields'] ) : null;
		$startList  = isset( $_POST['spreadshopStartList'] ) ? wp_unslash( $_POST['spreadshopStartList'] ) : null;
		$colors     = isset( $_POST['spreadshopColors'] ) ? wp_unslash( $_POST['spreadshopColors'] ) : null;
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		update_option( 'spreadshopCardFields', Settings::sanitizeCardFields( $cardFields ) );
		update_option( 'spreadshopStartList', Settings::sanitizeStartList( $startList ) );
		update_option( 'spreadshopColors', Settings::sanitizeColors( $colors ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * The label for each card field.
	 *
	 * @return array<string, string>
	 */
	public static function cardFieldLabels() {
		return array(
			'productType' => __( 'Product type', 'spreadshop' ),
			'price'       => __( 'Price', 'spreadshop' ),
			'swatches'    => __( 'Colour swatches', 'spreadshop' ),
			'sizes'       => __( 'Sizes', 'spreadshop' ),
			'hoverImage'  => __( 'Second image on hover', 'spreadshop' ),
		);
	}

	/**
	 * The label for each colour token.
	 *
	 * @return array<string, string>
	 */
	private static function colorLabels() {
		return array(
			'accent'     => __( 'Accent', 'spreadshop' ),
			'accentText' => __( 'Text on accent', 'spreadshop' ),
			'background' => __( 'Background', 'spreadshop' ),
			'text'       => __( 'Text', 'spreadshop' ),
			'muted'      => __( 'Secondary text', 'spreadshop' ),
			'border'     => __( 'Lines', 'spreadshop' ),
		);
	}

	/**
	 * Renders one layout toggle.
	 *
	 * @param string $option      Option name, also the input name and id.
	 * @param string $title       Row heading.
	 * @param string $label       Checkbox label.
	 * @param string $description Help text under the checkbox.
	 * @return void
	 */
	private static function renderToggleRow( $option, $title, $label, $description ) {
		?>
		<tr>
			<th scope="row">
				<label for="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $title ); ?></label>
			</th>
			<td>
				<label>
					<input id="<?php echo esc_attr( $option ); ?>" type="checkbox" name="<?php echo esc_attr( $option ); ?>" value="1"
						<?php echo checked( Settings::isLayoutEnabled( $option ), true, false ); ?>
					/>
					<?php echo esc_html( $label ); ?>
				</label>
				<p class="description"><?php echo esc_html( $description ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Renders the card fields as a checkbox group, chosen fields first in their saved order.
	 *
	 * This is the complete control without JavaScript. js/admin-card-fields.js upgrades it to a
	 * reorderable chip list; the hidden empty item lets an empty selection be told apart from a
	 * field that was never posted.
	 *
	 * @return void
	 */
	private static function renderCardFields() {
		$chosen = Settings::cardFields();
		$labels = self::cardFieldLabels();
		$order  = array_merge( $chosen, array_diff( Schema::allowedValues( 'spreadshopCardFields' ), $chosen ) );
		?>
		<fieldset class="spreadshop-card-fields" data-spreadshop-card-fields>
			<legend class="screen-reader-text"><?php esc_html_e( 'Card fields', 'spreadshop' ); ?></legend>
			<input type="hidden" name="spreadshopCardFields[]" value="" />
			<?php foreach ( $order as $field ) : ?>
				<?php
				if ( ! is_string( $field ) || ! isset( $labels[ $field ] ) ) {
					continue;
				}
				?>
				<label class="spreadshop-card-fields__option">
					<input type="checkbox" name="spreadshopCardFields[]" value="<?php echo esc_attr( $field ); ?>"
						<?php echo checked( in_array( $field, $chosen, true ), true, false ); ?>
					/>
					<?php echo esc_html( $labels[ $field ] ); ?>
				</label><br />
			<?php endforeach; ?>
		</fieldset>
		<p class="description"><?php esc_html_e( 'Used when Product cards is on. The product name is always shown first; the other fields follow in the order chosen here.', 'spreadshop' ); ?></p>
		<?php
	}

	/**
	 * Renders the start page choice.
	 *
	 * @return void
	 */
	private static function renderStartList() {
		$current = Settings::startList();
		$choices = array(
			'designs'  => __( 'Designs (the shop default)', 'spreadshop' ),
			'products' => __( 'All products', 'spreadshop' ),
		);
		?>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Start page', 'spreadshop' ); ?></legend>
			<?php foreach ( $choices as $value => $label ) : ?>
				<label>
					<input type="radio" name="spreadshopStartList" value="<?php echo esc_attr( $value ); ?>"
						<?php echo checked( $current, $value, false ); ?>
					/>
					<?php echo esc_html( $label ); ?>
				</label><br />
			<?php endforeach; ?>
		</fieldset>
		<p class="description"><?php esc_html_e( 'A Start Token above, or a deeplink= parameter on the short code, takes precedence.', 'spreadshop' ); ?></p>
		<?php
	}

	/**
	 * Renders one colour field per token.
	 *
	 * @return void
	 */
	private static function renderColors() {
		$current = Settings::colors();
		?>
		<fieldset class="spreadshop-colors">
			<legend class="screen-reader-text"><?php esc_html_e( 'Colours', 'spreadshop' ); ?></legend>
			<?php foreach ( self::colorLabels() as $token => $label ) : ?>
				<p class="spreadshop-colors__row">
					<label for="spreadshopColors-<?php echo esc_attr( $token ); ?>"><?php echo esc_html( $label ); ?></label><br />
					<input id="spreadshopColors-<?php echo esc_attr( $token ); ?>" class="spreadshop-color" type="text"
						name="spreadshopColors[<?php echo esc_attr( $token ); ?>]"
						value="<?php echo esc_attr( $current[ $token ] ?? '' ); ?>"
						pattern="#[0-9a-fA-F]{6}" maxlength="7" placeholder="#rrggbb"
					/>
				</p>
			<?php endforeach; ?>
		</fieldset>
		<p class="description"><?php esc_html_e( "Leave a colour empty to use your theme's. A theme can also set these as CSS custom properties, such as --spreadshop-accent; a colour chosen here overrides the theme. The colours apply once at least one layout option is on.", 'spreadshop' ); ?></p>
		<?php
	}
}
