/**
 * Product page: shows the product details (description, size guide) as tabs, and marks sizes
 * that are sold out as disabled.
 *
 * The client draws the details as columns with a heading button each, which it uses as an
 * accordion on small screens. The tabs reuse those columns as their panels and take their
 * names from the heading buttons; the tab list is appended to the columns' container, so the
 * client's own nodes keep their order. The selected tab survives the client's redraws.
 */
(() => {
  'use strict';

  let selected = 0;
  /** @type {HTMLElement | null} */
  let shopRoot = null;

  /**
   * @param {HTMLElement} container
   * @returns {HTMLElement[]}
   */
  function columnsOf(container) {
    return [...container.querySelectorAll(':scope > .sprd-detail-product-type__column')].filter(
      /** @returns {column is HTMLElement} */
      (column) => column instanceof HTMLElement,
    );
  }

  /**
   * @param {HTMLElement} column
   * @returns {string}
   */
  function labelOf(column) {
    return (
      column.querySelector('.sprd-detail-product-type__column__toggle')?.textContent ?? ''
    ).trim();
  }

  /**
   * @param {number} index
   * @param {boolean} moveFocus
   */
  function select(index, moveFocus) {
    selected = index;
    if (!shopRoot) {
      return;
    }
    enhance(shopRoot);
    if (moveFocus) {
      /** @type {HTMLElement | null} */
      const tab = shopRoot.querySelector(`.spreadshop-tabs [data-spreadshop-tab="${index}"]`);
      tab?.focus();
    }
  }

  /**
   * @param {HTMLElement} container
   * @param {HTMLElement[]} columns
   */
  function enhanceTabs(container, columns) {
    if (selected >= columns.length) {
      selected = 0;
    }

    /** @type {HTMLElement | null} */
    let list = container.querySelector(':scope > .spreadshop-tabs');
    if (!list || list.childElementCount !== columns.length) {
      list?.remove();
      list = document.createElement('div');
      list.className = 'spreadshop-tabs';
      list.setAttribute('role', 'tablist');
      list.setAttribute('data-spreadshop', '');
      list.addEventListener('keydown', (event) => {
        const count = columns.length;
        const moves = {
          ArrowRight: (selected + 1) % count,
          ArrowLeft: (selected - 1 + count) % count,
          Home: 0,
          End: count - 1,
        };
        if (event.key in moves) {
          event.preventDefault();
          select(moves[/** @type {keyof typeof moves} */ (event.key)], true);
        }
      });
      columns.forEach((_, index) => {
        const tab = document.createElement('button');
        tab.type = 'button';
        tab.className = 'spreadshop-tabs__tab';
        tab.setAttribute('role', 'tab');
        tab.setAttribute('data-spreadshop-tab', String(index));
        tab.addEventListener('click', () => select(index, false));
        list?.append(tab);
      });
      container.append(list);
    }

    const heading = container.parentElement?.querySelector('.sprd-detail-product-type__heading');
    if (heading?.textContent) {
      list.setAttribute('aria-label', heading.textContent.trim());
    }

    columns.forEach((column, index) => {
      const tab = /** @type {HTMLButtonElement} */ (list?.children[index]);
      const isSelected = index === selected;
      column.id ||= `spreadshop-tab-panel-${index}`;
      tab.id ||= `spreadshop-tab-${index}`;
      if (tab.textContent !== labelOf(column)) {
        tab.textContent = labelOf(column);
      }
      tab.setAttribute('aria-selected', String(isSelected));
      tab.setAttribute('aria-controls', column.id);
      tab.tabIndex = isSelected ? 0 : -1;
      column.setAttribute('role', 'tabpanel');
      column.setAttribute('aria-labelledby', tab.id);
      column.tabIndex = 0;
      column.toggleAttribute('data-spreadshop-tab-hidden', !isSelected);
    });
    container.setAttribute('data-spreadshop-tabs', '');
  }

  /**
   * @param {HTMLElement} shop
   */
  function markSoldOutSizes(shop) {
    for (const size of shop.querySelectorAll('.sprd-detail-sizes__size')) {
      const isSoldOut = size.classList.contains('sprd-detail-sizes__size--stockout');
      if (isSoldOut && size.getAttribute('aria-disabled') !== 'true') {
        size.setAttribute('aria-disabled', 'true');
      } else if (!isSoldOut && size.hasAttribute('aria-disabled')) {
        size.removeAttribute('aria-disabled');
      }
    }
  }

  /**
   * @param {HTMLElement} shop
   */
  function enhance(shop) {
    shopRoot = shop;
    /** @type {HTMLElement | null} */
    const container = shop.querySelector('.sprd-detail-product-type__container');
    if (container) {
      const columns = columnsOf(container);
      if (columns.length > 1) {
        enhanceTabs(container, columns);
      }
    }
    markSoldOutSizes(shop);
  }

  window.spreadshopLayout?.onRender?.(enhance);
})();
