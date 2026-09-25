# Shop layout integration — plan

Status: **Goals confirmed (v3). Phases 1–5 done, phase 6 in progress.** Branch
`feature/shop-layout` (from `develop`), nothing committed yet.

Progress (2026-09-24):
- Phase 1 done: npm root, Biome (`--error-on-warnings`), `tsc` checkJs, DOM-sink gate with
  tests, schema → `types/layout.d.ts` drift gate, CI `javascript` job, packager guards.
  `admin.js` reformatted to the house style (own commit when committing).
- Phase 2 done: schema, `Layout\Schema`, sanitisers + accessors, `deleteConnection()`/`deleteAll()`,
  `EmbedDetector`, Renderer wrapper classes + start-list precedence. 149 tests / 258 assertions.
  Deviation: sanitisers filter by hand against the schema's enums/patterns (valid by
  construction, unit-testable without WordPress) instead of `rest_validate_value_from_schema()`.
- Phase 3: `Admin\LayoutSection` (toggles, card fields, start page, colours) wired
  into the Advanced tab; `admin-colors.js` (core `wp-color-picker`); `admin-card-fields.js`
  (chip combobox). `AdvancedTab`'s render
  signature was left as is: the section reads its own settings.
- Phase 3 done (2026-09-25): chip CSS in `style/style.css`; i18n pipeline `scripts/i18n.sh`
  (`composer i18n` / `i18n:check`, CI job "i18n", packager requires the JS .json). `update-po`
  rewrites the .po header, so the script restores the committed header; the .po carries a pinned
  `POT-Creation-Date` because gettext otherwise stamps the current time into .mo/.l10n.php.
  Gate proven to fail on a changed string. `Frame` gained `hr.wp-header-end`: core notices
  otherwise landed under the "Layout" heading. Browser pass on dejok.lndo.site (working-copy
  build copied over the composer-installed 1.8.1; `composer install` there restores it):
  German JS strings load, combobox add/Alt+↑/save order, colour picker save verified.
