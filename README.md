=== Spreadshop Plugin ===
Contributors: Robert Schulz, Christian Lenz, Hanna Schmidt, Stefan Drehmann, Beatrice Thom
Tags: spreadshirt,shop,spreadshop,shirt shop, t-shirt, spreadshirt plugin, spreadshirt shop, online shop, shop online, wordpress integration, e-commerce, merchandising
Requires at least: 1.0
Tested up to: 6.4.3
Stable tag: 1.6.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily integrate the Spreadshop system into your WordPress blog or business page, instantly adding a powerful merchandise channel that perfectly fits the needs of your brand.

== Description ==
With this plugin, you can easily host a top-notch ecommerce shop in WordPress while staying up-to-date with the latest features of your Spreadshop.
This is achieved by embedding your existing Spreadshop as-is into any Wordpress post, page or path.
Both the plugin and your Spreadshop are fully free of charge. Always.

**What you need**
* Installed WordPress instance
* A Spreadshop (which can be registered [here](https://www.spreadshop.com))

== Installation ==
1. Download the plugin's *.zip file.
2. Go to the plugin menu within your WP dashboard.
3. Upload the *.zip file to your content folder.
4. Activate the plugin.
5. Follow the steps in the Spreadshop admin menu.

== Frequently Asked Questions ==
= What is the Plugin for? =
This plugin is for people who are running a Spreadshop (which might look like [this](https://shop.spreadshirt.com/SpreadShop)) and want to make that Spreadshop available in their WordPress system.
If you do not have a Spreadshop yet, you can [open one free of charge](https://www.spreadshop.com).

= How do I use the plugin? =
Once you activate the plugin, you will be redirected to the plugin's admin menu. Follow the presented steps to connect your Spreadshop with your WordPress system. Info on how to embed your SpreadShop is then also given in the admin menu. The admin menu can be accessed any time from the left-hand bar.

= Is there a difference between a stand-alone Spreadshop and the plugin? =
No, this plugin will simply *embed* your Spreadshop *as-is* into your WordPress system.

= How does this work from a technical point of view? =
This plugin simply performs a "Website Integration with JavaScript" under the hood, as explained [here](https://help.spreadshop.com/hc/en-us/articles/360010529039-Website-Integration-with-JavaScript).
The advantage of using this plugin is that you do not need to write any code yourself.

= Can I use the plugin to make my Spreadshop the start page of my site? =
Most WordPress themes come with a pre-installed start page layout. The plugin is not able to overwrite this setting, but you can usually embed your Spreadshop into the start page.
If you just want to run your Spreadshop on your own domain and nothing else, we recommend not to use WordPress at all but to follow the steps described [here](https://help.spreadshop.com/hc/en-us/articles/360010529039-Website-Integration-with-JavaScript).

= Which platform am I using? =
Simply put, if you signed up on .com, .ca or .com.au, your Spreadshop runs on the North American platform. All other domains imply you are based on the European platform.
This information is only relevant to you if a shop with the same ID or name exists on both platforms and you have to specify which one is yours.

= Where can I get more support? =
Visit our [forum](https://www.spreadshop.com/forum/) and get to know other Shop Owners who can help. Spreadshop staff is also available there.

= What If I deactivate the plugin? =
Spreadshop will disappear from your WordPress system. This, however, does *not* affect your stand-alone Spreadshop in any way.

= How do I uninstall the plugin? =
1. Go to your WordPress plugin section.
2. Click "deactivate" on your Spreadshop plugin.
3. Click "delete" on your Spreadshop plugin.


== Changelog ==
### 1.6.6
* Fixed a CSRF vulnerability

### 1.6.5
* Prevent redirect to the plugin's admin page any time another (unrelated) plugin was activated.

### 1.6.4
* Prevent "noindex" from appearing in slug-based integrations when used in conjunction with the Yoast SEO plugin

### 1.6.3
* Moved to the new myspreadshop domain, explained [here](https://www.spreadshop.com/blog/2021/08/03/social-commerce-take-your-new-spreadshop-domain-to-new-heights/)

### 1.6.2
* Fixed a SEO issue where slug-based integrations returned HTTP 404 although the content was included correctly in the response body

### 1.6.1
* Enforce HTTPS requests

### 1.6.0
* Reworked admin UI entirely
* Fixed several issues with the "Optimize Url / Push State URLs" feature
* Fixed an issue where the Spreadshop would appear in unintended places
* Included an option to load Spreadshop fonts
* Optimized site speed

#### 1.5.3
* Bugfix: Set the puhStateBaseUrl direct to the shop url except additional shop tokens. Add more specific infos to the backend modul of spreadshop.

#### 1.5.2
* Update: Raise the plugin version for the current Wordpress version 5.3.*
* Bugfix: Set integer values for optimizeUrl, swipe-menu and meta-data toggles

#### 1.5.1
* New feature: possibility of shortcode insertion
* Change: Edit-button in the modul is shown as button and no longer als mouse-over

#### 1.5.0
* change styling in settings menu / update content text

#### 1.4.8
* added FAQ link and change styling for buttons

#### 1.4.7
* added SpreadShop SwipeMenu option

#### 1.4.6
* added/refactored SpreadShop Country selection

#### 1.4.5
* added SpreadShop Metadata option

#### 1.4.4
* added SpreadShop Token option

#### 1.4.3
* changed visual styles of backend SpreadShop settings
* changed backend main picture
* small improvements

#### 1.4.2
* fixed internal platform selection bug

#### 1.4.1
* fixed missing title bug

#### 1.4
* internationalization fixes

#### 1.3.1
* well, no changes again only trying to figure out what went wrong in the release process

#### 1.3
* any reference to altering the main navigation was removed as it caused severe issues with some templates

#### 1.2
* integrated better language/locale support to prevent, that shops show up empty
* added the js files that got lost in tag 1.1.1

#### 1.1.1
* fixed problems with setup of shop data that stood in conflice to other plugins
* css fix for the backend to only display "edit settings" on request

#### 1.1
* Added option to change settings w/o reinitializing plugin
* Added opttion to define top padding for shop in case navigation of theme conflicts with shop

#### 1.0
* Initial release for the plugin MVP.
* Features definition of shopID and platform to integrate a SpreadShop into your WordPress instance.
