<?php

/**
 * Class SpreadshopAdminAdvanced
 * Renders the "Advanced" admin interface and handles the form data posted from it.
 */
class SpreadshopAdminAdvanced {

    public static function handle($isConnected) {
        if (!$isConnected) {
            return ['page' => 'notConnected'];
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //Nonce check for '_wpnonce' input
            check_admin_referer(SpreadshopConstants::SPREADSHOP_SETTINGS_GROUP . '-options');
            self::handleUpdate();
        }
        return ['page' => 'connected'];
    }

    public static function render($renderData) {
        if ($renderData['page'] === 'notConnected') {
            self::renderNotConnected();
        } else {
            self::renderConnected(
                get_option('spreadshopSlug', ''),
                get_option('spreadshopToken'),
                get_option('spreadshopOptimizeUrl'),
                get_option('spreadshopMetadata'),
                get_option('spreadshopSwipeMenu'),
                get_option('spreadshopLoadFonts')
            );
        }
    }

    private static function renderNotConnected() {
        ?>
        <div>
            Please connect your shop first before you deal with advanced settings.
        </div>
        <?php
    }

    private static function renderConnected($slug, $token, $optimizeUrl, $metaData, $swipeMenu, $loadFonts) {
        ?>
        <div id="spreadShopSettingsEdit">
            <form method="post">
                <?php settings_fields(SpreadshopConstants::SPREADSHOP_SETTINGS_GROUP); ?>
                <?php do_settings_sections(SpreadshopConstants::SPREADSHOP_SETTINGS_GROUP); ?>
                <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row">
                            <label for="startToken">Start Token (Optional)</label>
                        </th>
                        <td>
                            <input id="startToken" class="regular-text" type="text" name="spreadshopToken"
                                   value="<?= esc_attr($token); ?>"
                            />
                            <p class="description">
                                To show a specific page from your shop, enter the page url here. Only the text highlighted in the example below is necessary:
                            </p>
                            <p>https://shop-template-brand.myspreadshop.com/<strong class="sprd-highlighted-txt">cafe+koenji+logo?idea=5d5534fd2051766bd5973b60</strong>
                            </p>
                            <p class="description">
                                This is primarily useful for slug-based integrations (see below).
                                When using the shortcode, the same effect can be achieved by using the <strong>deeplink=</strong> parameter.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="metaData">Update Meta Data</label>
                        </th>
                        <td>
                            <label>
                                <input id="metaData" type="checkbox" name="spreadshopMetadata" value="1"
                                    <?= checked($metaData == 1); ?>
                                />
                                Allow Spreadshop to update your site's head section
                            </label>
                            <p class="description">
                                Spreadshop will update your site's title, description, seoIndex, as well as OpenGraph and Twitter Card tags.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="loadFonts">Load Spreadshop Fonts</label>
                        </th>
                        <td>
                            <label>
                                <input id="loadFonts" type="checkbox" name="spreadshopLoadFonts" value="1"
                                    <?= checked($loadFonts == 1); ?>
                                />
                                Use Fonts from your Shop's Partner Area
                            </label>
                            <p class="description">
                                If unchecked, the fonts from your Wordpress Theme will be used.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="swipeMenu">Mobile Swipe Menu</label>
                        </th>
                        <td>
                            <label>
                                <input id="swipeMenu" type="checkbox" name="spreadshopSwipeMenu" value="1"
                                    <?= checked($swipeMenu == 1); ?>
                                />
                                Use Mobile Swipe Menu instead of Burger Menu
                            </label>
                            <p class="description">
                                Check this option to avoid a second separate burger-menu from showing up on your page if your site already uses one.
                            </p>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <h2>Alternative slug-based integration</h2>
                <p>
                    The recommended way of embedding your Spreadshop is to use the <strong>[spreadshop]</strong> short code.<br/>
                    Alternatively, you can define a slug (url path) here to embed the Spreadshop.<br/>
                    <a href="https://wordpress.org/plugins/spreadshop/" target="_blank">Read more in our FAQ ></a>
                </p>

                <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row">
                            <label for="slug">Shop URL Path (Optional)</th>
                        <td>
                            <?= esc_html(get_home_url() . '/') ?>
                            <input id="slug" class="regular-text" type="text" name="spreadshopSlug"
                                   value="<?= esc_attr($slug); ?>"/>
                            <p class="description">Define a URL path where you want your Spreadshop to show up on your wordpress website.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="optimizeUrl">Push State URLs</label>
                        </th>
                        <td>
                            <label>
                                <input id="optimizeUrl" type="checkbox" name="spreadshopOptimizeUrl" value="1"
                                    <?= checked($optimizeUrl == 1); ?>
                                />
                                Optimize URL
                            </label>
                            <p class="description">Removes the hashbangs (as shown in the example below) from your URLs</p>
                            <p>https://mywp.example.com/myshop/<strong class="sprd-highlighted-txt">#!/</strong>cafe+koenji+logo?idea=5d5534fd2051766bd5973b60</p>
                            <p class="description">This only works when using the <strong>Shop URL Path</strong> method instead of the shortcode.</p>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <?php submit_button("Save Changes"); ?>
            </form>
        </div>
        <?php
    }

    private static function handleUpdate() {
        $slug = isset($_POST['spreadshopSlug']) ? $_POST['spreadshopSlug'] : '';
        $startToken = isset($_POST['spreadshopToken']) ? $_POST['spreadshopToken'] : '';
        $optimizeUrl = isset($_POST['spreadshopOptimizeUrl']) ? 1 : 0;
        $metaData = isset($_POST['spreadshopMetadata']) ? 1 : 0;
        $swipeMenu = isset($_POST['spreadshopSwipeMenu']) ? 1 : 0;
        $loadFonts = isset($_POST['spreadshopLoadFonts']) ? 1 : 0;
        update_option('spreadshopSlug', $slug);
        update_option('spreadshopToken', $startToken);
        update_option('spreadshopOptimizeUrl', $optimizeUrl);
        update_option('spreadshopMetadata', $metaData);
        update_option('spreadshopSwipeMenu', $swipeMenu);
        update_option('spreadshopLoadFonts', $loadFonts);
    }

}
