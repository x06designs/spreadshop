# Spreadshop plugin — FAQ

This is the FAQ for the **community-maintained fork**. The original plugin's FAQ still exists
at [wordpress.org/plugins/spreadshop](https://wordpress.org/plugins/spreadshop/#faq), but it
documents version 1.6.6, which no longer connects to Spreadshirt, and it describes behaviour
this fork deliberately changed. Where the two disagree, this page is the accurate one.

## Using the plugin

### What is this plugin for?

It embeds an existing Spreadshop into a WordPress site. You need a Spreadshop already —
you can [open one free of charge](https://www.spreadshop.com/). The plugin does not create
or manage shops; it only displays one you already own.

### How do I use it?

1. Install and activate the plugin. You land on the Spreadshop screen automatically.
2. On **Connect**, enter your shop's name or its numeric ID and confirm the shop it finds.
3. Put the `[spreadshop]` short code on any page or post.

The **Advanced** tab adds optional settings, including an alternative url-path integration
if you would rather not use the short code.

### Is the embedded shop different from my stand-alone Spreadshop?

No. The plugin embeds your Spreadshop as it is. Products, designs, prices, payouts and the
shop's appearance are all managed in the Spreadshop Partner Area, not in WordPress.

### How does it work technically?

The plugin renders a small configuration object and a `<script>` tag that loads Spreadshirt's
shop client directly from your shop's domain. The client then draws the shop into the page.
The plugin itself does not proxy products, images or orders.

### Can I make my Spreadshop the site's front page?

Most themes own the front page layout, and the plugin does not override it. Embedding the
short code into whatever your theme uses as the front page usually works. If you only want
the shop and nothing else, you do not really need WordPress — point your domain at the
Spreadshop directly.

### Which platform am I on, EU or NA?

If you signed up on `.com`, `.ca` or `.com.au`, your shop is on the North American platform.
Every other domain means the European one. This only matters when a shop with the same name
or ID exists on both and you have to say which is yours.

### Why does a second `[spreadshop]` on the same page show nothing?

Deliberate. Spreadshirt's shop client can only drive one embed per document, so the second
short code renders nothing rather than emitting a competing configuration. Use one per page.

### The short code does not preview in the block editor

Expected. Classic short codes are not executed inside the editor's iframe. View the page on
the front end to see the shop.

## Requirements and compatibility

### What does it need?

WordPress 6.0 or newer and **PHP 8.1 or newer**.

The PHP floor is not arbitrary. Spreadshirt's servers refuse requests from older TLS stacks,
and PHP 8.0 and below ship OpenSSL 1.1.1 in the common builds. On those, connecting a shop
fails with HTTP 403 no matter what else is correct. Once a shop is connected the embed itself
is drawn by the visitor's browser, so only the setup step is affected — but setup is
unavoidable on a fresh install.

### Does it work with block themes / full site editing?

Yes, both integrations. The url-path integration builds a complete document and renders the
theme's header and footer template parts, because block themes have no `header.php` to fall
back on.

### Does it work on multisite?

Untested. Options are stored per site. Treat it as unsupported until someone verifies it.

## Privacy and GDPR

### What does the plugin load from third parties, and when?

Every page carrying the shop loads it from Spreadshirt's servers as soon as the page renders.
No visitor action is required and the plugin asks for no consent of its own. These hosts are
contacted:

- `<shop>.myspreadshop.net` (or `.com` on the North American platform) — shop client, styles, shop data
- your shop's own address, e.g. `<name>.myspreadshop.de` — the product lists the shop shows
- `image.spreadshirtmedia.net` — product and design images
- `www.spreadshirt.net` — Spreadshirt's own cookie-consent script

Each request discloses the visitor's IP address and user agent to Spreadshirt. Spreadshirt
sets a `sprdConsent` cookie and shows its own consent banner inside the shop, defaulting to
necessary-only until the visitor chooses.

With the **Product cards** layout option on, the plugin reads each product list the shop has
just loaded once more, from the same host and without cookies, to add names and prices to the
tiles; the second image on hover comes from `image.spreadshirtmedia.net`. That adds no host
to the list above.

The plugin itself sets no cookies, uses no local storage, and loads nothing from Google.

### What do I have to do about that?

Two things, neither of which the plugin can do for you:

1. Name Spreadshirt as a recipient in your privacy policy.
2. If you run a consent manager, gate the page or the short code behind it. The embed is a
   plain server-rendered `<script>` tag, so it fires unconditionally.

## Settings and data

### What happens if I deactivate the plugin?

The shop disappears from your site and your **settings are kept**, so reactivating restores
the embed without reconnecting. Your stand-alone Spreadshop is unaffected either way.

This differs from the original plugin, which deleted every setting on deactivation.

### How do I remove it completely?

Deactivate, then delete. Settings are removed at deletion. You can also press **Disconnect**
on the Connect tab to clear them while leaving the plugin installed.

### Which settings does it store?

`spreadshopID`, `spreadshopPlatform`, `spreadshopLocale`, `spreadshopToken`, `spreadshopSlug`,
`spreadshopOptimizeUrl`, `spreadshopMetadata`, `spreadshopSwipeMenu`, `spreadshopLoadFonts`.

The names are unchanged from the original plugin, so upgrading in place keeps a connected
shop connected.

## Troubleshooting

### "Could not reach Spreadshirt" or a 403 when connecting

Almost always the PHP floor described above. Check your host's PHP version; 8.1 or newer with
OpenSSL 3 is required for the setup request to be accepted.

### "Could not find any shop with this ID or name"

The lookup reached Spreadshirt and got a clean answer: no shop by that name or ID on either
platform. Check the name in your shop's url — for `https://example.myspreadshop.de/` the name
is `example` — or use the numeric ID from the Partner Area.

### The shop appears on pages it should not

The url-path integration with **Push State URLs** enabled owns every path below its base path.
It matches whole path segments, so a slug of `shop` covers `/shop/anything` but not
`/shopping-cart`. If you only want the shop on specific pages, use the short code instead.

## Support

**For this fork** — bugs, questions, anything about the plugin itself: open an issue on
[the repository](https://github.com/x06designs/spreadshop). Spreadshirt does not support it.

**For your shop** — products, designs, payouts, shop settings: Spreadshirt's own channels, via
the [Partner Area](https://partner.spreadshirt.de/) or [spreadshop.com](https://www.spreadshop.com/).
Note that the old `spreadshop.com/forum/` and `help.spreadshop.com` addresses no longer resolve.
