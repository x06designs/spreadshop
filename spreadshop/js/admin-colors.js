/**
 * Turns the Layout section's colour fields into core's colour picker.
 *
 * Without JavaScript they stay plain #rrggbb text fields; the picker adds a swatch and a
 * Clear button, and an empty field means "use the theme's colour".
 */
(() => {
  'use strict';

  jQuery(() => {
    jQuery('.spreadshop-color').wpColorPicker();
  });
})();
