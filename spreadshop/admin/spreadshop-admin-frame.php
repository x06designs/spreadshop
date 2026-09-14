<?php
/**
 * Header and footer chrome for the plugin's admin screen.
 *
 * @package Spreadshop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SpreadshopAdminFrame
 * Renders header and footer of the admin section.
 */
class SpreadshopAdminFrame {

	/**
	 * Opens the admin page markup: header, tab navigation and the settings section.
	 *
	 * @param bool $inAdvancedTab Whether the Advanced tab is the active one.
	 * @param bool $isConnected   Whether a shop is linked; the Advanced tab is hidden until it is.
	 * @return void
	 */
	public static function renderTop( $inAdvancedTab, $isConnected ) {
		?>
		<div class="sprd-container">
			<div>
				<section class="sprd-header">
					<div class="sprd-header--logo">
						<span class="sprd-wordmark">Spreadshop</span>
					</div>
					<div>
						<p><?php esc_html_e( 'A WordPress plugin to seamlessly integrate your shop with your WordPress website.', 'spreadshop' ); ?></p>
					</div>
				</section>
				<nav class="nav-tab-wrapper">
					<a href="?page=Spreadshop" class="nav-tab<?php echo $inAdvancedTab ? '' : ' nav-tab-active'; ?>"><?php esc_html_e( 'Connect', 'spreadshop' ); ?></a>
					<?php
					if ( $isConnected ) {
						?>
						<a href="?page=Spreadshop&tab=advanced" class="nav-tab<?php echo $inAdvancedTab ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'Advanced', 'spreadshop' ); ?></a>
						<?php
					}
					?>
				</nav>
				<section class="sprd-settings wrap">
		<?php
	}

	/**
	 * Closes the admin page markup and renders the footer links.
	 *
	 * @param bool $isConnected Whether a shop is linked; the preview link needs one.
	 * @return void
	 */
	public static function renderBottom( $isConnected ) {
			$previewLink = '';
			$slug        = get_option( 'spreadshopSlug' );
		if ( $isConnected && $slug ) {
			$previewLink = '<a href="' . esc_url( get_home_url() . '/' . $slug ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Preview Shop', 'spreadshop' ) . '</a>';
		}
		?>
				</section>
			</div>
			<div class="sprd-links">
				<?php echo wp_kses_post( $previewLink ); ?>
				<a href="https://github.com/x06designs/spreadshop" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Read more in our FAQ >', 'spreadshop' ); ?></a>
			</div>
		</div>
		<?php
	}
}
