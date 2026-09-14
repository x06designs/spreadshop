<?php
/**
 * The Advanced tab of the plugin's admin screen.
 *
 * @package Spreadshop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SpreadshopAdminAdvanced
 * Renders the "Advanced" admin interface and handles the form data posted from it.
 */
class SpreadshopAdminAdvanced {

	/**
	 * Persists a submitted Advanced form and reports which view to render.
	 *
	 * @param bool $isConnected Whether a shop is linked.
	 * @return array<string, string> Render instructions for render().
	 */
	public static function handle( $isConnected ) {
		if ( ! $isConnected ) {
			return array( 'page' => 'notConnected' );
		}
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			// Nonce check for the '_wpnonce' input.
			check_admin_referer( SpreadshopConstants::SPREADSHOP_SETTINGS_GROUP . '-options' );
			self::handleUpdate();
		}
		return array( 'page' => 'connected' );
	}

	/**
	 * Renders the view chosen by handle().
	 *
	 * @param array<string, string> $renderData Render instructions from handle().
	 * @return void
	 */
	public static function render( $renderData ) {
		if ( $renderData['page'] === 'notConnected' ) {
			self::renderNotConnected();
		} else {
			self::renderConnected(
				get_option( 'spreadshopSlug', '' ),
				get_option( 'spreadshopToken' ),
				get_option( 'spreadshopOptimizeUrl' ),
				get_option( 'spreadshopMetadata' ),
				get_option( 'spreadshopSwipeMenu' ),
				get_option( 'spreadshopLoadFonts' )
			);
		}
	}

	/**
	 * Renders the placeholder shown while no shop is linked.
	 *
	 * @return void
	 */
	private static function renderNotConnected() {
		?>
		<div>
			<?php esc_html_e( 'Please connect your shop first before you deal with advanced settings.', 'spreadshop' ); ?>
		</div>
		<?php
	}

	/**
	 * Renders the Advanced settings form.
	 *
	 * @param string $slug        Url path the shop is served from, or an empty string.
	 * @param string $token       Deeplink opened on load, or an empty string.
	 * @param string $optimizeUrl Whether pushState urls are enabled.
	 * @param string $metaData    Whether the shop may rewrite the document head.
	 * @param string $swipeMenu   Whether the mobile swipe menu replaces the burger menu.
	 * @param string $loadFonts   Whether the shop's own fonts are used.
	 * @return void
	 */
	private static function renderConnected( $slug, $token, $optimizeUrl, $metaData, $swipeMenu, $loadFonts ) {
		?>
		<div id="spreadShopSettingsEdit">
			<form method="post">
				<?php settings_fields( SpreadshopConstants::SPREADSHOP_SETTINGS_GROUP ); ?>
				<?php do_settings_sections( SpreadshopConstants::SPREADSHOP_SETTINGS_GROUP ); ?>
				<table class="form-table">
					<tbody>
					<tr>
						<th scope="row">
							<label for="startToken"><?php esc_html_e( 'Start Token (Optional)', 'spreadshop' ); ?></label>
						</th>
						<td>
							<input id="startToken" class="regular-text" type="text" name="spreadshopToken"
									value="<?php echo esc_attr( $token ); ?>"
							/>
							<p class="description">
								<?php esc_html_e( 'To show a specific page from your shop, enter the page url here. Only the text highlighted in the example below is necessary:', 'spreadshop' ); ?>
							</p>
							<p>https://shop-template-brand.myspreadshop.com/<strong class="sprd-highlighted-txt">cafe+koenji+logo?idea=5d5534fd2051766bd5973b60</strong>
							</p>
							<p class="description">
								<?php esc_html_e( 'This is primarily useful for slug-based integrations (see below).', 'spreadshop' ); ?>
								<?php
								printf(
									/* translators: %s: the deeplink= shortcode parameter, shown in bold. */
									esc_html__( 'When using the shortcode, the same effect can be achieved by using the %s parameter.', 'spreadshop' ),
									'<strong>deeplink=</strong>'
								);
								?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="metaData"><?php esc_html_e( 'Update Meta Data', 'spreadshop' ); ?></label>
						</th>
						<td>
							<label>
								<input id="metaData" type="checkbox" name="spreadshopMetadata" value="1"
									<?php echo checked( (int) $metaData === 1, true, false ); ?>
								/>
								<?php esc_html_e( "Allow Spreadshop to update your site's head section", 'spreadshop' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( "Spreadshop will update your site's title, description, seoIndex, as well as OpenGraph and Twitter Card tags.", 'spreadshop' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="loadFonts"><?php esc_html_e( 'Load Spreadshop Fonts', 'spreadshop' ); ?></label>
						</th>
						<td>
							<label>
								<input id="loadFonts" type="checkbox" name="spreadshopLoadFonts" value="1"
									<?php echo checked( (int) $loadFonts === 1, true, false ); ?>
								/>
								<?php esc_html_e( "Use Fonts from your Shop's Partner Area", 'spreadshop' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'If unchecked, the fonts from your WordPress theme will be used.', 'spreadshop' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="swipeMenu"><?php esc_html_e( 'Mobile Swipe Menu', 'spreadshop' ); ?></label>
						</th>
						<td>
							<label>
								<input id="swipeMenu" type="checkbox" name="spreadshopSwipeMenu" value="1"
									<?php echo checked( (int) $swipeMenu === 1, true, false ); ?>
								/>
								<?php esc_html_e( 'Use Mobile Swipe Menu instead of Burger Menu', 'spreadshop' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Check this option to avoid a second separate burger-menu from showing up on your page if your site already uses one.', 'spreadshop' ); ?>
							</p>
						</td>
					</tr>
					</tbody>
				</table>

				<h2><?php esc_html_e( 'Alternative slug-based integration', 'spreadshop' ); ?></h2>
				<p>
					<?php
					printf(
						/* translators: %s: the [spreadshop] shortcode, shown in bold. */
						esc_html__( 'The recommended way of embedding your Spreadshop is to use the %s short code.', 'spreadshop' ),
						'<strong>[spreadshop]</strong>'
					);
					?>
					<br/>
					<?php esc_html_e( 'Alternatively, you can define a slug (url path) here to embed the Spreadshop.', 'spreadshop' ); ?><br/>
					<a href="https://github.com/x06designs/spreadshop" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Read more in our FAQ >', 'spreadshop' ); ?></a>
				</p>

				<table class="form-table">
					<tbody>
					<tr>
						<th scope="row">
							<label for="slug"><?php esc_html_e( 'Shop URL Path (Optional)', 'spreadshop' ); ?></label>
						</th>
						<td>
							<?php echo esc_html( get_home_url() . '/' ); ?>
							<input id="slug" class="regular-text" type="text" name="spreadshopSlug"
									value="<?php echo esc_attr( $slug ); ?>"/>
							<p class="description"><?php esc_html_e( 'Define a URL path where you want your Spreadshop to show up on your WordPress website.', 'spreadshop' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="optimizeUrl"><?php esc_html_e( 'Push State URLs', 'spreadshop' ); ?></label>
						</th>
						<td>
							<label>
								<input id="optimizeUrl" type="checkbox" name="spreadshopOptimizeUrl" value="1"
									<?php echo checked( (int) $optimizeUrl === 1, true, false ); ?>
								/>
								<?php esc_html_e( 'Optimize URL', 'spreadshop' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Removes the hashbangs (as shown in the example below) from your URLs', 'spreadshop' ); ?></p>
							<p>https://mywp.example.com/myshop/<strong class="sprd-highlighted-txt">#!/</strong>cafe+koenji+logo?idea=5d5534fd2051766bd5973b60</p>
							<p class="description">
								<?php
								printf(
									/* translators: %s: the "Shop URL Path" setting name, shown in bold. */
									esc_html__( 'This only works when using the %s method instead of the shortcode.', 'spreadshop' ),
									'<strong>' . esc_html__( 'Shop URL Path', 'spreadshop' ) . '</strong>'
								);
								?>
							</p>
						</td>
					</tr>
					</tbody>
				</table>
				<?php submit_button( __( 'Save Changes', 'spreadshop' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Writes the submitted Advanced settings.
	 *
	 * The nonce is verified by handle() before this is reached, which the sniff cannot see
	 * across the call boundary.
	 *
	 * @return void
	 */
	private static function handleUpdate() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified in handle().
		// wp_strip_all_tags rather than sanitize_text_field: both values are url fragments and
		// sanitize_text_field would silently eat percent-encoded characters out of them.
		$slug        = isset( $_POST['spreadshopSlug'] ) ? wp_strip_all_tags( wp_unslash( $_POST['spreadshopSlug'] ) ) : '';
		$startToken  = isset( $_POST['spreadshopToken'] ) ? wp_strip_all_tags( wp_unslash( $_POST['spreadshopToken'] ) ) : '';
		$optimizeUrl = isset( $_POST['spreadshopOptimizeUrl'] ) ? 1 : 0;
		$metaData    = isset( $_POST['spreadshopMetadata'] ) ? 1 : 0;
		$swipeMenu   = isset( $_POST['spreadshopSwipeMenu'] ) ? 1 : 0;
		$loadFonts   = isset( $_POST['spreadshopLoadFonts'] ) ? 1 : 0;
		update_option( 'spreadshopSlug', $slug );
		update_option( 'spreadshopToken', $startToken );
		update_option( 'spreadshopOptimizeUrl', $optimizeUrl );
		update_option( 'spreadshopMetadata', $metaData );
		update_option( 'spreadshopSwipeMenu', $swipeMenu );
		update_option( 'spreadshopLoadFonts', $loadFonts );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}
}
