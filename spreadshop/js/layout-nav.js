/**
 * Folds the sidebar's category tree and adds the mobile "Categories" toggle.
 *
 * Each department with sub-pages gets a button (aria-expanded, aria-controls) after its link;
 * the department that holds the current page starts open. Below the client's desktop width, a
 * "Categories" button in the header shows or hides the whole tree. When the shop uses its own
 * burger menu (Mobile Swipe Menu off), that menu stays in charge and no toggle is added.
 *
 * The client redraws the header on navigation, so the open state lives here, keyed by each
 * department's link, and is written back onto every redraw.
 */
(() => {
  'use strict';

  const { __, _n, sprintf } = wp.i18n;

  const PANEL =
    ':scope > .sprd-department-filter__menu, :scope > .sprd-department-filter__departments';

  /** @type {Set<string>} */
  const openDepartments = new Set();
  /** @type {Set<string>} */
  const activeDepartments = new Set();
  let isCategoriesOpen = false;
  let panelCount = 0;
  /** @type {HTMLElement | null} */
  let shopRoot = null;

  /**
   * @param {HTMLElement} menu
   * @returns {HTMLAnchorElement | null}
   */
  function linkOf(menu) {
    return menu.querySelector(':scope > .sprd-nav-link');
  }

  /**
   * @param {HTMLElement} menu
   * @returns {string}
   */
  function keyOf(menu) {
    return menu.id || linkOf(menu)?.getAttribute('href') || '';
  }

  /**
   * @param {HTMLElement} menu
   */
  function toggleDepartment(menu) {
    const key = keyOf(menu);
    if (openDepartments.has(key)) {
      openDepartments.delete(key);
    } else {
      openDepartments.add(key);
    }
    if (shopRoot) {
      enhance(shopRoot);
    }
  }

  /**
   * @param {HTMLElement} menu
   */
  function enhanceDepartment(menu) {
    const link = linkOf(menu);
    /** @type {HTMLElement | null} */
    const panel = menu.querySelector(PANEL);
    if (!link || !panel) {
      return;
    }

    const key = keyOf(menu);
    if (link.classList.contains('sprd-nav-link--active')) {
      if (!activeDepartments.has(key)) {
        activeDepartments.add(key);
        openDepartments.add(key);
      }
    } else {
      activeDepartments.delete(key);
    }

    /** @type {HTMLButtonElement | null} */
    let button = menu.querySelector(':scope > .spreadshop-nav-toggle');
    if (!button) {
      button = document.createElement('button');
      button.type = 'button';
      button.className = 'spreadshop-nav-toggle';
      button.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        toggleDepartment(menu);
      });
      menu.append(button);
    }
    if (!panel.id) {
      panelCount += 1;
      panel.id = `spreadshop-nav-panel-${panelCount}`;
    }

    const isOpen = openDepartments.has(key);
    /* translators: %s: a shop category, e.g. "Women". */
    const label = sprintf(__('Sub-pages of %s', 'spreadshop'), (link.textContent ?? '').trim());
    if (button.getAttribute('aria-label') !== label) {
      button.setAttribute('aria-label', label);
    }
    button.setAttribute('aria-controls', panel.id);
    button.setAttribute('aria-expanded', String(isOpen));
    menu.setAttribute('data-spreadshop-toggle', '');
    menu.toggleAttribute('data-spreadshop-open', isOpen);
  }

  /**
   * @param {HTMLElement} header
   * @param {HTMLElement} navigation
   */
  function enhanceCategories(header, navigation) {
    /** @type {HTMLButtonElement | null} */
    let button = header.querySelector(':scope > .spreadshop-nav-categories');
    if (!button) {
      button = document.createElement('button');
      button.type = 'button';
      button.className = 'spreadshop-nav-categories';
      button.textContent = __('Categories', 'spreadshop');
      button.addEventListener('click', (event) => {
        event.stopPropagation();
        isCategoriesOpen = !isCategoriesOpen;
        if (shopRoot) {
          enhance(shopRoot);
        }
      });
      header.append(button);
    }
    if (!navigation.id) {
      navigation.id = 'spreadshop-nav-tree';
    }
    button.setAttribute('aria-controls', navigation.id);
    button.setAttribute('aria-expanded', String(isCategoriesOpen));
    navigation.setAttribute('data-spreadshop-nav', isCategoriesOpen ? 'open' : 'closed');
  }

  /**
   * @param {Element | null} node
   * @param {string} label
   */
  function nameButton(node, label) {
    if (node && node.getAttribute('aria-label') !== label) {
      node.setAttribute('aria-label', label);
    }
  }

  /**
   * The header buttons only carry English screen-reader text and the filter panel's close
   * button none at all; they get translated names.
   *
   * @param {HTMLElement} shop
   */
  function nameHeaderButtons(shop) {
    nameButton(shop.querySelector('.sprd-search__button'), __('Search', 'spreadshop'));
    const count = Number.parseInt(
      shop.querySelector('.sprd-basket-indicator__count')?.textContent ?? '',
      10,
    );
    nameButton(
      shop.querySelector('.sprd-basket-indicator__button'),
      count > 0
        ? /* translators: %d: how many items are in the basket. */
          sprintf(_n('Basket, %d item', 'Basket, %d items', count, 'spreadshop'), count)
        : __('Basket', 'spreadshop'),
    );
    nameButton(shop.querySelector('.sprd-mobilefilter__close'), __('Close filters', 'spreadshop'));
  }

  /**
   * @param {HTMLElement} shop
   */
  function enhance(shop) {
    shopRoot = shop;
    nameHeaderButtons(shop);
    /** @type {NodeListOf<HTMLElement>} */
    const menus = shop.querySelectorAll('.sprd-navigation .sprd-department-filter__openmenu');
    for (const menu of menus) {
      if (menu.id !== 'sprd-department-filter-all-products') {
        enhanceDepartment(menu);
      }
    }

    /** @type {HTMLElement | null} */
    const header = shop.querySelector('.sprd-header');
    /** @type {HTMLElement | null} */
    const navigation = shop.querySelector('.sprd-header > .sprd-navigation');
    if (header && navigation && !header.querySelector('.sprd-header__burgerbutton')) {
      enhanceCategories(header, navigation);
    }
  }

  // Following a link inside the open tree navigates; the tree folds away so the new page is
  // what shows. Capture phase, because the client's router stops the click from bubbling.
  document.addEventListener(
    'click',
    (event) => {
      if (!isCategoriesOpen || !shopRoot || !(event.target instanceof Element)) {
        return;
      }
      if (event.target.closest('[data-spreadshop-nav] a[href]')) {
        isCategoriesOpen = false;
        enhance(shopRoot);
      }
    },
    true,
  );

  // The client's filter panel is a dialog that does not close on Escape.
  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape' || !shopRoot) {
      return;
    }
    /** @type {HTMLButtonElement | null} */
    const close = shopRoot.querySelector('.sprd-mobilefilter__modal .sprd-mobilefilter__close');
    if (close) {
      event.preventDefault();
      close.click();
      /** @type {HTMLButtonElement | null} */
      const opener = shopRoot.querySelector('.sprd-mobilefilter__open-btn');
      opener?.focus();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape' || !isCategoriesOpen || !shopRoot) {
      return;
    }
    const navigation = shopRoot.querySelector('[data-spreadshop-nav]');
    if (!navigation || !(event.target instanceof Node) || !navigation.contains(event.target)) {
      return;
    }
    isCategoriesOpen = false;
    enhance(shopRoot);
    /** @type {HTMLButtonElement | null} */
    const toggle = shopRoot.querySelector('.spreadshop-nav-categories');
    toggle?.focus();
  });

  window.spreadshopLayout?.onRender?.(enhance);
})();
