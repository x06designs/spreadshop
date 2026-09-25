/**
 * Browser checks against a WordPress page that embeds the shop, given as BASE_URL (for example
 * https://dejok.lndo.site/shop/). The page must have every layout option switched on.
 *
 * fixture: the shop client loads live, its product lists come from tests/js/fixtures.
 * live: real shop data. smoke: the shop's undocumented surface the plugin relies on.
 *
 * live and smoke run in a visible browser: Spreadshirt's edge answers the CORS preflight of
 * the client's own list request without an Allow-Origin header when the browser identifies
 * as headless, and the shop then shows its error page. Fixture runs are unaffected (the list
 * never leaves the test).
 */
import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.BASE_URL;
if (!baseURL) {
  throw new Error(
    'Set BASE_URL to a page that embeds the shop, e.g. BASE_URL=https://dejok.lndo.site/shop/',
  );
}

const desktop = { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 900 } };
const mobile = { ...devices['Pixel 7'], viewport: { width: 390, height: 844 } };
const headed = { headless: false };

export default defineConfig({
  outputDir: '.playwright-mcp/e2e-results',
  reporter: [['list']],
  timeout: 60_000,
  expect: { timeout: 15_000 },
  use: { baseURL, ignoreHTTPSErrors: true, screenshot: 'only-on-failure' },
  projects: [
    { name: 'fixture-desktop', testDir: 'tests/e2e', testMatch: 'fixture.*.spec.js', use: desktop },
    { name: 'fixture-mobile', testDir: 'tests/e2e', testMatch: 'fixture.*.spec.js', use: mobile },
    {
      name: 'live-desktop',
      testDir: 'tests/e2e',
      testMatch: 'live.*.spec.js',
      use: { ...desktop, ...headed },
    },
    {
      name: 'live-mobile',
      testDir: 'tests/e2e',
      testMatch: 'live.*.spec.js',
      use: { ...mobile, ...headed },
    },
    { name: 'smoke', testDir: 'tests/smoke', use: { ...desktop, ...headed } },
  ],
});