- Phase 4 done (2026-09-25): one stylesheet per toggle under `style/layout/` plus shared
  `tokens.css`; `Layout\Assets` (head enqueue via `EmbedDetector`, late enqueue from
  `Shortcode::render`, once per request) and `Layout\Tokens` (admin colours on `#myShop`,
  `sanitize_hex_color` on output). Biome lints/formats the layout CSS. Selectors are prefixed
  `.spreadshop-layout--x #myShop .SprdMain`: the client appends its own stylesheets after ours, so
  ties would lose. Token fallbacks end in the client's `--sprd-*` palette (unchanged look with no
  colour chosen). CSS-only tokens beyond the admin six: `--spreadshop-badge`, `-badge-text`,
  `-focus`, `-font-display`, `-sticky-offset`, `-sidebar-width`. Desktop breakpoint 1000px
  (the client's own). Found during the browser pass: department pages carry a desktop facet
  column `nav.sprd-filterpane`; user chose to hide it (sidebar toggle, ≥768px) and use the
  "Filtern" button, which opens the client's full-screen filter panel. Sticky sidebar cannot stop
  at the footer (its containing block is `#sprd-container`), so the footer paints over it.
  Compact footer keeps every link (product column included) and the "open your own shop" link.
- Phase 5 started (2026-09-25): `js/layout-observe.js` (the observer seam, `window.spreadshopLayout.onRender`)
  and `js/layout-nav.js`, loaded with the sidebar option. Departments with sub-pages get a
  +/− button (aria-expanded/aria-controls); the current department opens itself; state is kept
  per department link across the client's redraws. Below 1000px a "Kategorien" button shows
  or hides the tree (user chose this over per-item dropdowns); Escape closes it and returns
  focus, following a link closes it (capture phase: the client's router stops bubbling). With
  Mobile Swipe Menu off the client's burger stays in charge and no toggle is added (verified).
  Without the script the mobile row stays the horizontal scroller. Mobile active-filter chips
  no longer shrink to 48 % (ellipsis) and the list aligns with the heading.
- Filter button moved into the desktop top bar (column 4 of row 1) so long listing titles can
  no longer run under it; titles hyphenate.
- Phase 5 continued: `js/layout-data.js` (pure card model, 18 node:test cases on trimmed public
  fixtures `tests/js/fixtures/`) and `js/layout.js` (Resource Timing → re-fetch → cards).
  Deviations: the list is served from the shop's own host (e.g. stechmuecke.myspreadshop.de),
  not the configured origin, so the filter is `*.myspreadshop.*` + `/shopData/list`; tiles are
  keyed by article id **and** product type (one design id is shared across product types); the
  model is loaded in tests through `node:vm` (the repo is ESM, so no CommonJS guard). The client
  already draws the "Stick" badge itself, so `stickBadge` only adds one where the client did
  not, and turning it off does not remove the client's. `js/layout-product.js` (product-page
  option): description/size guide as ARIA tabs (arrow keys, Home/End), sold-out sizes
  `aria-disabled`. `layout-nav.js` also names search/basket (with count) and the filter close
  button in the site language, and closes the filter dialog on Escape. axe on list/detail/open
  tree at 1440 + 390 px: only the client's landmark/heading findings remain.
- Phase 6 (2026-09-25): `@playwright/test` 1.63.0 (dev). `npm run e2e` = fixture specs (lists
  served from tests/js/fixtures via page.route; the plugin's re-fetch can get a different
  fixture than the client, which is how the malformed-data fallback is tested) + live specs;
  `npm run smoke` = list shape, CORS, required classes, detail columns. Live and smoke run
  headed: Spreadshirt's edge fails the CORS preflight of the client's own list request for
  HeadlessChrome client hints. Not in CI (needs a deployed site). Found by the specs: the client
  marks tile links role="button"; the cards script removes it (restored with the card), and the
  observer now also watches `href` so recycled tiles redraw. Security review (OWASP subagent):
  no findings. Docs: README layout section, privacy note, dev commands, 1.9.0 changelog; FAQ
  hosts; modernization.md note. Version 1.9.0; i18n gate now also checks the .po header version.
- Settings review before release (user asked): every setting checked against the layout
  options on dejok. Load Spreadshop Fonts: shop font applies throughout, cards included (works;
  intro text now says so). Shop URL Path + Push State: sidebar, cards (47/47) and tabs work,
  also after reload. Mobile Swipe Menu off: the client's burger stays, no Categories toggle —
  admin text now explains both states with the sidebar on. Embroidery badge field removed (the
  client draws the badge itself; the field could neither add nor hide it). Hints added: card
  fields apply with Product cards, colours with any layout option. Temporary dejok settings
  restored (fonts 0, slug empty, push state 0, swipe 1).
- Mutation check (contextless subagent, 72 mutants): 47 killed, 7 equivalent, 18 gaps → tests
  added (data model edge cases, colour trim, schema throw, asset files/dependencies/inline
  position/translations). Search and basket get visible i18n labels there; nav expand/collapse and
  the mobile "Kategorien" toggle too.
- Basket count in the theme menu: spiked (client keeps the basket id in `localStorage.shpbskt`;
  the basket API answers cross-origin without auth), user declined. Not built.
- Review round (2026-09-25, user feedback on dejok), decisions:
  - Desktop: logo, search and basket form one bar across the shop; the category tree is the
    sticky sidebar below it (`.sprd-header-container`/`.sprd-header` are `display: contents`).
  - Compact footer: the four link columns as an even grid with small headings, payment row
    below; below 768px the client's accordion stays. The seller notes stay in the Kontakt column
    (they are its markup).
  - Filter panel: centred modal with a scrim at 768px+.
  - New toggle `spreadshopLayoutProductPage`: separated sections, product-view gallery hidden
    (repeats the top images), design/tags/share on one grid, smaller share icons, readable
    out-of-stock sizes (line-through + dashed, not 25 % opacity). Tabs for description / size
    guide come with phase 5 (JS).
  - Fixes: listing H1 no longer clipped (wraps, line-height 1); nav hover = `--spreadshop-hover`
    (default accent) plus underline; active filters span the grid row; design-tile text no longer
    at 40 % opacity; narrow-column overflow of the detail buttons and suggestion names; theme
    `button:hover` (white text) neutralised on the client's ghost buttons; the plugin's own
    `<main>` wrapper is now a `<div>` (duplicate main landmark).
  - A11y pass (axe-core 4.10 on `#myShop`, list/detail/filter modal, 1440 + 390 px, plus a
    text-contrast sweep and focus-visible check): no contrast failures left; remaining findings
    are client markup — its `main#sprd-content` sits inside the theme's main, footer h3 after h1,
    the filter modal's close button has no name and Escape does not close it, out-of-stock sizes
    are not marked disabled. Candidates for phase 5 JS (labels, Escape, aria-disabled).
  - Follow-up round: sidebar no longer sticky or scrolling; its rows end in a `1fr` track so a
    tall tree lengthens the page without spreading the content rows. Search is a square icon
    button like the basket (the client only renders its input after the button is pressed, so a
    real field would need JS driving the client). Active nav items keep their inset on hover;
    tag chips centred (the client fixes `.sprd-link` at 1.6em); share buttons get a round hover.
  - dejok must map `--spreadshop-hover` to its teal-deep: magenta text on paper is ~2.3:1.
- Unverified: whether the client accepts `?listModeOverride=PRODUCT` as `startToken` in the
  config object (verified only as a hash route).

Goals-gate decisions: lint/type gates use public `@biomejs/biome` + `typescript` with in-repo
configs written to the house conventions (the `@alchemisten/*` configs are restricted and
UNLICENSED; this repo is public GPL). Product type is shown exactly as delivered, suffix such as
"CREATOR" included.

Design source: `shop-mockup.html` in the dejok OpenDesign project (approved). First consumer:
dejok.lndo.site. The feature is generic: nothing dejok-specific ships in the plugin.

## Problem

The embed renders the Spreadshop client's own chrome (header bar, four-column dark footer,
image-only tiles) inside the host theme. It shares no colour or type with the site, and the
list tiles carry no name or price although the shop's own data has both.

