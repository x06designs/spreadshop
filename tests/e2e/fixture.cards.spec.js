import { expect, test } from '@playwright/test';
import { fixture } from '../support/layout-data.js';
import { openShop, serveLists, settle } from './support.js';

const ROUTE = '#!/frauen?q=D3';
const tiles = '#myShop .sprd-product-list > .sprd-product-list-item';

test('draws one card per tile from the list data', async ({ page }) => {
  await serveLists(page, { list: 'list-products.json' });
  await openShop(page, ROUTE);

  await expect(page.locator(`${tiles}[data-spreadshop-card]`)).toHaveCount(3);
  const keys = await page
    .locator(tiles)
    .evaluateAll((nodes) => nodes.map((node) => node.getAttribute('data-spreadshop-card')));
  expect(keys).toEqual([
    '69cbe1683105e74fbea53fcb:1265',
    '68f738c79dc7e318812830d4:1265',
    '69cbe1683105e74fbea53fcb:2091',
  ]);
  await expect(page.locator(`${tiles} .spreadshop-card__price`).first()).toHaveText('34,49 €');
});

test('names each product link after the product, not the image', async ({ page }) => {
  await serveLists(page, { list: 'list-products.json' });
  await openShop(page, ROUTE);

  await expect(page.getByRole('link', { name: 'Abgeknickt', exact: true })).toHaveCount(2);
  // The client's own badge sits inside the link, so it leads the name.
  await expect(page.getByRole('link', { name: 'Stick StMu STICK', exact: true })).toHaveCount(1);
  const alts = await page
    .locator(`${tiles} img:not([data-spreadshop])`)
    .evaluateAll((images) => images.map((image) => image.getAttribute('alt')));
  expect(alts.every((alt) => alt === '')).toBe(true);
});

test('stops changing the page once the cards are drawn', async ({ page }) => {
  await serveLists(page, { list: 'list-products.json' });
  await openShop(page, ROUTE);
  await expect(page.locator(`${tiles}[data-spreadshop-card]`)).toHaveCount(3);

  expect(await settle(page)).toBe(0);
  await expect(page.locator(`${tiles} .spreadshop-card__details`)).toHaveCount(3);
  await expect(page.locator(`${tiles} .spreadshop-card__name`)).toHaveCount(3);
});

test('leaves the tiles as the shop drew them when the data has an unexpected shape', async ({
  page,
}) => {
  /** @type {string[]} */
  const warnings = [];
  page.on('console', (message) => {
    if (message.type() === 'warning' && message.text().includes('Spreadshop')) {
      warnings.push(message.text());
    }
  });
  await serveLists(page, { list: 'list-products.json', refetch: 'list-malformed.json' });
  await openShop(page, ROUTE);
  await expect(page.locator(tiles)).toHaveCount(3);
  await settle(page);

  await expect(page.locator(`${tiles} [data-spreadshop]`)).toHaveCount(0);
  await expect(page.locator(`${tiles}[data-spreadshop-card]`)).toHaveCount(0);
  expect(warnings).toHaveLength(1);
});

test('draws no cards on the design list', async ({ page }) => {
  await serveLists(page, { list: 'list-designs.json' });
  await openShop(page, '#!/');
  await settle(page);

  await expect(page.locator(`${tiles} [data-spreadshop]`)).toHaveCount(0);
});

test('redraws a tile whose link the shop swapped for another product', async ({ page }) => {
  await serveLists(page, { list: 'list-products.json' });
  await openShop(page, ROUTE);
  await expect(page.locator(`${tiles}[data-spreadshop-card]`)).toHaveCount(3);

  const hoodie = fixture('list-products.json').articles[2];
  await page
    .locator(`${tiles} a.sprd-product-list-item__link`)
    .first()
    .evaluate((link, href) => {
      link.setAttribute('href', href);
    }, `#!${hoodie.linkToken}`);

  const first = page.locator(tiles).first();
  await expect(first).toHaveAttribute('data-spreadshop-card', '69cbe1683105e74fbea53fcb:2091');
  await expect(first.locator('.spreadshop-card__price')).toHaveText(hoodie.priceFormatted.trim());
  await expect(first.locator('.spreadshop-card__details')).toHaveCount(1);
});

test('removes its additions and restores the image text when a tile stops being a product', async ({
  page,
}) => {
  await serveLists(page, { list: 'list-products.json' });
  await openShop(page, ROUTE);
  const first = page.locator(tiles).first();
  await expect(first).toHaveAttribute('data-spreadshop-card', /.+/);

  await first.locator('a.sprd-product-list-item__link').evaluate((link) => {
    link.setAttribute('href', '#!/not-a-product');
  });

  await expect(first).not.toHaveAttribute('data-spreadshop-card');
  await expect(first.locator('[data-spreadshop]')).toHaveCount(0);
  await expect(first.locator('img').first()).not.toHaveAttribute('alt', '');
});
