<?php

/**
 * Class SpreadshopAdminFrame
 * Renders header and footer of the admin section.
 */
class SpreadshopAdminFrame {

    public static function renderTop($inAdvancedTab, $isConnected) {
        ?>
        <div class="sprd-container">
            <div>
                <section class="sprd-header">
                    <div class="sprd-header--logo">
                        <img src="https://www.spreadshop.com/content/v3/assets/spreadshop_logo.svg" title="Spreadshop"
                             alt="Spreadshop">
                    </div>
                    <div>
                        <p>The Official Wordpress plugin to seamlessly integrate your shop with your wordpress website.</p>
                    </div>
                </section>
                <nav class="nav-tab-wrapper">
                    <a href="?page=Spreadshop" class="nav-tab<?=$inAdvancedTab ? '' : ' nav-tab-active'?>">Connect</a>
                    <?php if($isConnected) {
                        ?>
                        <a href="?page=Spreadshop&tab=advanced" class="nav-tab<?=$inAdvancedTab ? ' nav-tab-active' : ''?>">Advanced</a>
                        <?php
                    }
                    ?>
                </nav>
                <section class="sprd-settings wrap">
        <?php
    }

    public static function renderBottom($isConnected) {
            $previewLink = '';
            $slug = get_option('spreadshopSlug');
            if($isConnected && $slug) {
                $previewLink = '<a href="' . esc_attr(get_home_url() . '/' . $slug) . '" target="_blank">Preview Shop</a>';
            }
            ?>
                </section>
            </div>
            <div class="sprd-links">
                <?=$previewLink?>
                <a href="https://wordpress.org/plugins/spreadshop/ " target="_blank">Read more in our FAQ ></a>
            </div>
        </div>
        <?php
    }
}
