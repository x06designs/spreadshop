/**
 * Helpers for the browser specs: serving fixture product lists and waiting for the shop.
 */
import { expect } from '@playwright/test';
import { fixture } from '../support/layout-data.js';

export const LIST_ROUTE = /\/shopData\/list(\?|$)/;

/**
 * Answers the shop's product-list requests from fixtures. The first answer per URL goes to
 * the shop client; `refetch` is what the plugin's own second request for the same URL gets,
 * so the client can draw tiles while the plugin reads something else.
 *
 * @param {import('@playwright/test').Page} page
 * @param {{ list: string, refetch?: string }} files Fixture file names.
 */
export async function serveLists(page, files) {
  /** @type {Map<string, number>} */
  const seen = new Map();
  await page.route(LIST_ROUTE, async (route) => {
    const url = route.request().url();
    const count = seen.get(url) ?? 0;
    seen.set(url, count + 1);
    const name = count === 0 ? files.list : (files.refetch ?? files.list);
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      headers: { 'access-control-allow-origin': '*' },
      body: JSON.stringify(fixture(name)),
    });
  });
}

/**
 * @param {import('@playwright/test').Page} page
 * @param {string} hash Shop route, e.g. "#!/frauen?q=D3".
 */
export async function openShop(page, hash) {
  await page.goto(hash);
  await expect(page.locator('#myShop .sprd-header')).toBeVisible();
}

/**
 * Resolves once no mutation has happened inside #myShop for `quietMs`.
 *
 * @param {import('@playwright/test').Page} page
 * @param {number} [quietMs]
 * @returns {Promise<number>} Mutations recorded during a final quiet window of the same length.
 */
export async function settle(page, quietMs = 1500) {
  return page.evaluate(
    (quiet) =>
      new Promise((resolve) => {
        const shop = document.getElementById('myShop');
        if (!shop) {
          resolve(0);
          return;
        }
        let timer = 0;
        let count = 0;
        let isFinal = false;
        const observer = new MutationObserver((records) => {
          count += records.length;
          if (!isFinal) {
            clearTimeout(timer);
            timer = window.setTimeout(finalWindow, quiet);
          }
        });
        function finalWindow() {
          isFinal = true;
          count = 0;
          window.setTimeout(() => {
            observer.disconnect();
            resolve(count);
          }, quiet);
        }
        observer.observe(shop, { childList: true, subtree: true, attributes: true });
        timer = window.setTimeout(finalWindow, quiet);
      }),
    quietMs,
  );
}
