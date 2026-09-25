/**
 * Pre-release check of the shop client surface the layout options rely on. None of it is a
 * published API, so a Spreadshirt release can move it; this fails before a plugin release does.
 */
import { expect, test } from '@playwright/test';
import { LIST_ROUTE, openShop } from '../e2e/support.js';
import { layoutData } from '../support/layout-data.js';

const REQUIRED_CLASSES = [
  '.sprd-header',
  '.sprd-header__image',
  '.sprd-search__button',
  '.sprd-basket-indicator__button',
  '.sprd-navigation',
  '.sprd-department-filter',
  '.sprd-department-filter__openmenu',
  '.sprd-department-filter__menu',
  '.sprd-breadcrumb-nav',
  '.sprd-listpage__title',
  '.sprd-mobilefilter__open-btn',
  '.sprd-product-list',
  '.sprd-product-list-item__link',
  '.sprd-product-list-item__image',
  '.sprd-pagination',
  '.sprd-footer',
  '.sprd-info-footer__col',
  '.sprd-service-footer',
];

test('the product-list endpoint still has the shape the cards read', async ({ page }) => {
  const response = page.waitForResponse((candidate) => LIST_ROUTE.test(candidate.url()));
  await openShop(page, '#!/frauen?q=D3');
  const list = await response;

  expect(list.headers()['access-control-allow-origin']).toBeTruthy();
  const model = layoutData.parseList(await list.json());
  expect(model).not.toBeNull();
  expect(model?.designBased).toBe(false);
  expect(model?.cards.size ?? 0).toBeGreaterThan(0);

  const keys = await page
    .locator('#myShop .sprd-product-list > .sprd-product-list-item a.sprd-product-list-item__link')
    .evaluateAll((links) => links.map((link) => link.getAttribute('href') ?? ''));
  const matched = keys.map((href) => layoutData.keyFromHref(href)).filter((key) => key !== null);
  expect(matched.length).toBe(keys.length);
  for (const key of matched) {
    expect(model?.cards.has(key)).toBe(true);
  }
});

test('the shop still renders the classes the layout options style', async ({ page }) => {
  await openShop(page, '#!/frauen?q=D3');
  await expect(page.locator('#myShop .sprd-product-list-item').first()).toBeVisible();
  for (const selector of REQUIRED_CLASSES) {
    await expect(page.locator(`#myShop ${selector}`).first(), selector).toBeAttached();
  }
});

test('the product page still has the detail columns the tabs are built from', async ({ page }) => {
  await openShop(page, '#!/frauen?q=D3');
  await page.locator('#myShop .sprd-product-list > .sprd-product-list-item a').first().click();
  const columns = page.locator(
    '#myShop .sprd-detail-product-type__container > .sprd-detail-product-type__column',
  );
  await expect(columns).toHaveCount(2);
  await expect(
    page.locator('#myShop .sprd-detail-product-type__column__toggle').first(),
  ).toBeAttached();
});
