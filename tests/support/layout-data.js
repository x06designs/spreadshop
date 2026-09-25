/**
 * Loads the shipped card model (a classic browser script) into Node for tests, the same file
 * the plugin serves, through a sandbox that stands in for window.
 */
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

/** @type {{ window: Window }} */
const sandbox = { window: /** @type {Window} */ ({}) };
runInNewContext(
  readFileSync(new URL('../../spreadshop/js/layout-data.js', import.meta.url), 'utf8'),
  sandbox,
);

export const layoutData = /** @type {SpreadshopLayoutData} */ (
  sandbox.window.spreadshopLayout?.data
);

/**
 * @param {string} name File name under tests/js/fixtures/.
 * @returns {Record<string, any>}
 */
export function fixture(name) {
  return JSON.parse(readFileSync(new URL(`../js/fixtures/${name}`, import.meta.url), 'utf8'));
}
