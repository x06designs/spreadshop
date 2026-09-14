# Spreadshop fork — modernization plan

Status: **Phases 0 to 3 done.** Phase 4 (packaging, CI) remains.

Decisions taken at the goals gate:

- Autoloader, when Phase 2 lands: **hand-rolled `spl_autoload_register`, zero runtime
  dependencies.** Composer stays dev-only, so the shipped tree needs no `vendor/` and a
  plain "Download ZIP" from the repository still activates. Deviation from the house
  Composer-autoloader default, made deliberately.
- Inpsyde Modularity: **no.** A container and a runtime dependency to wire three hooks.
- Block-theme/FSE risk: **test during Phase 0**, in a throwaway WordPress rather than the
  borrowed x06 sandbox.

Baseline: 1.7.0, 835 lines of PHP across 7 files plus one stylesheet. No Composer, no
namespace, no tests, no i18n, no build tooling. 11 global functions, 4 static classes.

## 1. Review against the Alchemisten conventions

The house standard (`wp-foundation:conventions`) describes a **per-client Nx monorepo**:
Bedrock at `apps/wordpress`, the plugin IS the lib at `libs/<slug>`, Inpsyde Modularity,
an `@alchemisten/*` Vite web layer, a JSON-Schema PHP↔TS contract in `generated/`, nx-wp
executors.

This artifact is none of those things. It is a standalone vendored fork of a third-party
GPL-2.0 plugin, in its own repository, which has to stay installable the way its users
install it: a zip, dropped into wp-admin, activated. Judging it against the monorepo
anatomy wholesale would be cargo-culting. The split below is the substance of the review.

### The AVOID checklist

Sixteen guardrails. Fourteen are inapplicable here because they govern a Bedrock site repo
or a deploy pipeline this project does not have: committing `web/wp`, creds in a committed
`.env`, plain-FTP deploy, server-side `composer update`, ungated updates, fragile root
cron, weakening Bedrock hardening, custom code in `web/app/plugins/`, a `src/` wrapper in a
lib, stale `generated/`, JS tooling via Composer, unscoped wpackagist, `DB_HOST=localhost`,
hardcoded URLs in Nx install targets.

Three bite, or will:

| Guardrail | Status |
|---|---|
| Calling `lando start/stop` directly instead of the `wp-env-*` seam | **Already respected** — the repair run went through `scripts/wp-env-up.sh` / `wp-env-down.sh` |
| Never commit `vendor/` or build artifacts | **Becomes live in Phase 0.** Today's `.gitignore` is a *WordPress site* ignore file (it ignores `/wp-admin/`, `/wp-includes/`, `wp-config.php`) sitting in a *plugin* repo. It covers nothing this project produces and would not stop `vendor/` |
| Commit `composer.lock`; gate updates on `composer audit` | **Becomes live in Phase 0**, the moment a `composer.json` exists |

### What transfers

- **`wp-dev:php-tooling` in full** — WPCS 3.x, PHPStan (start level 5, ratchet to 6),
  PHPCompatibilityWP at `testVersion 8.1-`, PHPUnit + Brain Monkey, the `lint` / `fix` /
  `phpstan` / `test:unit` / `check` composer scripts. This is the single biggest win
  available and is entirely independent of the monorepo.
- **Bootstrap-only main file** — plugin header, autoload, boot. No logic at top level.
- **PSR-4 `includes/`, organised by feature** — the shape transfers even though the
  generator does not.
- **Text domain and prefixes declared once in `phpcs.xml.dist`**, so i18n and global-prefix
  rules are machine-checked rather than remembered.
- **`.gitignore` discipline** and a packaging step that builds the shippable tree rather
  than committing it. `wp-plugins/scripts/package-plugin.sh` is the proven in-house shape.
- **CI running the same gates as local.**

### What does not transfer

| Convention | Why not |
|---|---|
| Per-client Nx monorepo | This is a fork of someone else's plugin, not a client engagement. There is no client and no second site |
| Bedrock at `apps/wordpress` | Not a site repo. The sandbox for testing it is borrowed from `wp-plugins` |
| `libs/<slug>` path-symlink package | Requires a monorepo to be a lib *of* |
| `@alchemisten/*` Vite web layer | **The plugin ships zero JavaScript.** The shop client is a remote script tag from Spreadshirt. A Vite pipeline here would compile nothing |
| JSON-Schema PHP↔TS contract, `generated/` | No TypeScript and no schema-driven surfaces to keep in sync |
| nx-wp executors, `project.json` | No Nx |
| Polyglot components | No block, REST or Elementor bindings exist, and none are wanted |
| Inpsyde Modularity | Judgment call, see the open decision below |

## 2. Phases

Each phase leaves the plugin installable and working. No phase depends on a later one.

### Phase 0 — Gates first, no behaviour change

Add `composer.json` with **dev dependencies only**, `phpcs.xml.dist`, `phpstan.neon.dist`,
a plugin-appropriate `.gitignore`. Run all gates against the code as it stands and record
the baseline violation count in this file.

Gates come before the refactor deliberately: the refactor is then checked by them rather
than trusted.

### Phase 1 — i18n

Roughly 45 user-facing string sites across the three admin files (`-connect` ~17,
`-advanced` ~12, `-frame` ~4, plus `submit_button()` labels). `languages/` with a generated
`.pot`. Loaded on `init`, not at file load, to avoid WordPress 6.7+ just-in-time textdomain
notices.