## Facts established

### Runtime

- The shop draws into `#myShop` in the host document: no iframe, no shadow root, BEM `sprd-*`
  classes. Host CSS applies. The class names are not a published API.
- The client is an SPA: every navigation re-renders; pushState navigations fire no event.
- List endpoint: `{origin}/{shopName}/shopData/list?query=&locale=&version=&size=&color=&collection=&idea=&listModeOverride=&page=`.
  CORS `access-control-allow-origin: *`, `cache-control: max-age=0` (a re-fetch is a real
  request, ~11 kB gzipped). Per article: `id`, `pureArticleName`, `name`, `priceFormatted`,
  `hoverImageUrl`, `displayEmbroideryBadge`, `productTypeId`, `excludedAppearanceIds`; per
  product type: appearances with `name`, `colors[]`, `sizes[]`, `inStock`. Top level:
  `designBased`, `numberOfPages`, `page`. No total product count.
- `?listModeOverride=PRODUCT` as start token opens "Alle Produkte" (verified, 96 tiles).
- Page 1 of every list is a network request, not bundled state: `browser_network_requests`
  showed `shopData/list?query=D3…&page=1` on a cold load of `#!/frauen?q=D3`.
- Tile markup: `.sprd-product-list-item > __hoverarea > a.__link[href="#!/<slug>-A<articleId>?…"] > __image > img[alt]` plus `__badge-container`. No text.

### Markup inventory (desktop 1440 px and mobile 390 px, list route)

