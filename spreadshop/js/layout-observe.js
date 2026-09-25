/**
 * The one place the layout scripts learn that the shop has drawn something.
 *
 * The shop client is a single-page app: it re-renders parts of #myShop on every navigation and
 * fires no event for it. Each layout script registers an enhancer here; enhancers run once the
 * shop appears and again after every burst of changes inside it. Enhancers must be idempotent:
 * their own insertions also count as changes and bring them back once, to find nothing to do.
 */
(() => {
  'use strict';

  /** @type {Array<(shop: HTMLElement) => void>} */
  const enhancers = [];
  let isScheduled = false;
  /** @type {HTMLElement | null} */
  let shop = null;

  function runAll() {
    isScheduled = false;
    if (!shop) {
      return;
    }
    for (const enhance of enhancers) {
      enhance(shop);
    }
  }

  function schedule() {
    if (!isScheduled) {
      isScheduled = true;
      requestAnimationFrame(runAll);
    }
  }

  /**
   * @param {(shop: HTMLElement) => void} enhance
   */
  function onRender(enhance) {
    enhancers.push(enhance);
    schedule();
  }

  function start() {
    shop = document.getElementById('myShop');
    if (!shop) {
      return;
    }
    // href too: the client reuses tile nodes and only swaps their link when a list changes.
    new MutationObserver(schedule).observe(shop, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['href'],
    });
    schedule();
  }

  window.spreadshopLayout = { ...window.spreadshopLayout, onRender };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