**The text domain collides with the upstream slug, deliberately.** WordPress requires the
text domain to match the plugin folder, and the folder must stay `spreadshop` because
`spreadshop/spreadshop.php` is the activation key WordPress stores — renaming it silently
disconnects every install upgrading from 1.6.6. So our domain is the same string the
discontinued official plugin used. Consequence: a site that once ran the official plugin may
still hold language packs at `wp-content/languages/plugins/spreadshop-*.mo`, and WordPress
will apply them to our strings. Where a string is unchanged from upstream this produces a
correct translation; where we changed it the msgid no longer matches and the string falls
through to English. Neither outcome is harmful, and the alternative costs every existing
install its connection.

A fork receives no translate.wordpress.org translations of its own — those are keyed to the
upstream slug. The `.pot` and any shipped `.mo` are ours to maintain.

### Phase 2 — Structure

Bootstrap-only `spreadshop.php`; namespace `Spreadshop\`; PHP moved under `includes/`
grouped by responsibility (`Core/`, `Admin/`, `Embed/`).

Two invariants this phase must not break, both back-compat with installs upgrading from
1.6.6 in place:

- **Every option key stays byte-identical** (`spreadshopID`, `spreadshopPlatform`, …). An
  existing connected site must not notice the upgrade.
- **The folder slug stays `spreadshop`** and the shortcode tag stays `[spreadshop]`.

### Phase 3 — Tests

The honest scope. Most of this plugin is glue to a third-party service, and testing glue
is theatre. What is genuinely worth testing:

- `acceptLanguage()` locale derivation — pure, four cases already exercised by a scratch probe
- `describeFailure()` — five branches, already exercised by a scratch probe
- `fetchCoreData()` payload parsing against mocked HTTP, including the malformed-payload guard
- The `template_include` path match — this is where a real bug lived (`/meinshopXYZ`
  hijacking), so it earns regression tests
- The shortcode single-run guard — also a real bug, also earns one
- Embed config assembly from options

Two scratch probes written during the repair (`connect-probe.php`, `failure-msg-test.php`)
covered the first three and were folded in rather than rewritten.

What would be theatre: asserting `wp_remote_get()` was called, testing WordPress core
behaviour, or mocking Spreadshirt so thoroughly the test only proves the mock.

**Delivered:** 73 tests, 115 assertions, PHPUnit 9.6 + Brain Monkey, no WordPress required.
Green on PHP 8.1 and 8.4. The suite was mutation-checked by reintroducing eight of the
original defects one at a time; all eight were caught.

The unit suite deliberately stops at the seam where WordPress begins. Anything past it --
does the admin screen render, does an upgrade preserve a connection, does a block theme get
its header -- is verified by running the plugin in a real WordPress, not by mocking one.

### Phase 4 — Packaging and CI

`scripts/package-plugin.sh` producing `dist/spreadshop/` — the runtime tree, no tests, no
tooling configs, no lockfiles. CI running lint, phpstan, unit tests on an 8.1/8.4 matrix,
and `composer audit`.

`wp-dev:php-tooling` specifies GitLab CI; this repository is on GitHub. Port the stages
(lint → static analysis → test), not the platform.

## 3. Open decisions

These change the work materially and are not mine to settle.

1. **Autoloader: hand-rolled or Composer?** A ~10-line `spl_autoload_register` keeps the
   plugin's runtime dependencies at **zero**, so the shipped zip needs no `vendor/` at all
   and Composer stays a dev-only tool. Composer's autoloader is the house default but drags
   `vendor/` into the shipped artifact. For a drop-in fork, the hand-rolled one is the
   better trade.
2. **Inpsyde Modularity: yes or no?** The house pattern, but this plugin registers three
   hooks and a shortcode. Adding a runtime dependency and a container to wire three hooks is
   heavy. Recommendation: no, and document the deviation here.
3. **PHPStan level.** Start at 5 and ratchet, or go straight to 6 and absorb the noise.

## 4. Risks and unknowns

- **Block themes / FSE — unverified.** `spreadshop-embed-as-template.php` calls
  `get_header()` / `get_footer()`. Block themes have no `header.php`; the slug-based
  integration may render the shop without site chrome. Needs testing against a block theme
  before any release. The shortcode path is unaffected.
- **Multisite — untested.** Options are per-site; `register_uninstall_hook` behaviour across
  a network delete is unverified.
- **The EU=`.net` / NA=`.com` mapping is hardcoded** in two places
  (`spreadshop-admin-connect.php`, `spreadshop-embed.php`). Correct today — verified against
  the live API — but a genuine domain rule copy-pasted, not a style nit: a third platform
  means finding both call sites.
- **`isSpreadshopConnected()` hardcodes its option name** rather than using
  `SPREADSHOP_OPTIONS`, and carries an `'undefined'` string sentinel — evidence that
  settings back-compat has already bitten once.
- **The locale catalogue is reference data owned by a rendering class** (the private static
  `$locales` on `SpreadshopAdminConnect`). It belongs to the platform domain.
- **A second `[spreadshop]` on one page renders empty and says nothing.** The single-run
  guard is correct but silent; a reused block pattern could trip it with no explanation.
- **The shortcode does not preview in the block editor** — classic shortcodes are not
  executed in the editor iframe. A documentation gap, not a defect.
- **No automated detection of a future Spreadshirt edge change.** It will surface as an
  opaque 403 when someone next connects a shop. Worth an admin-visible "Test connection"
  action, or a documented pre-release smoke check.
- **Spreadshirt's edge rules can change again.** The current fix depends on a
  `Mozilla/5.0 (compatible; …)` User-Agent plus `Accept-Language`, and on an OpenSSL 3 TLS
  stack. A doc comment in `fetchCoreData()` says so; a test cannot pin a third party's
  bot rules.
- **Consent gating is out of scope here.** The embed contacts Spreadshirt on page render
  with no consent step. Documented in README; building a gate is its own piece of work with
  its own design decisions.