| Mockup element | Stock markup | Verdict |
|---|---|---|
| Sidebar column | `header.sprd-header-container > .sprd-header` (logo, `.sprd-navigation`, `.sprd-header__actions`) | CSS |
| Category tree | nested `.sprd-department-filter__departments > __openmenu > a.sprd-nav-link + __menu > a.__entry`, active `.sprd-nav-link--active`; all in DOM | CSS layout + our JS for expand/collapse buttons |
| Search field | `.sprd-search__button` opens an overlay | **Not buildable as a field** — user chose: field-shaped full-width "Suchen" button opening the overlay |
| Basket with count | `.sprd-basket-indicator` + count wrapper | CSS |
| Mobile bar + "Kategorien" | navigation visible, departments hidden on mobile | CSS + our toggle |
| Breadcrumb, H1 | `.sprd-breadcrumb-nav`, `.sprd-listpage__title` | CSS |
| Product count under H1 | none, and no total in the data | **Not buildable** — user chose: dropped |
| Filter chips | `.sprd-mobilefilter__open-btn` opens a panel | **Not buildable as chips** — user chose: one chip-styled "Filter" button |
| "Mehr Produkte laden" | `.sprd-pagination` numbered ("Seite 1 von 2") | **Not buildable** — user chose: numbered pagination restyled |
| Card name, price, swatches | not in DOM; in list data | our JS |
| Card product type | only inside `name` ("<pure> - <type>") | derived by stripping `pureArticleName + " - "`, then `trim()`; shown only when that prefix matches exactly, else omitted (user chose). Trailing model suffix such as "CREATOR": open, see goals |
| Card sizes | per appearance | in-stock union across appearances left after `excludedAppearanceIds`, catalogue order, shown as first–last |
| "Neu" / "Stick" badge | `__badge-container` (stock) / `displayEmbroideryBadge` | stock badge restyled; "Stick" from data |
| Hover image | `hoverImageUrl` | our JS |
| Compact footer | `.sprd-footer` > `.sprd-info-footer`, `.sprd-legal-footer`, `.sprd-service-footer` | CSS; legal links stay |

## Decisions taken

| Topic | Decision |
|---|---|
| Where it lives | This plugin, as generic options. dejok maps its tokens in its own CSS |
| Colour and type | `--spreadshop-*` custom properties; fonts inherit from the theme; optional admin colour pickers |
| Layout switches | Separate toggles: sidebar navigation, compact footer, product cards |
| Start page | Option designs/products. Precedence: shortcode `deeplink` > Start Token > start list |
| Card fields | Chip combobox, checkbox fallback. Name always first; remaining fields in chosen order |
| Anatomy | Selective Alchemisten adoption (below) |

## Alchemisten adoption

Adopted, because the plugin now ships JavaScript and CSS of its own:

- **Biome** (`@alchemisten/biome-config`) and **type-checked JS** (`@alchemisten/tsconfig`,
  `checkJs`, JSDoc) over the new files in `spreadshop/js/`. Plain JS ships unbuilt, so
  "`spreadshop/` is exactly what ships" and zip-from-repo still hold. `admin.js` is brought
  under the gates in its own commit.
- **JSON-Schema contract** `spreadshop/schema/layout.schema.json` (WP REST subset). PHP
  filters items/values by hand against the schema's enums and patterns, then confirms with
  core `rest_validate_value_from_schema()`; a sanitiser never returns `WP_Error` (invalid →
  previous value + `add_settings_error`). The decoded schema is cached in a static. `types/layout.d.ts` at the repository root (json-schema-to-typescript, committed)
  types the JS; `npm run drift` fails on a regeneration diff.
- **`node:test`** for the pure model against committed fixtures. No new runner.
- npm tooling at the repository root beside Composer's; committed `package-lock.json`,
  `npm ci`, `npm audit` in CI.

Not adopted: Nx, Inpsyde Modularity, Vite build, React/DataViews admin — the reasons in
`modernization.md` still hold for them. That file gets a note that its "ships zero
JavaScript" premise is superseded by this plan.

## Settings (new options)

Added to `Constants::SPREADSHOP_OPTIONS` and `Settings::sanitizers()`. Every option has a
read accessor on `Settings` returning the schema default when the row is absent, because
`register_setting` defaults only exist on `admin_init`. Defaults keep 1.8.1 behaviour exactly.

