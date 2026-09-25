/**
 * Adds the product name and the chosen card fields to the shop's image-only product tiles.
 *
 * The client fetches each product list from its shopData/list endpoint. That request is seen
 * through the Resource Timing API and fetched once more (the endpoint allows any origin), so
 * routes, filters, search, page and locale are all covered without rebuilding a query. The
 * response goes through layout-data.js; tiles are matched to it by the link they carry.
 *
 * Everything added is marked data-spreadshop and built with createElement/textContent only.
 * A tile without a matching card is left exactly as the shop drew it.
 */
(() => {
  'use strict';

  const { __, _n, sprintf } = wp.i18n;

  const LIST_PATH = /\/shopData\/list$/;
  const SHOP_HOST = /(^|\.)myspreadshop\.[a-z.]+$/;
  const FETCH_TIMEOUT_MS = 8000;
  const VISIBLE_SWATCHES = 5;

  const data = window.spreadshopLayout?.data;
  const fields = window.spreadshopLayoutConfig?.cardFields ?? [];

  /** @type {Map<string, SpreadshopCardModel>} */
  const cards = new Map();
  /** @type {Set<string>} */
  const fetched = new Set();
  /** @type {AbortController | null} */
  let pending = null;
  let hasWarned = false;
  /** @type {HTMLElement | null} */
  let shopRoot = null;

  /**
   * @param {string} message
   */
  function warnOnce(message) {
    if (!hasWarned) {
      hasWarned = true;
      console.warn(`Spreadshop product cards: ${message}`);
    }
  }

  /**
   * @param {string} tag
   * @param {string} className
   * @param {string} [text]
   * @returns {HTMLElement}
   */
  function element(tag, className, text) {
    const node = document.createElement(tag);
    node.className = className;
    node.setAttribute('data-spreadshop', '');
    if (text) {
      node.textContent = text;
    }
    return node;
  }

  /**
   * @param {string} url
   */
  function load(url) {
    if (!data || fetched.has(url)) {
      return;
    }
    fetched.add(url);
    pending?.abort();
    const controller = new AbortController();
    pending = controller;
    const timer = setTimeout(() => controller.abort(), FETCH_TIMEOUT_MS);
    fetch(url, { signal: controller.signal, credentials: 'omit' })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`list request answered ${response.status}`);
        }
        return response.json();
      })
      .then((payload) => {
        const model = data.parseList(payload);
        if (!model) {
          warnOnce('the product list has an unexpected shape; tiles stay as the shop draws them.');
          return;
        }
        for (const [key, card] of model.cards) {
          cards.set(key, card);
        }
        if (shopRoot) {
          enhance(shopRoot);
        }
      })
      .catch((error) => {
        if (!controller.signal.aborted) {
          fetched.delete(url);
          warnOnce(`the product list could not be read (${error.message}).`);
        }
      })
      .finally(() => clearTimeout(timer));
  }

  /**
   * @param {PerformanceEntry} entry
   */
  function consider(entry) {
    try {
      const url = new URL(entry.name);
      if (
        url.protocol === 'https:' &&
        SHOP_HOST.test(url.hostname) &&
        LIST_PATH.test(url.pathname)
      ) {
        load(url.href);
      }
    } catch {
      // Not a URL this script cares about.
    }
  }

  /**
   * @param {SpreadshopCardModel} card
   * @returns {HTMLElement}
   */
  function swatches(card) {
    const list = element('div', 'spreadshop-card__swatches');
    const names = card.swatches.map((swatch) => swatch.name).filter(Boolean);
    list.setAttribute('role', 'img');
    list.setAttribute(
      'aria-label',
      `${sprintf(
        /* translators: %d: how many colours the product comes in. */
        _n('%d colour', '%d colours', card.swatches.length, 'spreadshop'),
        card.swatches.length,
      )}: ${names.join(', ')}`,
    );
    for (const swatch of card.swatches.slice(0, VISIBLE_SWATCHES)) {
      const dot = element('span', 'spreadshop-card__swatch');
      dot.style.setProperty('--spreadshop-swatch', swatch.hex);
      list.append(dot);
    }
    const more = card.swatches.length - VISIBLE_SWATCHES;
    if (more > 0) {
      list.append(element('span', 'spreadshop-card__swatch-more', `+${more}`));
    }
    return list;
  }

  /**
   * @param {SpreadshopCardModel} card
   * @returns {HTMLElement}
   */
  function details(card) {
    const box = element('div', 'spreadshop-card__details');
    box.setAttribute('data-spreadshop-key', card.key);
    const foot = element('div', 'spreadshop-card__foot');
    for (const field of fields) {
      if (field === 'productType' && card.productType) {
        box.append(element('p', 'spreadshop-card__type', card.productType));
      } else if (field === 'price') {
        foot.append(element('span', 'spreadshop-card__price', card.price));
      } else if (field === 'swatches' && card.swatches.length > 0) {
        foot.append(swatches(card));
      } else if (field === 'sizes' && card.sizes) {
        const sizes = element('p', 'spreadshop-card__sizes');
        sizes.append(element('span', 'spreadshop-sr-only', `${__('Sizes', 'spreadshop')}: `));
        sizes.append(
          document.createTextNode(
            card.sizes.count > 1 ? `${card.sizes.first}–${card.sizes.last}` : card.sizes.first,
          ),
        );
        box.append(sizes);
      }
    }
    if (foot.childElementCount > 0) {
      box.append(foot);
    }
    return box;
  }

  /**
   * @param {HTMLElement} tile
   */
  function restore(tile) {
    for (const node of tile.querySelectorAll('[data-spreadshop]')) {
      node.remove();
    }
    /** @type {HTMLImageElement | null} */
    const image = tile.querySelector('img[data-spreadshop-alt]');
    if (image) {
      image.alt = image.getAttribute('data-spreadshop-alt') ?? '';
      image.removeAttribute('data-spreadshop-alt');
    }
    const link = tile.querySelector('a[data-spreadshop-role]');
    if (link) {
      link.setAttribute('role', link.getAttribute('data-spreadshop-role') ?? '');
      link.removeAttribute('data-spreadshop-role');
    }
    tile.removeAttribute('data-spreadshop-card');
  }

  /**
   * @param {HTMLElement} tile
   * @param {HTMLAnchorElement} link
   * @param {SpreadshopCardModel} card
   */
  function enrich(tile, link, card) {
    restore(tile);
    tile.setAttribute('data-spreadshop-card', card.key);

    /** @type {HTMLImageElement | null} */
    const image = link.querySelector('img:not([data-spreadshop])');
    if (image) {
      image.setAttribute('data-spreadshop-alt', image.alt);
      image.alt = '';
    }
    link.append(element('span', 'spreadshop-card__name', card.name));
    // The client marks the tile link role="button", but it navigates to the product: once it
    // carries the product name it is announced as the link it is.
    const role = link.getAttribute('role');
    if (role !== null) {
      link.setAttribute('data-spreadshop-role', role);
      link.removeAttribute('role');
    }

    const frame = link.querySelector('.sprd-product-list-item__image');
    if (frame && fields.includes('hoverImage') && card.hoverImage) {
      const hover = /** @type {HTMLImageElement} */ (element('img', 'spreadshop-card__hover'));
      hover.alt = '';
      hover.loading = 'lazy';
      hover.decoding = 'async';
      hover.src = card.hoverImage;
      frame.append(hover);
    }

    tile.append(details(card));
  }

  /**
   * @param {HTMLElement} shop
   */
  function enhance(shop) {
    shopRoot = shop;
    /** @type {NodeListOf<HTMLElement>} */
    const tiles = shop.querySelectorAll('.sprd-product-list > .sprd-product-list-item');
    for (const tile of tiles) {
      /** @type {HTMLAnchorElement | null} */
      const link = tile.querySelector('a.sprd-product-list-item__link');
      const key = link && data ? data.keyFromHref(link.getAttribute('href') ?? '') : null;
      const card = key ? cards.get(key) : undefined;
      if (!link || !card) {
        if (tile.hasAttribute('data-spreadshop-card')) {
          restore(tile);
        }
        continue;
      }
      const drawn = tile.querySelector(':scope > .spreadshop-card__details');
      if (
        tile.getAttribute('data-spreadshop-card') !== card.key ||
        drawn?.getAttribute('data-spreadshop-key') !== card.key ||
        !link.querySelector('.spreadshop-card__name')
      ) {
        enrich(tile, link, card);
      }
    }
  }

  if (!data || typeof PerformanceObserver !== 'function') {
    return;
  }
  new PerformanceObserver((list) => {
    for (const entry of list.getEntries()) {
      consider(entry);
    }
  }).observe({ type: 'resource', buffered: true });

  window.spreadshopLayout?.onRender?.(enhance);
})();
