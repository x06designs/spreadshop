/**
 * Turns the shop's product-list response into the card model the product cards draw.
 *
 * Pure: no DOM, no network, no translations. The response is Spreadshirt's and undocumented, so
 * every field is checked; an article that does not have the expected shape yields no card
 * rather than half a card, and a response without the expected shape yields null.
 */
(() => {
  'use strict';

  const IMAGE_ORIGIN = 'https://image.spreadshirtmedia.net/';
  const HEX = /^#[0-9a-f]{6}$/i;

  /**
   * @typedef {SpreadshopSwatch} Swatch
   * @typedef {SpreadshopCardModel} CardModel
   * @typedef {SpreadshopListModel} ListModel
   */

  /**
   * @param {unknown} value
   * @returns {value is Record<string, unknown>}
   */
  function isObject(value) {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
  }

  /**
   * @param {unknown} value
   * @returns {value is string}
   */
  function isText(value) {
    return typeof value === 'string' && value.trim() !== '';
  }

  /**
   * The key a tile and its article share: the article (design) id plus the product type, since
   * one design is sold on several product types under the same id.
   *
   * @param {string} articleId
   * @param {string} productTypeId
   * @returns {string}
   */
  function cardKey(articleId, productTypeId) {
    return `${articleId}:${productTypeId}`;
  }

  /**
   * The key of the tile a shop link points to, or null for a link that is not a product.
   *
   * @param {string} href Tile link, e.g. "#!/name-A69cbe…?productType=1265&…".
   * @returns {string | null}
   */
  function keyFromHref(href) {
    const article = /-A([0-9a-f]+)(?:[?#/]|$)/i.exec(href);
    const productType = /[?&]productType=(\d+)/.exec(href);
    return article && productType ? cardKey(article[1], productType[1]) : null;
  }

  /**
   * The product type as the shop names it: the article name after "<design name> - ".
   * Only when that prefix is there exactly; otherwise nothing, rather than a guess.
   *
   * @param {string} name
   * @param {string} pureName
   * @returns {string | null}
   */
  function productTypeOf(name, pureName) {
    const prefix = `${pureName} - `;
    if (!name.startsWith(prefix)) {
      return null;
    }
    const rest = name.slice(prefix.length).trim();
    return rest === '' ? null : rest;
  }

  /**
   * @param {unknown} appearance
   * @returns {{ id: string, name: string, hex: string | null, sizes: { name: string, inStock: boolean }[], inStock: boolean } | null}
   */
  function readAppearance(appearance) {
    if (!isObject(appearance) || !isText(appearance.id) || !Array.isArray(appearance.sizes)) {
      return null;
    }
    const colors = Array.isArray(appearance.colors) ? appearance.colors : [];
    const hex = typeof colors[0] === 'string' && HEX.test(colors[0]) ? colors[0] : null;
    const sizes = appearance.sizes
      .filter((size) => isObject(size) && isText(size.name))
      .map((size) => ({ name: String(size.name), inStock: size.inStock === true }));
    return {
      id: appearance.id,
      name: isText(appearance.name) ? appearance.name.trim() : '',
      hex,
      sizes,
      inStock: appearance.inStock === true,
    };
  }

  /**
   * @param {Record<string, unknown>} article
   * @param {Record<string, unknown>} productTypes
   * @returns {CardModel | null}
   */
  function readArticle(article, productTypes) {
    const { id, name, pureArticleName, priceFormatted, productTypeId } = article;
    if (!isText(id) || !isText(name) || !isText(pureArticleName) || !isText(priceFormatted)) {
      return null;
    }
    if (!isText(productTypeId)) {
      return null;
    }

    const excluded = new Set(
      Array.isArray(article.excludedAppearanceIds)
        ? article.excludedAppearanceIds.filter((value) => typeof value === 'string')
        : [],
    );
    const productType = productTypes[productTypeId];
    const appearances = (
      isObject(productType) && Array.isArray(productType.appearances) ? productType.appearances : []
    )
      .map(readAppearance)
      .filter(
        /** @returns {a is NonNullable<ReturnType<typeof readAppearance>>} */
        (a) => a?.inStock === true && !excluded.has(a.id),
      );

    /** @type {Swatch[]} */
    const swatches = [];
    for (const appearance of appearances) {
      if (appearance.hex) {
        swatches.push({ hex: appearance.hex, name: appearance.name });
      }
    }

    /** @type {string[]} */
    const sizeOrder = [];
    const inStockSizes = new Set();
    for (const appearance of appearances) {
      for (const size of appearance.sizes) {
        if (!sizeOrder.includes(size.name)) {
          sizeOrder.push(size.name);
        }
        if (size.inStock) {
          inStockSizes.add(size.name);
        }
      }
    }
    const sizes = sizeOrder.filter((size) => inStockSizes.has(size));

    const hoverImage =
      typeof article.hoverImageUrl === 'string' && article.hoverImageUrl.startsWith(IMAGE_ORIGIN)
        ? article.hoverImageUrl
        : null;

    return {
      key: cardKey(id, productTypeId),
      name: pureArticleName.trim(),
      productType: productTypeOf(name, pureArticleName),
      price: priceFormatted.trim(),
      swatches,
      sizes:
        sizes.length > 0
          ? { first: sizes[0], last: sizes[sizes.length - 1], count: sizes.length }
          : null,
      hoverImage,
    };
  }

  /**
   * @param {unknown} payload The parsed JSON of a shopData/list response.
   * @returns {ListModel | null} Null when the response does not look like a product list.
   */
  function parseList(payload) {
    if (!isObject(payload) || !Array.isArray(payload.articles)) {
      return null;
    }
    const designBased = payload.designBased === true;
    /** @type {Map<string, CardModel>} */
    const cards = new Map();
    if (designBased) {
      return { designBased, cards };
    }
    const productTypes = isObject(payload.productTypes) ? payload.productTypes : null;
    if (!productTypes) {
      return null;
    }
    for (const article of payload.articles) {
      if (!isObject(article)) {
        continue;
      }
      const card = readArticle(article, productTypes);
      if (card) {
        cards.set(card.key, card);
      }
    }
    return { designBased, cards };
  }

  const api = { parseList, keyFromHref, productTypeOf };

  window.spreadshopLayout = { ...window.spreadshopLayout, data: api };
})();