| Option | Type | Default |
|---|---|---|
| `spreadshopLayoutSidebar` | toggle | 0 |
| `spreadshopLayoutCompactFooter` | toggle | 0 |
| `spreadshopLayoutCards` | toggle | 0 |
| `spreadshopCardFields` | unique ordered list, enum `productType`, `price`, `swatches`, `sizes`, `hoverImage` (`stickBadge` retired before 1.9.0; a stored value is dropped on read) | `["price","swatches","hoverImage"]` |
| `spreadshopStartList` | enum `designs`, `products` | `designs` |
| `spreadshopColors` | object, keys enum `accent`, `accentText`, `background`, `text`, `muted`, `border`; values `#rrggbb` | `{}` |

Sanitising rules: sanitisers are idempotent (they run twice via `update_option`); input is
`wp_unslash`ed; the plugin form posts a hidden empty sentinel so "no fields" is distinct from
absent (sentinel stripped); `null`/absent on the `options.php` path means default; unknown ids,
duplicates, non-strings are dropped;
`hoverImage` is canonicalised to the end (it sits on the image, order has no
effect). Colours pass `sanitize_hex_color` on save **and** on output. Layout options survive
Disconnect (they are not shop data) and are removed on uninstall — `deleteAll()` splits into
`deleteConnection()` (Disconnect) and `deleteAll()` (the global `spreadshopDeleteSettings()`
uninstall shim), each unit-tested.

## Components

- `includes/Embed/EmbedDetector.php` — the "will an embed render on this request" predicate,
  moved out of `ResourceHints` (private today) and shared.
- `includes/Embed/Renderer.php` — wrapper gains `spreadshop-layout--{sidebar,compact-footer,cards}`
  classes; start-list token applied per the precedence above.
- `includes/Layout/Assets.php` — enqueues `style/layout.css`, `js/layout-data.js`,
  `js/layout.js` at `wp_enqueue_scripts` when the detector holds and a toggle is on; late
  enqueue from `Shortcode::render` as fallback. Config via `wp_add_inline_script` +
  `wp_json_encode`. `wp_set_script_translations` for JS strings.
- `includes/Layout/Tokens.php` — prints admin picks as `--spreadshop-*` on `#myShop` only.
  Defaults live solely as `var(--spreadshop-x, <default>)` fallbacks in `layout.css`, so a
  theme mapping on `:root` beats the defaults and an explicit pick beats both.
- `style/layout.css` — every rule scoped under `.spreadshop-layout--<toggle>`.
- `js/layout-data.js` — pure, classic script exposing `window.spreadshopLayout` with a
  CommonJS guard for `node:test`. No i18n, no DOM: validates the list payload (unknown shape →
  "no enrichment", warn once) and returns the card model with raw counts and colour names
  (derivations per the inventory table; swatches exclude `excludedAppearanceIds`).
- `js/layout-observe.js` — the one observer seam both DOM scripts use: a MutationObserver on
  `#myShop`, the `data-spreadshop` marker, ignore-our-own-mutations, re-inject on re-render.
- `js/layout.js` — thin DOM adapter for cards. `PerformanceObserver({type:'resource',
  buffered:true})` filtered to the shop origin + `/shopData/list`, deduped by URL, re-fetches
  that exact URL (routes, filters, search, locale, page and pushState covered without
  rebuilding a query). `AbortController` per route plus a timeout; no retry; no storage. Tiles
  matched by article id `A<id>` in the link; skipped when `designBased`, and on
  detail/basket/checkout. Per tile: the name goes as text inside the existing anchor and the
  image `alt` becomes `""` (restored if enrichment is removed); price, type, sizes and swatches
  go in one owned container **outside** the anchor, recording the article id; mismatch
  re-enriches (node recycling). Text via `textContent`/`createElement` only; images only from
  `https://image.spreadshirtmedia.net/`; hex via `style.setProperty` after a strict check.
  Swatches get an `aria-label` with count (`_n`) and colour names. Depends on `wp-i18n`
  (frontend weight noted in the FAQ); `layout-data.js` does not.
