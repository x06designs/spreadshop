<?php
/**
 * The Connect tab of the plugin's admin screen.
 *
 * @package Spreadshop
 */

namespace Spreadshop\Admin;

use Spreadshop\Constants;
use Spreadshop\Platform;
use Spreadshop\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class ConnectTab
 * Renders the "Connect your Shop" interfaces and handles the form data posted from them.
 */
class ConnectTab {

	/**
	 * How many times a lookup may be attempted before its answer is taken as final.
	 *
	 * @var int
	 */
	const MAX_LOOKUP_ATTEMPTS = 2;

	/**
	 * Locales selectable per platform when a shop serves more than one.
	 *
	 * @var array<string, array<string, string>>
	 */
	private static $locales = array(
		'EU' => array(
			'Danmark'             => 'da_DK',
			'Europe'              => 'en_EU',
			'Ireland'             => 'en_IE',
			'United Kingdom'      => 'en_GB',
			'Deutschland'         => 'de_DE',
			'Österreich'          => 'de_AT',
			'Schweiz (Deutsch)'   => 'de_CH',
			'Suisse (Francais)'   => 'fr_CH',
			'Svizzera (Italiano)' => 'it_CH',
			'Espana'              => 'es_ES',
			'Suomi'               => 'fi_FI',
			'France'              => 'fr_FR',
			'Belgique (Francais)' => 'fr_BE',
			'Italia'              => 'it_IT',
			'Belgie (Nederlands)' => 'nl_BE',
			'Nederland'           => 'nl_NL',
			'Norge'               => 'no_NO',
			'Polska'              => 'pl_PL',
			'Sverige'             => 'sv_SE',
		),
		'NA' => array(
			'United States'     => 'en_US',
			'Canada (English)'  => 'en_CA',
			'Canada (Francais)' => 'fr_CA',
			'Australia'         => 'en_AU',
		),
	);

	/**
	 * Dispatches a submitted Connect form and reports which view to render.
	 *
	 * @param bool $isConnected Whether a shop is linked.
	 * @return array<string, mixed> Render instructions for render().
	 */
	public static function handle( $isConnected ) {
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			// Nonce check for the '_wpnonce' input.
			check_admin_referer( Constants::SPREADSHOP_SETTINGS_GROUP . '-options' );
			$form = isset( $_POST['spreadshopAdminForm'] ) ? sanitize_text_field( wp_unslash( $_POST['spreadshopAdminForm'] ) ) : '';
			if ( $form === 'connect' ) {
				return self::handleConnect();
			} elseif ( $form === 'disconnect' ) {
				return self::handleDisconnect();
			} elseif ( $form === 'confirmConnect' ) {
				return self::handleConfirmConnect();
			} elseif ( $form === 'testConnection' ) {
				return self::handleTestConnection();
			}
		}

