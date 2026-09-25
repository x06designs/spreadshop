# Spreadshop for WordPress

[![CI](https://github.com/x06designs/spreadshop/actions/workflows/ci.yml/badge.svg?branch=develop)](https://github.com/x06designs/spreadshop/actions/workflows/ci.yml)
[![License: GPL v2](https://img.shields.io/badge/license-GPL--2.0-blue.svg)](LICENSE)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb4.svg)](https://www.php.net/)

Embed a Spreadshop into a WordPress site — products, cart and checkout, served by
Spreadshirt, rendered inside your own pages.

> **This is a community-maintained fork.** The official plugin was last released in February
> 2024 and no longer connects to Spreadshirt: their servers now reject the requests it makes.
> This fork fixes that, along with a number of defects the original shipped with. See the
> [changelog](#changelog).

---

## What it does

You already have a Spreadshop. This plugin puts it on your WordPress site, two ways:

| Method | Use it when |
|---|---|
| `[spreadshop]` short code | You want the shop inside a page you already control. **Recommended.** |
| Slug-based route | You want the shop to own a url path of its own, e.g. `/shop`. |

Products, designs, prices and payouts stay in the Spreadshop Partner Area. This plugin
displays what is already there and, if you want, restyles the shop's navigation, footer,
product tiles and product page so they take on your theme's fonts and colours (see
[Layout](#layout)).

## Requirements

| | |
|---|---|
| WordPress | 6.0 or newer — tested up to 7.1 |
| PHP | **8.1 or newer** |
| A Spreadshop | [free to open](https://www.spreadshop.com/) |

The PHP floor is not arbitrary. Spreadshirt's servers refuse older TLS stacks, and PHP 8.0
and below ship OpenSSL 1.1.1 in the common builds — on those, connecting a shop fails with
HTTP 403 no matter what else is correct.

## Installation

1. Download `spreadshop-<version>.zip` from the
   [releases](https://github.com/x06designs/spreadshop/releases).
2. **Plugins → Add New → Upload Plugin**, choose the zip, install and activate.
3. You land on the Spreadshop screen. Enter your shop's name or numeric ID and confirm.
4. Put `[spreadshop]` on any page or post.

Your shop's name is the first part of its url — for `https://example.myspreadshop.de/` it is
`example`. The numeric ID works too, and is in the Partner Area.

## Settings

Everything below is optional and lives on the **Advanced** tab.

| Setting | What it does |
|---|---|
| Start Token | Opens a specific shop page instead of the front page |
| Update Meta Data | Lets the shop set the page title, description and social tags |
| Load Spreadshop Fonts | Uses the shop's own fonts instead of your theme's |
| Mobile Swipe Menu | Swipe navigation instead of a second burger menu |
| Shop URL Path | Serves the shop at a path of its own, instead of the short code |
| Push State URLs | Drops the `#!/` from shop urls. Needs the path method |

### Layout

Also on the **Advanced** tab. Every option is off by default, each works on its own, and
switching one off restores the shop exactly as Spreadshirt draws it.

| Setting | What it does |
|---|---|
| Sidebar navigation | Logo, search and basket as a bar across the shop, the category tree as a sidebar with foldable departments. On small screens: a "Categories" button that opens the tree |
| Compact footer | The footer's link columns as an even grid, payment marks below. The service and legal links stay |
| Product cards | Adds the product name and the chosen card fields (product type, price, colour swatches, sizes, second image on hover) to the product tiles |
| Product page | Separates the product page's sections, shows description and size guide as tabs, tidies the design, tags and sharing block |
| Card fields | Which fields a card shows, and in which order |
| Start page | Open on the design list (the shop default) or on all products |
| Colours | Accent, text, background and lines. Empty means "use the theme's" |

A theme can set the same colours as CSS custom properties, which a colour chosen here
overrides: `--spreadshop-accent`, `--spreadshop-accent-text`, `--spreadshop-background`,
`--spreadshop-text`, `--spreadshop-muted`, `--spreadshop-border`, plus `--spreadshop-hover`,
`--spreadshop-badge`, `--spreadshop-badge-text`, `--spreadshop-focus`,
`--spreadshop-font-display`, `--spreadshop-sticky-offset` and `--spreadshop-sidebar-width`.

The layout options restyle markup Spreadshirt does not document. If a Spreadshirt release
changes it, the affected option can look off until the plugin follows; product cards fall
back to the plain image tile rather than showing half a card.

A page may contain **one** `[spreadshop]`. A second renders nothing, because Spreadshirt's
shop client can only drive one embed per document.

## Privacy

The shop is loaded from Spreadshirt's servers as soon as the page renders, which discloses
your visitor's IP address and user agent to them. The plugin sets no cookies of its own and
loads nothing from Google.

With **Product cards** on, the plugin reads each product list the shop loads a second time,
from the same Spreadshirt host, and the second image on hover is fetched from Spreadshirt's
image server. No other host is added and nothing is stored.

If your site is subject to the GDPR you need to name Spreadshirt as a recipient in your
privacy policy, and gate the page behind your consent manager if you run one. The
[FAQ](FAQ.md#privacy-and-gdpr) lists exactly which hosts are contacted.

## Documentation

**[Read the FAQ →](FAQ.md)** — usage, block themes, multisite, privacy, troubleshooting the
403, and where support lives.

## Development

The plugin has no runtime dependencies and no build step: `spreadshop/` is exactly what
ships. Everything else lives at the repository root, outside the plugin directory, so nothing
has to be stripped before release.

```bash
composer install

composer lint        # WordPress Coding Standards
composer fix         # auto-fix what phpcbf can
composer phpstan     # static analysis, level 6
composer test:unit   # unit suite, no WordPress required
composer check       # all three
composer i18n        # regenerate .pot, merge the .po, compile .mo/.l10n.php/JS .json
composer i18n:check  # fails on stale translation files or untranslated strings

npm ci
npm run lint         # Biome: lint + format check (warnings fail)
npm run typecheck    # tsc over the plain JS via JSDoc (checkJs)
npm run gate:sinks   # fails on innerHTML/outerHTML/insertAdjacentHTML/document.write
npm test             # node:test suite in tests/js
npm run check        # all four

# Browser checks against a page that embeds the shop with every layout option on
BASE_URL=https://example.lndo.site/shop/ npm run e2e    # fixture lists + live data
BASE_URL=https://example.lndo.site/shop/ npm run smoke  # before a release: Spreadshirt's markup and list data

./scripts/package-plugin.sh   # -> dist/spreadshop/ and dist/spreadshop-<version>.zip
```

The JavaScript ships unbuilt, as classic scripts: the npm tooling checks it but never
transforms it, so `spreadshop/` stays exactly what ships.

The unit suite runs on Brain Monkey and needs no WordPress install. Anything that genuinely
depends on WordPress — the admin screens, upgrading in place, block-theme rendering, the SEO
plugin interaction — is verified by running the plugin in a real WordPress rather than by
mocking one.

CI runs lint, static analysis, the unit suite on PHP 8.1 and 8.4, `composer audit`,
`npm run check`, `npm audit` and the packaging script on every push and pull request.

## Licence and attribution

GPL-2.0-or-later. See [LICENSE](LICENSE) — the same text ships inside the plugin at
`spreadshop/LICENSE`, because the distributed plugin has to carry its own copy.

Originally written by Robert Schulz (sprd.net AG) and Stefan Drehmann (IronShark GmbH),
copyright © sprd.net AG and IronShark GmbH. Modified from 1.7.0 onward by X-06 Designs.

Spreadshop and Spreadshirt are trademarks of sprd.net AG. This fork is not endorsed by,
affiliated with, or supported by sprd.net AG or IronShark GmbH.

---

## Changelog

### 1.8.1
* Hardening: every setting is now cleaned on the way in whichever route wrote it. Registering an option also exposes it to WordPress's own settings handler, which does not pass through this plugin's forms, so their validation did not run on that path. A stored shop ID decides which host the shop is loaded from, so this matters most there
* The connection test re-checks the stored shop details before contacting Spreadshirt rather than trusting what it reads back
* Saving the Advanced tab no longer errors if a field arrives as an array instead of text

### 1.8.0
* The slug-based integration no longer presents itself as a 404 to SEO plugins. Yoast marked those pages noindex and Rank Math titled them "Page Not Found"; correcting the underlying request state fixes both, and any other SEO plugin, at once
* Added a **Test connection** button, so a future change at Spreadshirt's end can be diagnosed on demand instead of surfacing as a shop that quietly stopped loading
* A one-off empty response from Spreadshirt is retried once rather than reported as a broken shop
* The shop's origins are announced in the page head, so the browser can open those connections while the shop client is still downloading. The image server alone takes about 300ms to reach and was previously not discovered until products began rendering
* German translation
* The slug page's title now keeps your site name instead of replacing the whole title with the slug
* The settings error box is announced to screen readers, and the platform chooser is a properly labelled group
* The admin menu icon is now a sharp SVG instead of a 20x20 bitmap
* No inline JavaScript remains in the admin screen

### 1.7.0
* Fixed shop connection failing with "Could not reach Spreadshirt": Spreadshirt's edge rejects requests that carry no browser-style User-Agent and no Accept-Language header
* Deactivating the plugin no longer deletes your configuration — settings are now removed on uninstall only
* Replaced the settings header logo, whose hosted asset no longer exists, with a text wordmark
* Connection errors now report the actual cause instead of a blanket "could not reach"
* Fixed the shortcode guard, which never engaged and re-emitted the shop for every [spreadshop] on a page
* The post-activation redirect no longer fires during bulk activation, WP-CLI or cron
* Removed PHP 8 warnings from unguarded request and response array access
* Setup requires PHP 8.1 or newer: older TLS stacks (OpenSSL 1.1.1) are refused by Spreadshirt
* Fixed the slug-based integration on block themes, where it rendered no site header or footer and, lacking a doctype, made the shop replace itself with an error
* Fixed the slug-based integration presenting itself as a 404 to SEO plugins: Yoast marked the page noindex and Rank Math titled it "Page Not Found". Both are now correct, as is any other SEO plugin, because the underlying 404 state is corrected rather than patched per plugin
* Fixed Push State URLs claiming unrelated pages: a slug of "shop" also captured /shopping-cart and every other path merely starting with those letters
* Fixed the four Advanced checkboxes emitting invalid markup, and a broken label that stopped "Shop URL Path" responding to clicks
* The admin screen is now translatable, with a translation template in languages/
* Removed the unused spreadshopNaviEntry setting

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

### 1.5.3
* Bugfix: Set the puhStateBaseUrl direct to the shop url except additional shop tokens. Add more specific infos to the backend modul of spreadshop.

### 1.5.2
* Update: Raise the plugin version for the current Wordpress version 5.3.*
* Bugfix: Set integer values for optimizeUrl, swipe-menu and meta-data toggles

### 1.5.1
* New feature: possibility of shortcode insertion
* Change: Edit-button in the modul is shown as button and no longer als mouse-over

### 1.5.0
* change styling in settings menu / update content text

### 1.4.8
* added FAQ link and change styling for buttons

### 1.4.7
* added SpreadShop SwipeMenu option

### 1.4.6
* added/refactored SpreadShop Country selection

### 1.4.5
* added SpreadShop Metadata option

### 1.4.4
* added SpreadShop Token option

### 1.4.3
* changed visual styles of backend SpreadShop settings
* changed backend main picture
* small improvements

### 1.4.2
* fixed internal platform selection bug

### 1.4.1
* fixed missing title bug

### 1.4
* internationalization fixes

### 1.3.1
* well, no changes again only trying to figure out what went wrong in the release process

### 1.3
* any reference to altering the main navigation was removed as it caused severe issues with some templates

### 1.2
* integrated better language/locale support to prevent, that shops show up empty
* added the js files that got lost in tag 1.1.1

### 1.1.1
* fixed problems with setup of shop data that stood in conflice to other plugins
* css fix for the backend to only display "edit settings" on request

### 1.1
* Added option to change settings w/o reinitializing plugin
* Added opttion to define top padding for shop in case navigation of theme conflicts with shop

### 1.0
* Initial release for the plugin MVP.
* Features definition of shopID and platform to integrate a SpreadShop into your WordPress instance.