- `js/layout-nav.js` — expand/collapse buttons (`aria-expanded`) for the sidebar tree and the
  mobile "Kategorien" toggle, through the shared observer seam. Must not break the client's
  own menu handlers, with Mobile Swipe Menu on or off.
- `js/admin-card-fields.js` — upgrades the checkbox group to an ARIA 1.2 combobox with chips;
  hidden inputs posted in chip order. Each chip has visible "move up"/"move down"/"remove"
  buttons (i18n labels); Alt+↑/↓ as accelerator with `preventDefault`; Backspace removes the
  last chip only when the input is empty. Instructions via `aria-describedby`, changes
  announced in a polite live region; all strings through i18n.
- `includes/Admin/AdvancedTab.php` — "Layout" section; `handleUpdate()` and the render
  signature extended (the six positional arguments become one settings array). The cards
  toggle's description says the design list (the shop default start page) shows no cards.

## Failure behaviour

Enrichment is additive. Endpoint, CORS or markup change → tiles fall back to the stock
image-only tile, never a half card. CSS is scoped to the toggles, so switching them off
restores the stock shop exactly.

## Gates

- Existing: `composer check` (WPCS, PHPStan 6, PHPUnit unit) — extended: sanitiser cases
  (nested arrays, unknown ids, duplicates, non-strings, null, idempotence, hostile colour
  keys/values), accessor defaults for absent rows, Renderer classes, start-list precedence.
- New `npm run check`: Biome, `tsc --noEmit`, `node --test`, schema drift, and a grep gate
  failing on `innerHTML`/`outerHTML`/`insertAdjacentHTML`/`document.write` in `spreadshop/js/`.
- i18n gate (`composer i18n:check`, CI job "i18n"): WP-CLI via composer dev dependency
  `wp-cli/i18n-command`; `make-pot` over PHP + JS compared to `spreadshop.pot` with headers
  stripped (msgid/msgstr only); `make-json` output `languages/spreadshop-de_DE-<md5>.json`
  committed, drift-checked and on the packager's required-file list.
- `npm run e2e` (Playwright, dev-only, `BASE_URL` env). Two modes:
  - **fixture mode, deterministic data (the client script itself still loads live):** `page.route()` serves the committed
    fixtures for `shopData/list` (product list, design list, empty, malformed); asserts
    enrichment, fallback, node recycling, observer ignoring our own nodes, name/alt in the
    accessibility tree. This is what the mutation check runs against.
  - **live mode:** each toggle on/off at 1440 and 390 px; legal links visible and focusable
    with the compact footer; client menus still work with sidebar on and Swipe Menu on/off;
    keyboard-only combobox pass incl. move/remove buttons; screenshots to `.playwright-mcp/`.
- `npm run smoke` (Playwright spec, pre-release, live shop): endpoint shape against the same
  payload validator, CORS header, required `sprd-*` classes present in the rendered client.
- Packaging: leak guard extended (`package*.json`, `tsconfig.json`, `biome.json`, `*.d.ts`,
  `*.test.js`, `types/`); required-file list gains the new JS/CSS/schema.
- Fixtures: trimmed list responses (product list, design list, empty shop, malformed) — public
  catalogue data only.

## Phases

1. Tooling: npm root, Biome, tsconfig, node:test, schema + drift, grep gate, CI job, packaging
   guards. No behaviour change.
2. Settings: schema, options, accessors, sanitisers, detector extraction, Renderer classes,
   start list. Unit tests.
3. Admin: Layout section, colour pickers, card-field combobox, i18n.
4. CSS: tokens, sidebar, compact footer, cards.
5. JS: nav toggles, data model, enrichment.
6. Evidence (e2e + screenshots), security review, mutation check, docs (README settings table
   and "appearance" sentence, FAQ privacy: extra list request and hover images, changelog,
   modernization.md note). Release as 1.9.0.
