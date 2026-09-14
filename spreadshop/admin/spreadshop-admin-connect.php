<?php

/**
 * Class SpreadshopAdminConnect
 * Renders the "Connect your Shop" interfaces and handles the form data posted from them.
 */
class SpreadshopAdminConnect {

    private static $locales = [
        "EU" => [
            "Danmark" => "da_DK",
            "Europe" => "en_EU",
            "Ireland" => "en_IE",
            "United Kingdom" => "en_GB",
            "Deutschland" => "de_DE",
            "Österreich" => "de_AT",
            "Schweiz (Deutsch)" => "de_CH",
            "Suisse (Francais)" => "fr_CH",
            "Svizzera (Italiano)" => "it_CH",
            "Espana" => "es_ES",
            "Suomi" => "fi_FI",
            "France" => "fr_FR",
            "Belgique (Francais)" => "fr_BE",
            "Italia" => "it_IT",
            "Belgie (Nederlands)" => "nl_BE",
            "Nederland" => "nl_NL",
            "Norge" => "no_NO",
            "Polska" => "pl_PL",
            "Sverige" => "sv_SE",
        ],
        "NA" => [
            "United States" => "en_US",
            "Canada (English)" => "en_CA",
            "Canada (Francais)" => "fr_CA",
            "Australia" => "en_AU"
        ]
    ];

