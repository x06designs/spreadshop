import { expect, test } from '@playwright/test';
import { openShop, settle } from './support.js';

const LIST = '#!/frauen?q=D3';

/**
 * @param {import('@playwright/test').TestInfo} testInfo
 * @returns {boolean}
 */
function isMobile(testInfo) {
  return testInfo.project.name.endsWith('mobile');
}

test('lays the list out without horizontal scrolling and draws the cards', async ({
  page,
}, testInfo) => {
  await openShop(page, LIST);
  await expect(
    page
      .locator('#myShop .sprd-product-list > .sprd-product-list-item[data-spreadshop-card]')
      .first(),
  ).toBeVisible();
  await settle(page);

  const width = await page.evaluate(() => ({
    scroll: document.documentElement.scrollWidth,
    client: document.documentElement.clientWidth,
  }));
  expect(width.scroll).toBeLessThanOrEqual(width.client);
  await page.screenshot({
    path: `.playwright-mcp/e2e-live-list-${testInfo.project.name}.png`,
    fullPage: true,
  });
});

test('keeps the legal links visible and reachable with the compact footer', async ({ page }) => {
  await openShop(page, LIST);
  for (const name of ['Datenschutz', 'Impressum']) {
    const link = page.locator('#myShop .sprd-footer a:visible', { hasText: name }).first();
    await expect(link).toBeVisible();
    await link.focus();
    await expect(link).toBeFocused();
  }
});

test('folds a department of the category tree', async ({ page }, testInfo) => {
  await openShop(page, LIST);
  if (isMobile(testInfo)) {
    await page.locator('#myShop .spreadshop-nav-categories').click();
  }
  const toggle = page.getByRole('button', { name: 'Unterseiten von Männer' });
  await expect(toggle).toHaveAttribute('aria-expanded', 'false');
  const panel = page.locator(`#${await toggle.getAttribute('aria-controls')}`);
  await expect(panel).toBeHidden();

  await toggle.click();
  await expect(toggle).toHaveAttribute('aria-expanded', 'true');
  await expect(panel).toBeVisible();
});

test('opens and closes the mobile category tree', async ({ page }, testInfo) => {
  test.skip(!isMobile(testInfo), 'The toggle only exists below the desktop width.');
  await openShop(page, LIST);
  const categories = page.locator('#myShop .spreadshop-nav-categories');
  const tree = page.locator('#myShop .sprd-navigation');
  await expect(tree).toBeHidden();

  await categories.click();
  await expect(categories).toHaveAttribute('aria-expanded', 'true');
  await expect(tree).toBeVisible();

  await page.getByRole('button', { name: 'Unterseiten von Frauen' }).focus();
  await page.keyboard.press('Escape');
  await expect(tree).toBeHidden();
  await expect(categories).toBeFocused();
});

test('names the filter dialog button and closes the dialog on Escape', async ({ page }) => {
  await openShop(page, LIST);
  const opener = page.locator('#myShop .sprd-mobilefilter__open-btn');
  await opener.click();
  const close = page.getByRole('button', { name: 'Filter schließen' });
  await expect(close).toBeVisible();

  await page.keyboard.press('Escape');
  await expect(page.locator('#myShop .sprd-mobilefilter__modal')).toHaveCount(0);
  await expect(opener).toBeFocused();
});

test('shows the product details as tabs that follow the arrow keys', async ({ page }) => {
  await openShop(page, LIST);
  await page.locator('#myShop .sprd-product-list > .sprd-product-list-item a').first().click();
  const tabs = page.getByRole('tab');
  await expect(tabs).toHaveCount(2);
  await expect(tabs.first()).toHaveAttribute('aria-selected', 'true');
  await expect(page.getByRole('tabpanel')).toHaveCount(1);

  await tabs.first().focus();
  await page.keyboard.press('ArrowRight');
  await expect(tabs.nth(1)).toBeFocused();
  await expect(tabs.nth(1)).toHaveAttribute('aria-selected', 'true');
  await expect(page.getByRole('tabpanel')).toHaveAccessibleName(
    (await tabs.nth(1).textContent()) ?? '',
  );
});
