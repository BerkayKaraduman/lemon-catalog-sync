=== Lemon Catalog Sync for Elementor ===
Contributors: berkaykaraduman
Tags: lemon squeezy, digital products, elementor, catalog, sync
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sync your Lemon Squeezy catalog into a "Dijital Ürünler" post type with per-product Elementor design. No WooCommerce.

== Description ==

Lemon Squeezy is the source of product data; WordPress owns URLs, categories and SEO; Elementor owns the design.

* Every Lemon Squeezy product becomes its own `lcs_product` post with its own URL (default `/urun/product-slug/`).
* Name, price, description, image, variants, status and Buy Now URL are synced from the official Lemon Squeezy API (all pages, `include=variants`, store filtered).
* Sync never touches post content, Elementor data, URL slugs, categories, excerpts or Yoast / Rank Math data. Only `_lcs_*` meta, the title and publish/draft status are written.
* Products that disappear from Lemon Squeezy are drafted and flagged — never deleted — and only after a complete, error-free fetch.
* Elementor widgets: LCS Product Image, Product Price, Lemon Description, Buy Button, Variant Selector, Product Meta. Dynamic Tags for Elementor Pro. Works with Theme Builder Single and Archive templates.
* Works without Elementor: shortcodes and an automatic product block.
* Hosted checkout or Lemon.js checkout overlay (official CDN, loaded only where needed).
* Manual sync and WP-Cron (15 min, hourly, twice daily, daily) with a shared lock and 429 Retry-After handling.

== Installation ==

1. Upload the plugin ZIP via Plugins → Add New → Upload Plugin and activate it.
2. Add your API key in Lemon Catalog Sync → Settings, or in wp-config.php: `define( 'LCS_LEMON_API_KEY', '...' );`
3. Click "Test Connection" and select your store.
   Start with a Lemon Squeezy test mode API key. When your store goes live, replace it with a live mode key and sync again.
4. Run Lemon Catalog Sync → Sync → SYNC NOW.
5. With Elementor Pro: create a Theme Builder Single template for "Dijital Ürünler" and include the Post Content widget. Without it: choose "Automatic Product Block" as Frontend Display Mode.

== Frequently Asked Questions ==

= Will a sync overwrite my Elementor design? =

No. The sync only writes `_lcs_*` meta, the post title and publish/draft status. `_elementor_*` meta, post content, slug, categories and SEO data are never written.

= What happens if the Lemon Squeezy API fails? =

Nothing changes. Missing-product detection only runs after every page was fetched without errors.

= Where is per-product Elementor content shown? =

Where the Post Content widget sits in your Theme Builder Single template.

== Shortcodes ==

`[lcs_product_price]`, `[lcs_product_image]`, `[lcs_lemon_description]`, `[lcs_product_buy_button]`, `[lcs_product_variants]`, `[lcs_product_meta]`, `[lcs_products category="preset" columns="3" limit="12" orderby="date" order="DESC"]`

== Author ==

Yahya Berkay Karaduman — Instagram: @yahyaberkay (https://www.instagram.com/yahyaberkay/)

== Changelog ==

= 1.0.0 =
* Initial release.
