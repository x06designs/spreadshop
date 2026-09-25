/**
 * Upgrades the card-field checkbox group to a reorderable chip list with an add combobox.
 *
 * The checkbox group stays the whole control without JavaScript. Once upgraded, its checkboxes
 * are disabled so they no longer post, and each chip posts a hidden input instead, in chip
 * order. The group's empty sentinel input keeps posting, so an empty selection still arrives.
 *
 * The add field follows the ARIA 1.2 combobox pattern (list autocomplete, active option via
 * aria-activedescendant). Chips are reordered with visible buttons; Alt+Arrow Up/Down on a chip
 * button is an accelerator for the same thing.
 */
(() => {
  'use strict';

  const { __, sprintf } = wp.i18n;

  /**
   * @typedef {{ value: string, label: string }} FieldOption
   */

  /**
   * @param {string} tag
   * @param {Record<string, string>} [attributes]
   * @param {string} [text]
   * @returns {HTMLElement}
   */
  function element(tag, attributes = {}, text = '') {
    const node = document.createElement(tag);
    for (const [name, value] of Object.entries(attributes)) {
      node.setAttribute(name, value);
    }
    if (text) {
      node.textContent = text;
    }
    return node;
  }

  /**
   * @param {HTMLFieldSetElement} group
   * @param {number} index Distinguishes ids if a page ever carries two groups.
   */
  function upgrade(group, index) {
    /** @type {HTMLInputElement[]} */
    const checkboxes = Array.from(group.querySelectorAll('input[type="checkbox"]'));
    /** @type {FieldOption[]} */
    const options = checkboxes.map((box) => ({
      value: box.value,
      label: (box.parentElement?.textContent ?? box.value).trim(),
    }));
    /** @type {string[]} */
    let chosen = checkboxes.filter((box) => box.checked).map((box) => box.value);
    let activeIndex = -1;

    const idBase = `spreadshop-card-fields-${index}`;
    const root = element('div', { class: 'spreadshop-chips' });
    const list = element('ul', {
      class: 'spreadshop-chips__list',
      'aria-label': __('Chosen card fields', 'spreadshop'),
    });
    const inputLabel = element(
      'label',
      { for: `${idBase}-input`, class: 'spreadshop-chips__label' },
      __('Add a card field', 'spreadshop'),
    );
    const comboWrap = element('div', { class: 'spreadshop-chips__combobox' });
    const input = /** @type {HTMLInputElement} */ (
      element('input', {
        id: `${idBase}-input`,
        type: 'text',
        role: 'combobox',
        autocomplete: 'off',
        'aria-autocomplete': 'list',
        'aria-expanded': 'false',
        'aria-controls': `${idBase}-listbox`,
        'aria-describedby': `${idBase}-help`,
      })
    );
    const listbox = element('ul', {
      id: `${idBase}-listbox`,
      role: 'listbox',
      class: 'spreadshop-chips__listbox',
      'aria-label': __('Card fields you can add', 'spreadshop'),
    });
    listbox.hidden = true;
    const help = element(
      'p',
      { id: `${idBase}-help`, class: 'description' },
      __(
        'Type to filter, pick a field with the arrow keys and add it with Enter. Backspace in the empty field removes the last field. On a field, Alt+Arrow Up and Alt+Arrow Down move it.',
        'spreadshop',
      ),
    );
    const status = element('div', {
      class: 'screen-reader-text',
      role: 'status',
      'aria-live': 'polite',
    });

    comboWrap.append(input, listbox);
    root.append(list, inputLabel, comboWrap, help, status);

    for (const box of checkboxes) {
      box.disabled = true;
    }
    group.hidden = true;
    group.after(root);

    /**
     * @param {string} value
     * @returns {string}
     */
    function labelOf(value) {
      return options.find((option) => option.value === value)?.label ?? value;
    }

    /**
     * @param {string} message
     */
    function announce(message) {
      status.textContent = message;
    }

    /** @returns {FieldOption[]} */
    function addable() {
      const query = input.value.trim().toLowerCase();
      return options.filter(
        (option) => !chosen.includes(option.value) && option.label.toLowerCase().includes(query),
      );
    }

    /**
     * @param {boolean} open
     */
    function setOpen(open) {
      listbox.hidden = !open;
      input.setAttribute('aria-expanded', String(open));
      if (!open) {
        activeIndex = -1;
        input.removeAttribute('aria-activedescendant');
      }
    }

    function renderListbox() {
      const matches = addable();
      listbox.replaceChildren();
      if (matches.length === 0) {
        const empty = element(
          'li',
          { class: 'spreadshop-chips__empty', 'aria-disabled': 'true' },
          chosen.length === options.length
            ? __('Every field is already shown.', 'spreadshop')
            : __('No field matches.', 'spreadshop'),
        );
        listbox.append(empty);
        activeIndex = -1;
        input.removeAttribute('aria-activedescendant');
        return;
      }
      activeIndex = Math.min(activeIndex, matches.length - 1);
      matches.forEach((option, position) => {
        const item = element(
          'li',
          {
            id: `${idBase}-option-${option.value}`,
            role: 'option',
            'aria-selected': String(position === activeIndex),
            'data-value': option.value,
          },
          option.label,
        );
        listbox.append(item);
      });
      if (activeIndex >= 0) {
        input.setAttribute(
          'aria-activedescendant',
          `${idBase}-option-${matches[activeIndex].value}`,
        );
      } else {
        input.removeAttribute('aria-activedescendant');
      }
    }

    /**
     * @param {'up' | 'down' | 'remove'} action
     * @param {string} value
     * @param {string} label
     * @returns {HTMLButtonElement}
     */
    function chipButton(action, value, label) {
      const texts = {
        /* translators: %s: a card field, e.g. "Price". */
        up: [sprintf(__('Move %s up', 'spreadshop'), label), '↑'],
        /* translators: %s: a card field, e.g. "Price". */
        down: [sprintf(__('Move %s down', 'spreadshop'), label), '↓'],
        /* translators: %s: a card field, e.g. "Price". */
        remove: [sprintf(__('Remove %s', 'spreadshop'), label), '×'],
      };
      const button = /** @type {HTMLButtonElement} */ (
        element(
          'button',
          {
            type: 'button',
            class: `button-link spreadshop-chip__${action}`,
            'aria-label': texts[action][0],
            'data-action': action,
            'data-value': value,
          },
          texts[action][1],
        )
      );
      return button;
    }

    function renderChips() {
      list.replaceChildren();
      for (const value of chosen) {
        const label = labelOf(value);
        const chip = element('li', { class: 'spreadshop-chip' });
        const name = element('span', { class: 'spreadshop-chip__label' }, label);
        const posted = element('input', {
          type: 'hidden',
          name: 'spreadshopCardFields[]',
          value,
        });
        chip.append(
          name,
          chipButton('up', value, label),
          chipButton('down', value, label),
          chipButton('remove', value, label),
          posted,
        );
        list.append(chip);
      }
    }

    /**
     * @param {string} value
     * @param {'up' | 'down' | 'remove'} action
     */
    function focusChipButton(value, action) {
      const button = /** @type {HTMLButtonElement | null} */ (
        list.querySelector(`[data-value="${value}"][data-action="${action}"]`)
      );
      button?.focus();
    }

    /**
     * @param {string} value
     */
    function add(value) {
      if (chosen.includes(value)) {
        return;
      }
      chosen = [...chosen, value];
      input.value = '';
      renderChips();
      renderListbox();
      /* translators: %s: a card field, e.g. "Price". */
      announce(sprintf(__('%s added.', 'spreadshop'), labelOf(value)));
    }

    /**
     * @param {string} value
     */
    function remove(value) {
      const position = chosen.indexOf(value);
      chosen = chosen.filter((item) => item !== value);
      renderChips();
      renderListbox();
      /* translators: %s: a card field, e.g. "Price". */
      announce(sprintf(__('%s removed.', 'spreadshop'), labelOf(value)));
      const next = chosen[Math.min(position, chosen.length - 1)];
      if (next) {
        focusChipButton(next, 'remove');
      } else {
        input.focus();
      }
    }

    /**
     * @param {string} value
     * @param {-1 | 1} step
     * @param {'up' | 'down'} keepFocusOn
     */
    function move(value, step, keepFocusOn) {
      const from = chosen.indexOf(value);
      const to = from + step;
      const label = labelOf(value);
      if (to < 0 || to >= chosen.length) {
        announce(
          step < 0
            ? /* translators: %s: a card field, e.g. "Price". */
              sprintf(__('%s is already first.', 'spreadshop'), label)
            : /* translators: %s: a card field, e.g. "Price". */
              sprintf(__('%s is already last.', 'spreadshop'), label),
        );
        return;
      }
      const reordered = [...chosen];
      reordered.splice(from, 1);
      reordered.splice(to, 0, value);
      chosen = reordered;
      renderChips();
      focusChipButton(value, keepFocusOn);
      announce(
        sprintf(
          /* translators: 1: a card field, e.g. "Price"; 2: its new position; 3: how many fields are chosen. */
          __('%1$s moved to position %2$d of %3$d.', 'spreadshop'),
          label,
          to + 1,
          chosen.length,
        ),
      );
    }

    list.addEventListener('click', (event) => {
      const button = /** @type {HTMLElement} */ (event.target).closest('button[data-action]');
      if (!(button instanceof HTMLButtonElement)) {
        return;
      }
      const value = button.dataset.value ?? '';
      const action = button.dataset.action;
      if (action === 'remove') {
        remove(value);
      } else if (action === 'up') {
        move(value, -1, 'up');
      } else if (action === 'down') {
        move(value, 1, 'down');
      }
    });

    list.addEventListener('keydown', (event) => {
      const button = /** @type {HTMLElement} */ (event.target).closest('button[data-action]');
      if (!(button instanceof HTMLButtonElement) || !event.altKey) {
        return;
      }
      if (event.key !== 'ArrowUp' && event.key !== 'ArrowDown') {
        return;
      }
      event.preventDefault();
      const value = button.dataset.value ?? '';
      const step = event.key === 'ArrowUp' ? -1 : 1;
      move(value, step, step < 0 ? 'up' : 'down');
      // The accelerator works from any chip button; focus returns to the one it came from.
      if (button.dataset.action === 'remove') {
        focusChipButton(value, 'remove');
      }
    });

    input.addEventListener('input', () => {
      activeIndex = addable().length > 0 ? 0 : -1;
      setOpen(true);
      renderListbox();
    });

    input.addEventListener('click', () => {
      setOpen(true);
      renderListbox();
    });

    input.addEventListener('keydown', (event) => {
      const matches = addable();
      switch (event.key) {
        case 'ArrowDown':
          event.preventDefault();
          setOpen(true);
          activeIndex = matches.length === 0 ? -1 : (activeIndex + 1) % matches.length;
          renderListbox();
          break;
        case 'ArrowUp':
          event.preventDefault();
          setOpen(true);
          activeIndex =
            matches.length === 0 ? -1 : (activeIndex - 1 + matches.length) % matches.length;
          renderListbox();
          break;
        case 'Enter':
          // Never let Enter in this field submit the whole settings form.
          event.preventDefault();
          if (!listbox.hidden && activeIndex >= 0 && matches[activeIndex]) {
            add(matches[activeIndex].value);
          }
          break;
        case 'Escape':
          if (!listbox.hidden) {
            event.preventDefault();
            setOpen(false);
          } else {
            input.value = '';
          }
          break;
        case 'Backspace':
          if (input.value === '' && chosen.length > 0) {
            event.preventDefault();
            const last = chosen[chosen.length - 1];
            chosen = chosen.slice(0, -1);
            renderChips();
            renderListbox();
            /* translators: %s: a card field, e.g. "Price". */
            announce(sprintf(__('%s removed.', 'spreadshop'), labelOf(last)));
          }
          break;
        default:
          break;
      }
    });

    input.addEventListener('blur', () => {
      setOpen(false);
    });

    // mousedown, not click: a click would blur the input first and close the list under it.
    listbox.addEventListener('mousedown', (event) => {
      event.preventDefault();
      const item = /** @type {HTMLElement} */ (event.target).closest('[role="option"]');
      if (item instanceof HTMLElement && item.dataset.value) {
        add(item.dataset.value);
      }
    });

    renderChips();
    renderListbox();
  }

  document.addEventListener('DOMContentLoaded', () => {
    /** @type {NodeListOf<HTMLFieldSetElement>} */
    const groups = document.querySelectorAll('fieldset[data-spreadshop-card-fields]');
    groups.forEach(upgrade);
  });
})();
