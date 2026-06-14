=== Versus - Product Compare for WooCommerce ===
Contributors: wppoland
Tags: woocommerce, compare, product comparison, comparison table, accessibility
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Fast, accessible product comparison for WooCommerce: side-by-side table, difference highlighting, guest and customer lists, no jQuery.

== Description ==

Versus adds a "Compare" button to your WooCommerce shop, archive and single product pages. Shoppers build a list of products and view them side by side in a clean comparison table — without leaving your store.

The table shows the product image, name, price, SKU, availability and short description, plus a row for every product attribute (colour, size, material and more). Rows whose values differ between products are highlighted, and a single toggle hides everything that is identical so the real differences stand out.

= Built for speed and accessibility =

* **No jQuery** in the plugin's own front-end code — the script is vanilla JS, deferred and loaded in the footer.
* **No layout shift (CLS).** The comparison table scrolls horizontally inside its own wrapper, so adding columns never reflows the page.
* **Keyboard friendly.** The compare buttons are real buttons with `aria-pressed` state that updates over AJAX.
* **Guests and customers.** Logged-out visitors build a comparison stored per browser; logged-in customers get a "Compare" tab in My Account, and a guest list is merged into the account on login.

= Settings =

A WooCommerce-capability settings page (Versus menu) lets you:

* Enable or disable comparison and set how many products can be compared at once (2–6).
* Choose where the compare button appears (loops, single product) and whether guests can use it.
* Choose which standard fields appear as rows (price, SKU, availability, short description) and whether to include product attributes.
* Toggle difference highlighting, the "differences only" default, and the image / add-to-cart / remove controls in each column header.
* Customise the front-end strings — the compare button, remove button, compare link, differences toggle, clear-all button and empty-list message — or leave them on their translated defaults.

= Translation ready =

All strings are translatable through the `versus` text domain, and a `versus.pot` template ships in `/languages`. Deleting the plugin removes its options and the comparison table.

= Engine =

The comparison orchestration (endpoint, nonce, asset enqueue, AJAX, guest cookie, difference calculation) is provided by the shared, namespace-neutral `wppoland/storefront-kit` Compare engine; this plugin is a thin adapter that supplies the text domain, options, asset URLs, storage and the table / button markup.

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

== Screenshots ==

1. The side-by-side comparison table with difference highlighting.
2. The Versus settings screen.

== Changelog ==

= 0.2.0 =
* Added a "Labels & text" section to the settings screen so the compare button, remove button, compare link, differences toggle, clear-all button and empty-list message can be customised (empty falls back to the translated default).
* Made the plugin fully translation ready: `Domain Path` header, text-domain loading on `init`, and a `versus.pot` template in `/languages`.
* Added `uninstall.php` cleanup that removes the plugin options and the comparison-items table when the plugin is deleted.

= 0.1.0 =
* Initial release: accessible product comparison for WooCommerce with a difference-highlighting table, guest + customer lists, and a settings page for compared fields and placement.