		return $isConnected ? array( 'page' => 'connected' ) : array(
			'page'     => 'initial',
			'errorMsg' => '',
		);
	}

	/**
	 * Renders the view chosen by handle().
	 *
	 * @param array<string, mixed> $renderData Render instructions from handle().
	 * @return void
	 */
	public static function render( $renderData ) {
		if ( $renderData['page'] === 'initial' ) {
			self::renderInitial( $renderData['errorMsg'] );
		} elseif ( $renderData['page'] === 'confirm' ) {
			self::renderConfirm( $renderData['euResponse'], $renderData['naResponse'] );
		} elseif ( $renderData['page'] === 'connected' ) {
			self::renderConnected(
				get_option( 'spreadshopPlatform' ),
				get_option( 'spreadshopID' ),
				get_option( 'spreadshopLocale' ),
				isset( $renderData['testResult'] ) ? $renderData['testResult'] : null
			);
		}
	}

	/**
	 * Renders the shop-name entry form.
	 *
	 * @param string $errorMsg Message to show above the field, or an empty string.
	 * @return void
	 */
	private static function renderInitial( $errorMsg = '' ) {
		?>
		<h1><?php esc_html_e( 'Connect your Shop', 'spreadshop' ); ?></h1>
		<p><?php esc_html_e( 'Please conclude the initial shop-linking step outlined below. Instructions on how to embed your linked shop will follow.', 'spreadshop' ); ?></p>
		<form id="connectform" name="connectform" method="post">
			<?php settings_fields( Constants::SPREADSHOP_SETTINGS_GROUP ); ?>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="shopId"><?php esc_html_e( 'Shop Name or ID', 'spreadshop' ); ?></label>
					</th>
					<td>
						<?php
						if ( $errorMsg ) {
							?>
							<div class="sprd-error-box" role="alert">
								<span><?php esc_html_e( 'ERROR:', 'spreadshop' ); ?> </span><?php echo esc_html( $errorMsg ); ?>
							</div>
							<?php
						}
						?>
						<input type="text" class="regular-text" id="shopId" name="spreadshopIDOrName"/>
						<p class="description">
							<?php esc_html_e( "Enter your Spreadshop's numeric ID (found in the Partner Area) or name.", 'spreadshop' ); ?><br/>
							<?php esc_html_e( "The name is visible in your shop's url, for example:", 'spreadshop' ); ?>
							https://<strong>this-name</strong>.myspreadshop.net/
						</p>
					</td>
				</tr>
				<input type="hidden" name="spreadshopAdminForm" value="connect">
			</table>
			<?php submit_button( __( 'Connect', 'spreadshop' ) ); ?>
		</form>

		<p>
			<em><?php esc_html_e( 'No shop yet?', 'spreadshop' ); ?>
				<a href="https://www.spreadshirt.com/start-selling-shirts-C3598"><?php esc_html_e( 'Register now!', 'spreadshop' ); ?></a>
			</em>
		</p>
		<?php
	}

	/**
	 * Renders the confirmation step, offering a choice when both platforms matched.
	 *
	 * @param array<string, mixed> $euResponse Lookup result from the European platform.
	 * @param array<string, mixed> $naResponse Lookup result from the North American platform.
	 * @return void
	 */
	private static function renderConfirm( $euResponse, $naResponse ) {
		$bothPlatforms = $euResponse['shopId'] !== null && $naResponse['shopId'] !== null;
		$firstEntry    = $euResponse['shopId'] === null ? $naResponse : $euResponse;
		?>
		<h1><?php esc_html_e( 'Confirm the Connection', 'spreadshop' ); ?></h1>
		<?php
		if ( $bothPlatforms ) {
			?>
			<table class="form-table">
				<tbody>
				<tr>
					<th><?php esc_html_e( 'Platform', 'spreadshop' ); ?></th>
					<td>
						<fieldset>
							<legend><?php esc_html_e( 'We found shops matching your criteria on both platforms. Which one do you want to link?', 'spreadshop' ); ?></legend>
							<label>
								<input type="radio" name="platformSwitch" value="EU" checked
										data-spreadshop-shows="confirmEU" data-spreadshop-hides="confirmNA"/>
								<?php
									printf(
										/* translators: %s: shop id and name, for example 1376884 (Stechmuecke). */
										esc_html__( 'Shop %s on the European platform', 'spreadshop' ),
										esc_html( $euResponse['shopId'] . ' (' . $euResponse['shopName'] . ')' )
									);
								?>
							</label>
							<br/>
							<label>
								<input type="radio" name="platformSwitch" value="NA"
										data-spreadshop-shows="confirmNA" data-spreadshop-hides="confirmEU"/>
								<?php
									printf(
										/* translators: %s: shop id and name, for example 1376884 (Stechmuecke). */
										esc_html__( 'Shop %s on the North American platform', 'spreadshop' ),
										esc_html( $naResponse['shopId'] . ' (' . $naResponse['shopName'] . ')' )
									);
								?>
							</label>
						</fieldset>
					</td>
				</tr>
				</tbody>
			</table>
			<?php
			self::renderConfirmForm( 'confirmEU', 'EU', $euResponse, false );
			self::renderConfirmForm( 'confirmNA', 'NA', $naResponse, true );
		} else {
			self::renderConfirmForm( '', $euResponse['shopId'] === null ? 'NA' : 'EU', $firstEntry, false );
		}
	}

	/**
	 * Renders one confirmation form, one per platform that matched.
	 *
	 * @param string               $formId   Element id, needed only when both platforms matched.
	 * @param string               $platform Either 'EU' or 'NA'.
	 * @param array<string, mixed> $response Lookup result for that platform.
	 * @param bool                 $hidden   Whether this form starts hidden behind the platform switch.
	 * @return void
	 */
	private static function renderConfirmForm( $formId, $platform, $response, $hidden ) {
		?>
		<form id="<?php echo esc_attr( $formId ); ?>" name="connectform" method="post" <?php echo esc_attr( $hidden ? 'hidden' : '' ); ?>>
		<?php settings_fields( Constants::SPREADSHOP_SETTINGS_GROUP ); ?>
		<table class="form-table">
			<tbody>
			<tr>
				<th><label for="shopId"><?php esc_html_e( 'Shop ID', 'spreadshop' ); ?></label></th>
				<td><input id="shopId" class="regular-text" name="shopId" type="text" readonly value="<?php echo esc_attr( $response['shopId'] ); ?>"/></td>
			</tr>
			<tr>
				<th><label for="shopNameIgnored"><?php esc_html_e( 'Shop Name', 'spreadshop' ); ?></label></th>
				<td><input id="shopNameIgnored" class="regular-text" name="shopNameIgnored" type="text" readonly value="<?php echo esc_attr( $response['shopName'] ); ?>"/></td>
			</tr>
			<tr>
				<th><label for="platform"><?php esc_html_e( 'Platform', 'spreadshop' ); ?></label></th>
				<td><input id="platform" class="regular-text" name="platform" type="text" readonly value="<?php echo esc_attr( $platform ); ?>"/></td>
			</tr>
			<tr>
				<th><label for="locale"><?php esc_html_e( 'Locale', 'spreadshop' ); ?></label></th>
				<td><?php self::renderLocaleSelector( $response['locales'], $response['baseLocale'] ); ?></td>
			</tr>
			</tbody>
		</table>
		<input type="hidden" name="spreadshopAdminForm" value="confirmConnect">
		<?php submit_button( __( 'Confirm Connection', 'spreadshop' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Renders a locale picker, or a readonly field when only one locale applies.
	 *
	 * @param array<string, string> $locales    Selectable locales, keyed by display name.
	 * @param string                $baseLocale Locale preselected by the shop's own configuration.
	 * @return void
	 */
	private static function renderLocaleSelector( $locales, $baseLocale ) {
		if ( count( $locales ) === 1 ) {
			?>
			<input id="locale" class="regular-text" name="locale" type="text" readonly value="<?php echo esc_attr( reset( $locales ) ); ?>"/>
			<?php
		} else {
			?>
			<select id="locale" name="locale">
				<?php
				foreach ( $locales as $name => $id ) {
					$selected = $id === $baseLocale ? 'selected' : '';
					?>
					<option <?php echo esc_attr( $selected ); ?> value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $name . ' ' . $id ); ?></option>
					<?php
				}
				?>
			</select>
			<p class="description"><?php esc_html_e( 'Influences language, currency and units of measurement.', 'spreadshop' ); ?></p>
			<?php
		}
	}

	/**
	 * Renders the linked-shop summary and the disconnect action.
	 *
	 * @param string                    $platform   Either 'EU' or 'NA'.
	 * @param string                    $shopId     Numeric shop id.
	 * @param string                    $locale     Locale the shop renders in.
	 * @param array<string, mixed>|null $testResult Outcome of a connection test, when one was just run.
	 * @return void
	 */
	private static function renderConnected( $platform, $shopId, $locale, $testResult = null ) {
		?>
		<h1><?php esc_html_e( 'Connected', 'spreadshop' ); ?></h1>
		<?php if ( is_array( $testResult ) ) { ?>
			<div class="<?php echo $testResult['ok'] ? 'sprd-ok-box' : 'sprd-error-box'; ?>" role="alert">
				<?php echo esc_html( $testResult['message'] ); ?>
			</div>
		<?php } ?>
		<p>
			<?php
			printf(
				/* translators: %s: the [spreadshop] shortcode, shown in bold. */
				esc_html__( 'You can now embed your Spreadshop anywhere using the shortcode %s!', 'spreadshop' ),
				'<strong>[spreadshop]</strong>'
			);
			?>
		</p>
		<p>
			<?php esc_html_e( 'If you want a specific page of your shop to show, you can pass a deeplink parameter.', 'spreadshop' ); ?><br/>
			<?php
			printf(
				/* translators: 1: an example shop url, 2: the matching shortcode, shown in bold. */
				esc_html__( 'For example, to show %1$s use the short code %2$s.', 'spreadshop' ),
				'https://shop-template-brand.myspreadshop.com/cafe+koenji+logo?idea=5d5534fd2051766bd5973b60',
				'<strong>[spreadshop deeplink="cafe+koenji+logo?idea=5d5534fd2051766bd5973b60"]</strong>'
			);
			?>
		</p>
		<form id="connectform" name="connectform" method="post">
			<?php settings_fields( Constants::SPREADSHOP_SETTINGS_GROUP ); ?>
			<table class="form-table">
				<tbody>
				<tr>
					<th><label for="platform"><?php esc_html_e( 'Platform', 'spreadshop' ); ?></label></th>
					<td><input id="platform" class="regular-text" name="platform" type="text" readonly value="<?php echo esc_attr( $platform ); ?>"/></td>
				</tr>
				<tr>
					<th><label for="shopId"><?php esc_html_e( 'Shop ID', 'spreadshop' ); ?></label></th>
					<td><input id="shopId" class="regular-text" name="shopId" type="text" readonly value="<?php echo esc_attr( $shopId ); ?>"/></td>
				</tr>
				<tr>
					<th><label for="locale"><?php esc_html_e( 'Locale', 'spreadshop' ); ?></label></th>
					<td><input id="locale" class="regular-text" name="locale" type="text" readonly value="<?php echo esc_attr( $locale ); ?>"/></td>
				</tr>
				</tbody>
			</table>
			<input type="hidden" name="spreadshopAdminForm" value="disconnect">
			<?php submit_button( __( 'Disconnect', 'spreadshop' ) ); ?>
		</form>

		<form id="testform" name="testform" method="post">
			<?php settings_fields( Constants::SPREADSHOP_SETTINGS_GROUP ); ?>
			<p class="description"><?php esc_html_e( 'Not sure the shop is still reachable? Ask Spreadshirt right now.', 'spreadshop' ); ?></p>
			<input type="hidden" name="spreadshopAdminForm" value="testConnection">
			<?php submit_button( __( 'Test connection', 'spreadshop' ), 'secondary' ); ?>
		</form>
		<?php
	}

	/**
	 * Looks the submitted name or id up on both platforms.
	 *
	 * The nonce is verified by handle() before this is reached, which the sniff cannot see
	 * across the call boundary.
	 *
	 * @return array<string, mixed> Render instructions: the confirmation step, or the form with an error.
	 */
	private static function handleConnect() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in handle().
		$shopIdOrName = isset( $_POST['spreadshopIDOrName'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['spreadshopIDOrName'] ) ) ) : '';
		if ( empty( $shopIdOrName ) ) {
			return array(
				'page'     => 'initial',
				'errorMsg' => __( 'You left the field empty!', 'spreadshop' ),
			);
		}
		if ( ! preg_match( '/^[a-zA-Z0-9-]*$/', $shopIdOrName ) ) {
			return array(
				'page'     => 'initial',
				'errorMsg' => __( 'The input you provided is neither a valid shop ID nor name!', 'spreadshop' ),
			);
		}
		$euResponse = self::fetchCoreData( $shopIdOrName, 'EU' );
		$naResponse = self::fetchCoreData( $shopIdOrName, 'NA' );
		if ( ! in_array( $euResponse['status'], array( 200, 404 ), true ) || ! in_array( $naResponse['status'], array( 200, 404 ), true ) ) {
			// At least one of the requests "got lost".
			return array(
				'page'     => 'initial',
				'errorMsg' => self::describeFailure( $euResponse, $naResponse ),
			);
		}
		if ( $euResponse['shopId'] === null && $naResponse['shopId'] === null ) {
			return array(
				'page'     => 'initial',
				'errorMsg' => __( 'Could not find any shop with this ID or name!', 'spreadshop' ),
			);
		}

		return array(
			'page'       => 'confirm',
			'euResponse' => $euResponse,
			'naResponse' => $naResponse,
		);
	}

	/**
	 * Reports what actually went wrong. A blanket "could not reach" hides the difference
	 * between an unreachable host and a host that answered with a refusal.
	 */
	/**
	 * Turns a failed lookup into a message that names the actual cause.
	 *
	 * @param array<string, mixed> $euResponse Lookup result from the European platform.
	 * @param array<string, mixed> $naResponse Lookup result from the North American platform.
	 * @return string Message for the admin.
	 */
	private static function describeFailure( $euResponse, $naResponse ) {
		foreach ( array( $euResponse, $naResponse ) as $response ) {
			if ( in_array( $response['status'], array( 200, 404 ), true ) ) {
				continue;
			}
			if ( isset( $response['errorDetail'] ) ) {
				/* translators: %s: the underlying transport error reported by WordPress. */
				return sprintf( __( 'Could not reach Spreadshirt: %s', 'spreadshop' ), $response['errorDetail'] );
			}
			if ( $response['status'] === 403 ) {
				return __( 'Spreadshirt refused the request (HTTP 403). This server\'s outgoing TLS stack is likely too old — setup needs PHP 8.1 or newer (OpenSSL 3).', 'spreadshop' );
			}
			/* translators: %d: an HTTP status code, for example 500. */
			return sprintf( __( 'Spreadshirt answered with HTTP %d. Try again in a moment.', 'spreadshop' ), (int) $response['status'] );
		}
		return __( 'Could not reach Spreadshirt! Try again.', 'spreadshop' );
	}

	/**
	 * Re-runs the shop lookup against the connected platform and reports what came back.
	 *
	 * Spreadshirt has changed the rules its edge enforces before, and when that happens the
	 * only symptom is the shop quietly failing to load for visitors. This turns that into
	 * something answerable on demand.
	 *
	 * @return array<string, mixed> Render instructions for the connected view.
	 */
	private static function handleTestConnection() {
		$shopId   = Settings::sanitizeShopId( get_option( 'spreadshopID' ) );
		$platform = Settings::sanitizePlatform( get_option( 'spreadshopPlatform' ) );

		/*
		 * The stored values decide the host this request goes to, so they are re-checked here
		 * rather than trusted. They are written through validated forms, but the options API
		 * is a second way in and a shop id carrying a hostname would point this somewhere else
		 * entirely.
		 */
		if ( '' === $shopId || '' === $platform ) {
			return array(
				'page'       => 'connected',
				'testResult' => array(
					'ok'      => false,
					'message' => __( 'The stored shop details are not usable. Disconnect and connect the shop again.', 'spreadshop' ),
				),
			);
		}

		$response = self::fetchCoreData( $shopId, $platform );

		if ( 200 === $response['status'] ) {
			return array(
				'page'       => 'connected',
				'testResult' => array(
					'ok'      => true,
					'message' => __( 'Connection works. Spreadshirt answered and the shop was found.', 'spreadshop' ),
				),
			);
		}

		return array(
			'page'       => 'connected',
			'testResult' => array(
				'ok'      => false,
				'message' => self::describeFailure(
					$response,
					array(
						'status' => 200,
						'shopId' => 1,
					)
				),
			),
		);
	}

	/**
	 * Unlinks the shop and drops its settings.
	 *
	 * @return array<string, string> Render instructions for the empty Connect form.
	 */
	private static function handleDisconnect() {
		Settings::deleteAll();
		return array(
			'page'     => 'initial',
			'errorMsg' => '',
		);
	}

	/**
	 * Stores the confirmed shop.
	 *
	 * The nonce is verified by handle() before this is reached, which the sniff cannot see
	 * across the call boundary.
	 *
	 * @return array<string, string> Render instructions for the connected view, or an error.
	 */
	private static function handleConfirmConnect() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified in handle().
		$shopId   = isset( $_POST['shopId'] ) ? sanitize_text_field( wp_unslash( $_POST['shopId'] ) ) : '';
		$platform = isset( $_POST['platform'] ) ? sanitize_text_field( wp_unslash( $_POST['platform'] ) ) : '';
		$locale   = isset( $_POST['locale'] ) ? sanitize_text_field( wp_unslash( $_POST['locale'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		// These validations should never fail, because the form is not free-text.
		if ( ! preg_match( '/^[0-9]+$/', $shopId ) ) {
			return array(
				'page'     => 'initial',
				'errorMsg' => __( 'Invalid shop ID', 'spreadshop' ),
			);
		}
		if ( ! Platform::isValid( $platform ) ) {
			return array(
				'page'     => 'initial',
				'errorMsg' => __( 'Invalid platform', 'spreadshop' ),
			);
		}
		if ( ! preg_match( '/^[a-z]{2}_[A-Z]{2}$/', $locale ) ) {
			return array(
				'page'     => 'initial',
				'errorMsg' => __( 'Invalid locale', 'spreadshop' ),
			);
		}
		update_option( 'spreadshopID', $shopId );
		update_option( 'spreadshopPlatform', $platform );
		update_option( 'spreadshopLocale', $locale );
		update_option( 'spreadshopMetadata', 1 );
		return array( 'page' => 'connected' );
	}

	/**
	 * Asks one platform for a shop's core data.
	 *
	 * @param string $shopIdOrName Shop name or numeric id, already validated.
	 * @param string $platform     Either 'EU' or 'NA'.
	 * @param int    $attempt      Which attempt this is; see MAX_LOOKUP_ATTEMPTS.
	 * @return array<string, mixed> Status, shop id, and on success the locales, base locale and name.
	 */
	private static function fetchCoreData( $shopIdOrName, $platform, $attempt = 1 ) {
		$url      = Platform::shopOrigin( $shopIdOrName, $platform ) . '/' . $shopIdOrName . '/shopData/core?agent=spreadshopWpPluginSignup';
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 15,
				'redirection' => 3,
				'user-agent'  => self::userAgent(),
				'headers'     => array(
					'Accept'          => 'application/json',
					'Accept-Language' => self::acceptLanguage(),
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return array(
				'status'      => -1,
				'shopId'      => null,
				'errorDetail' => $response->get_error_message(),
			);
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		if ( $status !== 200 ) {
			return array(
				'status' => $status,
				'shopId' => null,
			);
		}

		$payload = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $payload ) || ! isset( $payload['shopData']['shopId'] ) ) {
			/*
			 * Spreadshirt occasionally answers 200 with an empty body. It is a blip -- the very
			 * next request succeeds -- but reported as-is it looks to the admin like the shop is
			 * broken. The lookup is a GET and carries no side effects, so one retry is safe.
			 * Capped at two attempts: beyond that it is not a blip.
			 */
			if ( self::MAX_LOOKUP_ATTEMPTS > $attempt ) {
				return self::fetchCoreData( $shopIdOrName, $platform, $attempt + 1 );
			}

			return array(
				'status'      => -1,
				'shopId'      => null,
				'errorDetail' => __( 'Spreadshirt returned a response this plugin could not read.', 'spreadshop' ),
			);
		}

		$shopData       = $payload['shopData'];
		$responseLocale = isset( $payload['locale']['id'] ) ? $payload['locale']['id'] : '';
		$international  = ! empty( $payload['shopProps']['international'] );
		$locales        = ( $international || $responseLocale === '' )
			? self::$locales[ $platform ]
			: array( $responseLocale => $responseLocale );

		return array(
			'status'     => $status,
			'shopId'     => $shopData['shopId'],
			'locales'    => $locales,
			'baseLocale' => isset( $shopData['baseLocale'] ) ? $shopData['baseLocale'] : '',
			'shopName'   => isset( $shopData['shopUrlName'] ) ? $shopData['shopUrlName'] : '',
		);
	}

	/**
	 * Spreadshirt's edge rejects anything that does not look like a conventional web client.
	 * A bare product token is refused with HTTP 403; the parenthesised comment form, paired
	 * with the Accept-Language header below, is what earns a 200. Changing either breaks setup.
	 */
	/**
	 * Builds the User-Agent header the Spreadshirt edge accepts.
	 *
	 * @return string User-Agent header value.
	 */
	private static function userAgent() {
		return 'Mozilla/5.0 (compatible; SpreadshopWP/' . Constants::SPREADSHOP_VERSION . '; +' . home_url( '/' ) . ')';
	}

	/**
	 * Required by Spreadshirt's edge, and doubles as the language hint for the shop payload.
	 */
	/**
	 * Builds an Accept-Language header from the site's locale.
	 *
	 * @return string Accept-Language header value.
	 */
	private static function acceptLanguage() {
		$locale   = str_replace( '_', '-', get_locale() );
		$language = strtok( $locale, '-' );
		$tags     = array( $locale );
		if ( $language !== $locale ) {
			$tags[] = $language . ';q=0.9';
		}
		if ( $language !== 'en' ) {
			$tags[] = 'en;q=0.8';
		}
		return implode( ',', $tags );
	}
}
