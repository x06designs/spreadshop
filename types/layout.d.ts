/* Generated from spreadshop/schema/layout.schema.json by scripts/gen-types.js. Do not edit. */

/**
 * The layout options: which parts of the shop's own chrome are restyled, which fields a product card shows, and the colour overrides.
 */
export interface SpreadshopLayoutSettings {
/**
 * Shows the shop's header as a sidebar navigation.
 */
spreadshopLayoutSidebar: (0 | 1)
/**
 * Collapses the shop's footer into one row of service and legal links.
 */
spreadshopLayoutCompactFooter: (0 | 1)
/**
 * Adds product details from the shop's own data to the list tiles.
 */
spreadshopLayoutCards: (0 | 1)
/**
 * Restyles the product page: separated sections, tabs for its details, a tidier design, tags and sharing block.
 */
spreadshopLayoutProductPage: (0 | 1)
/**
 * Fields a product card shows after the name, in display order. hoverImage sits on the image, so its position has no effect and it is kept last.
 */
spreadshopCardFields: ("productType" | "price" | "swatches" | "sizes" | "hoverImage")[]
/**
 * What the shop opens on when no start token or deeplink is set.
 */
spreadshopStartList: ("designs" | "products")
/**
 * Colour overrides; an absent key falls back to the theme's mapping, then to the plugin default.
 */
spreadshopColors: {
accent?: string
accentText?: string
background?: string
text?: string
muted?: string
border?: string
}
}