    public static function handle($isConnected) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //Nonce check for '_wpnonce' input
            check_admin_referer(SpreadshopConstants::SPREADSHOP_SETTINGS_GROUP . '-options');
            if ($_POST['spreadshopAdminForm'] === 'connect') {
                return self::handleConnect();
            } else if ($_POST['spreadshopAdminForm'] === 'disconnect') {
                return self::handleDisconnect();
            } else if ($_POST['spreadshopAdminForm'] === 'confirmConnect') {
                return self::handleConfirmConnect();
            }
        } else {
            if ($isConnected) {
                return ['page' => 'connected'];
            } else {
                return ['page' => 'initial', 'errorMsg' => ''];
            }
        }
    }

    public static function render($renderData) {
        if ($renderData['page'] === 'initial') {
            self::renderInitial($renderData['errorMsg']);
        } else if ($renderData['page'] === 'confirm') {
            self::renderConfirm($renderData['euResponse'], $renderData['naResponse']);
        } else if ($renderData['page'] === 'connected') {
            self::renderConnected(get_option('spreadshopPlatform'), get_option('spreadshopID'), get_option('spreadshopLocale'));
        }
    }

    private static function renderInitial($errorMsg = '') {
        ?>
        <h1>Connect your Shop</h1>
        <p>Please conclude the initial shop-linking step outlined below. Instructions on how to embed your linked shop will follow.</p>
        <form id="connectform" name="connectform" method="post">
            <?php settings_fields(SpreadshopConstants::SPREADSHOP_SETTINGS_GROUP); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="shopId">Shop Name or ID</label>
                    </th>
                    <td>
                        <?php
                        if ($errorMsg) {
                            ?>
                            <div class="sprd-error-box">
                                <span>ERROR: </span><?= esc_html($errorMsg); ?>
                            </div>
                            <?php
                        }
                        ?>
                        <input type="text" class="regular-text" id="shopId" name="spreadshopIDOrName"/>
                        <p class="description">
                            Enter your Spreadshop's numeric ID (found in the Partner Area) or name.<br/>
                            The name is visible in your shop's url, for example:
                            https://<strong>this-name</strong>.myspreadshop.net/
                        </p>
                    </td>
                </tr>
                <input type="hidden" name="spreadshopAdminForm" value="connect">
            </table>
            <?php submit_button("Connect"); ?>
        </form>

        <p>
            <em>No shop yet?
                <a href="https://www.spreadshirt.com/start-selling-shirts-C3598">Register now!</a>
            </em>
        </p>
        <?php
    }

    private static function renderConfirm($euResponse, $naResponse) {
        $bothPlatforms = $euResponse['shopId'] != null && $naResponse['shopId'] != null;
        $firstEntry = $euResponse['shopId'] === null ? $naResponse : $euResponse;
        ?> <h1>Confirm the Connection</h1><?php
        if ($bothPlatforms) {
            ?>
            <table class="form-table">
                <tbody>
                <tr>
                    <th>Platform</th>
                    <td>
                        <p>We found shops matching your criteria on both platforms. Which one do you want to link?</p>
                        <fieldset>
                            <label>
                                <input type="radio" name="platformSwitch" value="EU" checked
                                       onchange="document.getElementById('confirmEU').hidden=false; document.getElementById('confirmNA').hidden=true;"/>
                                Shop <?= esc_html($euResponse['shopId'] . ' (' . $euResponse['shopName'] . ')') ?> on the European platform
                            </label>
                            <br/>
                            <label>
                                <input type="radio" name="platformSwitch" value="NA"
                                       onchange="document.getElementById('confirmEU').hidden=true; document.getElementById('confirmNA').hidden=false;"/>
                                Shop <?= esc_html($naResponse['shopId'] . ' (' . $naResponse['shopName'] . ')') ?> on the North American platform
                            </label>
                        </fieldset>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php
            self::renderConfirmForm('confirmEU', 'EU', $euResponse, false);
            self::renderConfirmForm('confirmNA', 'NA', $naResponse, true);
        } else {
            self::renderConfirmForm('', $euResponse['shopId'] === null ? 'NA' : 'EU', $firstEntry, false);
        }
    }

    private static function renderConfirmForm($formId, $platform, $response, $hidden) {
        ?>
        <form id="<?= $formId ?>" name="connectform" method="post" <?= $hidden ? 'hidden="true"' : '' ?>">
        <?php settings_fields(SpreadshopConstants::SPREADSHOP_SETTINGS_GROUP); ?>
        <table class="form-table">
            <tbody>
            <tr>
                <th><label for="shopId">Shop ID</label></th>
                <td><input id="shopId" class="regular-text" name="shopId" type="text" readonly value="<?= esc_attr($response['shopId']) ?>"/></td>
            </tr>
            <tr>
                <th><label for="shopNameIgnored">Shop Name</label></th>
                <td><input id="shopNameIgnored" class="regular-text" name="shopNameIgnored" type="text" readonly value="<?= esc_attr($response['shopName']) ?>"/></td>
            </tr>
            <tr>
                <th><label for="platform">Platform</label></th>
                <td><input id="platform" class="regular-text" name="platform" type="text" readonly value="<?= esc_attr($platform) ?>"/></td>
            </tr>
            <tr>
                <th><label for="locale">Locale</label></th>
                <td><?php self::renderLocaleSelector($response['locales'], $response['baseLocale']); ?></td>
            </tr>
            </tbody>
        </table>
        <input type="hidden" name="spreadshopAdminForm" value="confirmConnect">
        <?php submit_button("Confirm Connection"); ?>
        </form>
        <?php
    }

    private static function renderLocaleSelector($locales, $baseLocale) {
        if (count($locales) === 1) {
            ?>
            <input id="locale" class="regular-text" name="locale" type="text" readonly value="<?= esc_attr(reset($locales)) ?>"/>
            <?php
        } else {
            ?>
            <select id="locale" name="locale">
                <?php
                foreach ($locales as $name => $id) {
                    $selected = $id === $baseLocale ? 'selected' : '';
                    ?>
                    <option <?= $selected ?> value="<?= esc_attr($id) ?>"><?= esc_html($name . " " . $id) ?></option>
                    <?php
                } ?>
            </select>
            <p class="description">Influences language, currency and units of measurement.</p>
            <?php
        }
    }

    private static function renderConnected($platform, $shopId, $locale) {
        ?>
        <h1>Connected</h1>
        <p>Your can now embed your spreadshop anywhere using the shortcode <strong>[spreadshop]</strong>!</p>
        <p>
            If you want a specific page of your shop to show, you can pass a deeplink parameter.<br/>
            For example, if you want this page to show https://shop-template-brand.myspreadshop.com/cafe+koenji+logo?idea=5d5534fd2051766bd5973b60,<br/>
            use short code <strong>[spreadshop deeplink="cafe+koenji+logo?idea=5d5534fd2051766bd5973b60"]</strong>.
        </p>
        <form id="connectform" name="connectform" method="post">
            <?php settings_fields(SpreadshopConstants::SPREADSHOP_SETTINGS_GROUP); ?>
            <table class="form-table">
                <tbody>
                <tr>
                    <th><label for="platform">Platform</label></th>
                    <td><input id="platform" class="regular-text" name="platform" type="text" readonly value="<?= esc_attr($platform) ?>"/></td>
                </tr>
                <tr>
                    <th><label for="shopId">Shop ID</label></th>
                    <td><input id="shopId" class="regular-text" name="shopId" type="text" readonly value="<?= esc_attr($shopId) ?>"/></td>
                </tr>
                <tr>
                    <th><label for="locale">Locale</label></th>
                    <td><input id="locale" class="regular-text" name="locale" type="text" readonly value="<?= esc_attr($locale) ?>"/></td>
                </tr>
                </tbody>
            </table>
            <input type="hidden" name="spreadshopAdminForm" value="disconnect">
            <?php submit_button("Disconnect"); ?>
        </form>
        <?php
    }

    private static function handleConnect() {
        $shopIdOrName = $_POST['spreadshopIDOrName'];
        if (empty($shopIdOrName)) {
            return ['page' => 'initial', 'errorMsg' => 'You left the field empty!'];
        }
        if (!preg_match('/^[a-zA-Z0-9-]*$/', $shopIdOrName)) {
            return ['page' => 'initial', 'errorMsg' => 'The input you provided is neither a valid shop ID nor name!'];
        }
        $euResponse = self::fetchCoreData($shopIdOrName, 'EU');
        $naResponse = self::fetchCoreData($shopIdOrName, 'NA');
        if (!in_array($euResponse['status'], [200, 404]) || !in_array($naResponse['status'], [200, 404])) {
            // at least one of the requests "got lost"
            return ['page' => 'initial', 'errorMsg' => 'Could not reach Spreadshirt! Try again.'];
        }
        if ($euResponse['shopId'] === null && $naResponse['shopId'] === null) {
            return ['page' => 'initial', 'errorMsg' => 'Could not find any shop with this ID or name!'];
        }

        return ['page' => 'confirm', 'euResponse' => $euResponse, 'naResponse' => $naResponse];
    }

    private static function handleDisconnect() {
        spreadshopDeactivate();
        return ['page' => 'initial', 'errorMsg' => ''];
    }

    private static function handleConfirmConnect() {
        $shopId = $_POST['shopId'];
        $platform = $_POST['platform'];
        $locale = $_POST['locale'];
        // these validations should never fail because the form is not free-text
        if (!preg_match('/^[0-9]+$/', $shopId)) {
            return ['page' => 'initial', 'errorMsg' => 'Invalid shopId'];
        }
        if (!in_array($platform, ['EU', 'NA'])) {
            return ['page' => 'initial', 'errorMsg' => 'Invalid platform'];
        }
        if (!preg_match('/^[a-z]{2}_[A-Z]{2}$/', $locale)) {
            return ['page' => 'initial', 'errorMsg' => 'Invalid locale'];
        }
        update_option('spreadshopID', $shopId);
        update_option('spreadshopPlatform', $platform);
        update_option('spreadshopLocale', $locale);
        update_option('spreadshopMetadata', 1);
        return ['page' => 'connected'];
    }

    private static function fetchCoreData($shopIdOrName, $platform) {
        $tld = $platform === 'EU' ? 'net' : 'com';
        try {
            $response = wp_remote_get('https://' . $shopIdOrName . '.myspreadshop.' . $tld . '/' . $shopIdOrName . '/shopData/core?agent=spreadshopWpPluginSignup');
            $status = wp_remote_retrieve_response_code($response);
            if ($status === 200) {
                $payload = json_decode(wp_remote_retrieve_body($response), true);
                $shopId = $payload['shopData']['shopId'];
                $shopName = $payload['shopData']['shopUrlName'];
                $shopName = isset($shopName) ? $shopName : '';
                $international = $payload['shopProps']['international'];
                $baseLocale = $payload['shopData']['baseLocale'];
                $locales = $international ?
                    self::$locales[$platform] :
                    [$payload['locale']['id'] => $payload['locale']['id']];
                return ['status' => $status, 'shopId' => $shopId, 'locales' => $locales, 'baseLocale' => $baseLocale, 'shopName' => $shopName];
            }

            return ['status' => $status, 'shopId' => null];
        } catch (Exception $e) {
            return ['status' => -1, 'shopId' => null];
        }
    }

}
