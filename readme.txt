=== Versus - Product Compare for WooCommerce ===
Contributors: motylanogha
Tags: woocommerce, product compare, compare products, product comparison, comparison table
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Fast product compare for WooCommerce: side-by-side comparison table, difference highlighting, guest and customer lists. No jQuery.

== Description ==

Versus adds a "Compare" button to your WooCommerce shop, archive and single product pages. Shoppers compare products side by side in a WooCommerce comparison table, with product data kept inside your store.

Versus is developed in the open. The code, and a place to report bugs or request features, live at https://github.com/wppoland/versus.

The table shows the product image, name, price, SKU, availability and short description, plus a row for every product attribute (colour, size, material and more). Rows whose values differ between products are highlighted, and a single toggle hides everything that is identical so the real differences stand out.

= Documentation and links =

* **Documentation** - https://plogins.com/versus/docs/
* **Plugin page** - https://plogins.com/versus/
* **Source code** - https://github.com/wppoland/versus
* **Bug reports and feature requests** - https://github.com/wppoland/versus/issues
* **Discussions and questions** - https://github.com/wppoland/versus/discussions


= Built for speed and accessibility =

* **No jQuery** in the plugin's own front-end code, the script is vanilla JS, deferred and loaded in the footer.
* **No layout shift (CLS).** The comparison table scrolls horizontally inside its own wrapper, so adding columns never reflows the page.
* **Keyboard friendly.** The compare buttons are real buttons with `aria-pressed` state that updates over AJAX.
* **Guests and customers.** Logged-out visitors build a comparison stored per browser; logged-in customers get a "Compare" tab in My Account, and a guest list is merged into the account on login.

= Settings =

A WooCommerce-capability settings page (Versus menu) lets you:

* Enable or disable comparison and set how many products can be compared at once (2–6).
* Choose where the compare button appears (loops, single product) and whether guests can use it.
* Choose which standard fields appear as rows (price, SKU, availability, short description) and whether to include product attributes.
* Toggle difference highlighting, the "differences only" default, and the image / add-to-cart / remove controls in each column header.
* Customise the front-end strings, the compare button, remove button, compare link, differences toggle, clear-all button and empty-list message, or leave them on their translated defaults.

= Translation ready =

All strings are translatable through the `versus` text domain, and a `versus.pot` template ships in `/languages`. Deleting the plugin removes its options and the comparison table.

= How it works =

Adding or removing a product is a single nonce-verified AJAX request; no full page reload. Guest selections are kept in a per-browser cookie for six months and merged into the account on login. The CSS and JavaScript are enqueued only on pages that actually show the compare button or the table.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/versus`, or install via Plugins → Add New.
2. Activate it. WooCommerce must be active.
3. Visit the **Versus** menu in wp-admin to choose the compared fields and placement.

== Frequently Asked Questions ==

= Does it require WooCommerce? =

Yes. Versus requires an active WooCommerce installation.

= Does it use jQuery? =

No. The plugin's own front-end script is vanilla JavaScript with no jQuery dependency.

= Can guests compare products? =

Yes, when "Allow guests" is enabled. A guest's comparison is stored per browser and is merged into their account when they log in.

= Where does the compare button appear? =

On shop and archive loops and on the single product page, depending on your settings. The comparison itself opens on the My Account "Compare" tab for customers, or a dedicated compare URL for guests.

= What fields appear in the comparison table? =

The table can show product image, name, price, SKU, availability, short description and product attributes such as size, colour or material.

= Can shoppers hide identical rows? =

Yes. Difference highlighting and a "differences only" toggle help shoppers focus on what actually changes between compared products.

= Does the product compare list work for logged-in customers? =

Yes. Logged-in customers get a My Account Compare tab. Guest compare lists can be merged into the account after login.

== Screenshots ==

1. The side-by-side comparison table with difference highlighting.
2. The Versus settings screen.

== External Services ==

Versus does not connect to, or send any data to, any external service or third-party server. It bundles no SDK, API client, web font, map tile, CDN asset or analytics call, everything runs on your own site.

Comparison data stays inside your WordPress database: a custom `{prefix}versus_compare_items` table holds the compared product IDs, the plugin settings live in the `versus_settings` option (with `versus_db_version` tracking the schema), and a guest's selection is kept in a first-party cookie in their own browser. Adding or removing a product is a same-origin AJAX request to your site's own `admin-ajax.php`; no outbound HTTP request is ever made. Deleting the plugin removes those options and drops the table.

== Changelog ==

= 0.2.0 =
* Polished every interface: inline help tooltips on every setting, a modern themeable comparison table (CSS custom properties, fluid sizing, dark-mode and reduced-motion support), a friendly empty-state on the comparison page, and a live count badge on the compare link.
* Improved accessibility: accessible "?" help affordances wired via `aria-describedby`, a polite live region that announces compare changes to screen readers, visible focus styles and full keyboard operability.
* More robust front-end: graceful handling of network failures, guard against double submission, automatic table refresh after a remove, and friendly fallbacks for missing data.
* Added a "Labels & text" section to the settings screen so the compare button, remove button, compare link, differences toggle, clear-all button and empty-list message can be customised (empty falls back to the translated default).
* Made the plugin fully translation ready: `Domain Path` header and a `versus.pot` template in `/languages` (WordPress loads the translations automatically).
* Added `uninstall.php` cleanup that removes the plugin options and the comparison-items table when the plugin is deleted.

= 0.1.0 =
* Initial release: accessible product comparison for WooCommerce with a difference-highlighting table, guest + customer lists, and a settings page for compared fields and placement.
