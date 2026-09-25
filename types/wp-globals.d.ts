/**
 * The globals the plugin's classic scripts use: WordPress's, as the script handles they depend
 * on provide them, and the plugin's own layout namespace. Hand-written: classic scripts share
 * globals, not modules.
 */

interface SpreadshopColorPickerHost {
  wpColorPicker(options?: Record<string, unknown>): SpreadshopColorPickerHost;
}

declare function jQuery(selectorOrReady: string | (() => void)): SpreadshopColorPickerHost;

declare const wp: {
  i18n: typeof import('@wordpress/i18n');
};

interface SpreadshopSwatch {
  hex: string;
  name: string;
}

interface SpreadshopCardModel {
  key: string;
  name: string;
  productType: string | null;
  price: string;
  swatches: SpreadshopSwatch[];
  sizes: { first: string; last: string; count: number } | null;
  hoverImage: string | null;
}

interface SpreadshopListModel {
  designBased: boolean;
  cards: Map<string, SpreadshopCardModel>;
}

interface SpreadshopLayoutData {
  parseList(payload: unknown): SpreadshopListModel | null;
  keyFromHref(href: string): string | null;
  productTypeOf(name: string, pureName: string): string | null;
}

interface Window {
  spreadshopLayoutConfig?: {
    cardFields: string[];
  };
  spreadshopLayout?: {
    onRender?: (enhance: (shop: HTMLElement) => void) => void;
    data?: SpreadshopLayoutData;
  };
}
