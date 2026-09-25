import assert from 'node:assert/strict';
import { test } from 'node:test';
import { layoutData as data, fixture } from '../support/layout-data.js';

test('builds one card per article and product type', () => {
  const model = data.parseList(fixture('list-products.json'));
  assert.ok(model);
  assert.equal(model.designBased, false);
  assert.deepEqual(
    [...model.cards.keys()],
    [
      '69cbe1683105e74fbea53fcb:1265',
      '68f738c79dc7e318812830d4:1265',
      '69cbe1683105e74fbea53fcb:2091',
    ],
  );
});

test('reads name, product type and price as delivered', () => {
  const card = data
    .parseList(fixture('list-products.json'))
    ?.cards.get('69cbe1683105e74fbea53fcb:1265');
  assert.equal(card?.name, 'Abgeknickt');
  assert.equal(card?.productType, 'Stanley/Stella Unisex Bio-T-Shirt CREATOR');
  assert.equal(card?.price, '34,49 €');
});

test('leaves out excluded and sold-out appearances from the swatches', () => {
  const payload = fixture('list-products.json');
  const card = data.parseList(payload)?.cards.get('69cbe1683105e74fbea53fcb:1265');
  const article = payload.articles[0];
  const offered = payload.productTypes['1265'].appearances.filter(
    (/** @type {{ id: string, inStock: boolean }} */ a) =>
      a.inStock && !article.excludedAppearanceIds.includes(a.id),
  );
  assert.equal(card?.swatches.length, offered.length);
  for (const swatch of card?.swatches ?? []) {
    assert.match(swatch.hex, /^#[0-9a-f]{6}$/i);
  }
});

test('reports the in-stock size range in catalogue order', () => {
  const card = data
    .parseList(fixture('list-products.json'))
    ?.cards.get('69cbe1683105e74fbea53fcb:1265');
  assert.deepEqual({ ...card?.sizes }, { first: 'XS', last: '3XL', count: 7 });
});

test('omits the product type when the name does not start with the design name', () => {
  assert.equal(data.productTypeOf('Other - T-Shirt', 'Abgeknickt'), null);
  assert.equal(data.productTypeOf('Abgeknickt - ', 'Abgeknickt'), null);
  assert.equal(data.productTypeOf('Abgeknickt - Tasse ', 'Abgeknickt'), 'Tasse');
});

test('accepts hover images only from the image server', () => {
  const payload = fixture('list-products.json');
  payload.articles[0].hoverImageUrl = 'https://evil.example/x.jpg';
  const cards = data.parseList(payload)?.cards;
  assert.equal(cards?.get('69cbe1683105e74fbea53fcb:1265')?.hoverImage, null);
  assert.match(
    cards?.get('69cbe1683105e74fbea53fcb:2091')?.hoverImage ?? '',
    /^https:\/\/image\.spreadshirtmedia\.net\//,
  );
});

test('drops swatches whose colour is not a plain hex value', () => {
  const payload = fixture('list-products.json');
  for (const appearance of payload.productTypes['1265'].appearances) {
    appearance.colors = ['red;background:url(x)'];
  }
  const card = data.parseList(payload)?.cards.get('69cbe1683105e74fbea53fcb:1265');
  assert.equal(card?.swatches.length, 0);
});

test('skips an article that lacks a required field instead of drawing half a card', () => {
  const payload = fixture('list-products.json');
  delete payload.articles[0].priceFormatted;
  const cards = data.parseList(payload)?.cards;
  assert.equal(cards?.has('69cbe1683105e74fbea53fcb:1265'), false);
  assert.equal(cards?.size, 2);
});

test('returns no cards for the design list', () => {
  const model = data.parseList(fixture('list-designs.json'));
  assert.equal(model?.designBased, true);
  assert.equal(model?.cards.size, 0);
});

test('returns null for a response of unknown shape', () => {
  assert.equal(data.parseList(null), null);
  assert.equal(data.parseList({ articles: 'x' }), null);
  assert.equal(data.parseList({ articles: [], designBased: false }), null);
});

test('reads the tile key from the link the client renders', () => {
  assert.equal(
    data.keyFromHref(
      '#!/abgeknickt-A69cbe1683105e74fbea53fcb?productType=1265&sellable=x&appearance=1',
    ),
    '69cbe1683105e74fbea53fcb:1265',
  );
  assert.equal(data.keyFromHref('#!/abgeknickt?idea=69cbe1683105e74fbea53fcb'), null);
});

const FIRST = '69cbe1683105e74fbea53fcb:1265';

/**
 * The appearances of the first article that are offered: in stock and not excluded.
 *
 * @param {Record<string, any>} payload
 * @returns {Record<string, any>[]}
 */
function offered(payload) {
  const excluded = payload.articles[0].excludedAppearanceIds;
  return payload.productTypes['1265'].appearances.filter(
    (/** @type {{ id: string, inStock: boolean }} */ a) => a.inStock && !excluded.includes(a.id),
  );
}

test('keeps only whole article ids in a tile link', () => {
  assert.equal(data.keyFromHref('#!/name-A69cbeXYZ?productType=1265'), null);
  assert.equal(data.keyFromHref('#!/name-A69cbe/?productType=1265'), '69cbe:1265');
});

test('finds the product type only after the design name at the start', () => {
  assert.equal(data.productTypeOf('Neu: Abgeknickt - Tasse', 'Abgeknickt'), null);
});

test('drops short, long and unanchored colour values', () => {
  for (const colour of ['#abc', '#abcd', 'x#aabbccZ', '#aabbccdd']) {
    const payload = fixture('list-products.json');
    for (const appearance of payload.productTypes['1265'].appearances) {
      appearance.colors = [colour];
    }
    assert.equal(data.parseList(payload)?.cards.get(FIRST)?.swatches.length, 0, colour);
  }
});

test('leaves out sold-out appearances from swatches and sizes', () => {
  const payload = fixture('list-products.json');
  const [kept, ...rest] = offered(payload);
  for (const appearance of rest) {
    appearance.inStock = false;
  }
  for (const size of kept.sizes) {
    size.inStock = size.name === 'M';
  }
  const card = data.parseList(payload)?.cards.get(FIRST);
  assert.equal(card?.swatches.length, 1);
  assert.deepEqual({ ...card?.sizes }, { first: 'M', last: 'M', count: 1 });
});

test('counts a size only when some offered appearance has it in stock', () => {
  const payload = fixture('list-products.json');
  for (const appearance of payload.productTypes['1265'].appearances) {
    for (const size of appearance.sizes) {
      if (size.name === '3XL') {
        size.inStock = false;
      }
      if (size.name === 'XS') {
        delete size.inStock;
      }
    }
  }
  const card = data.parseList(payload)?.cards.get(FIRST);
  assert.deepEqual({ ...card?.sizes }, { first: 'S', last: 'XXL', count: 5 });
});

test('skips articles whose required text is only whitespace', () => {
  for (const field of ['id', 'name', 'pureArticleName', 'priceFormatted']) {
    const payload = fixture('list-products.json');
    payload.articles[0][field] = '   ';
    const cards = data.parseList(payload)?.cards;
    assert.equal(cards?.size, 2, field);
  }
});

test('trims the name, the price and the colour names', () => {
  const payload = fixture('list-products.json');
  payload.articles[0].pureArticleName = 'Abgeknickt';
  payload.articles[0].name = 'Abgeknickt - Stanley/Stella Unisex Bio-T-Shirt CREATOR ';
  payload.articles[0].priceFormatted = ' 34,49 € ';
  const [first] = offered(payload);
  first.name = '  Navy  ';
  const card = data.parseList(payload)?.cards.get(FIRST);
  assert.equal(card?.name, 'Abgeknickt');
  assert.equal(card?.price, '34,49 €');
  assert.equal(card?.swatches[0]?.name, 'Navy');
});

test('takes the name as given apart from surrounding space', () => {
  const payload = fixture('list-products.json');
  payload.articles[0].pureArticleName = ' Abgeknickt ';
  payload.articles[0].name = ' Abgeknickt  - Tasse';
  assert.equal(data.parseList(payload)?.cards.get(FIRST)?.name, 'Abgeknickt');
});

test('lets the later of two articles with the same key win', () => {
  const payload = fixture('list-products.json');
  payload.articles.push({ ...payload.articles[0], priceFormatted: '1,00 €' });
  assert.equal(data.parseList(payload)?.cards.get(FIRST)?.price, '1,00 €');
});

test('rejects hover images on a host that only starts like the image server', () => {
  const payload = fixture('list-products.json');
  payload.articles[0].hoverImageUrl = 'https://image.spreadshirtmedia.net.evil.example/x.jpg';
  assert.equal(data.parseList(payload)?.cards.get(FIRST)?.hoverImage, null);
});

test('draws no cards for a design list even when it carries product data', () => {
  const payload = fixture('list-products.json');
  payload.designBased = true;
  assert.equal(data.parseList(payload)?.cards.size, 0);
});
