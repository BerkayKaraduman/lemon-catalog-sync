=== Lemon Catalog Sync for Elementor ===
Contributors: berkaykaraduman
Tags: lemon squeezy, digital products, elementor, catalog, sync
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 3.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sync Lemon Squeezy products with WordPress and build fully customizable product experiences with Elementor — no WooCommerce required.

== Description ==

Lemon Squeezy is the source of product data; WordPress owns URLs, categories and SEO; Elementor owns the design.

* Every Lemon Squeezy product becomes its own `lcs_product` post with its own URL (default `/urun/product-slug/`).
* Name, price, description, image, variants, status and Buy Now URL are synced from the official Lemon Squeezy API (all pages, `include=variants`, store filtered).
* Sync never touches post content, Elementor data, URL slugs, categories, excerpts or Yoast / Rank Math data. Only `_lcs_*` meta, the title and publish/draft status are written.
* Products that disappear from Lemon Squeezy are drafted and flagged — never deleted — and only after a complete, error-free fetch.
* Elementor widgets: LCS Product Image, Product Price, Compare Price, Card Subtitle, Card Badge, Product Link, Lemon Description, Buy Button, Variant Selector, Product Meta. Dynamic Tags for Elementor Pro. Works with Theme Builder Single and Archive templates and Elementor Pro Loop Grid / Loop Item.
* Card fields managed in WordPress ("Kart Görünümü" box): compare-at price, card subtitle and badge. The sync never changes them.
* Works without Elementor: shortcodes and an automatic product block.
* Hosted checkout or Lemon.js checkout overlay (official CDN, loaded only where needed).
* Manual sync and WP-Cron (15 min, hourly, twice daily, daily) with a shared lock and 429 Retry-After handling.

== Installation ==

1. Upload the plugin ZIP via Plugins → Add New → Upload Plugin and activate it.
2. Recommended: add your API key to wp-config.php, above the line `/* That's all, stop editing! Happy publishing. */`:
   `define( 'LCS_LEMON_API_KEY', 'YOUR_LEMON_SQUEEZY_API_KEY' );`
   (Alternatively paste it in Lemon Catalog Sync → Settings; it is then stored encrypted.) Never commit wp-config.php or the key to a repository.
3. Click "Test Connection" and select your store.
   Start with a Lemon Squeezy test mode API key. When your store goes live, replace it with a live mode key and sync again.
4. Run Lemon Catalog Sync → Sync → SYNC NOW.
5. With Elementor Pro: create a Theme Builder Single template for "Dijital Ürünler" and include the Post Content widget. Without it: choose "Automatic Product Block" as Frontend Display Mode.

== Frequently Asked Questions ==

= Will a sync overwrite my Elementor design? =

No. The sync only writes `_lcs_*` meta (except the WordPress-managed card fields `_lcs_compare_price`, `_lcs_card_subtitle`, `_lcs_card_badge`), the post title and publish/draft status. `_elementor_*` meta, post content, slug, categories and SEO data are never written.

= What happens if the Lemon Squeezy API fails? =

Nothing changes. Missing-product detection only runs after every page was fetched without errors.

= Where is per-product Elementor content shown? =

Where the Post Content widget sits in your Theme Builder Single template.

== Shortcodes ==

`[lcs_product_price]`, `[lcs_product_image]`, `[lcs_lemon_description]`, `[lcs_product_buy_button]`, `[lcs_product_variants]`, `[lcs_product_meta]`, `[lcs_products category="preset" columns="3" limit="12" orderby="date" order="DESC" card_fields="yes"]`, `[lcs_compare_price strike="yes"]`, `[lcs_card_subtitle]`, `[lcs_card_badge]`, `[lcs_product_link text="İncele"]`

== Author ==

Yahya Berkay Karaduman — Instagram: @yahyaberkay (https://www.instagram.com/yahyaberkay/)

== Changelog ==

= 3.0.0 =
* First public release since 1.0.0; includes everything from 1.1.0–1.3.0 below.
* Secure API key configuration via wp-config.php (LCS_LEMON_API_KEY); single credential service; key never displayed, logged or included in error messages.
* Product Compare Price, Card Badge and Card Subtitle, saved per product with a dedicated AJAX "Save Card Details" button and never changed by the sync.
* Elementor: LCS Compare Price, Card Badge, Card Subtitle and Product Link widgets and dynamic tags; Elementor Pro Loop Grid / Loop Item support with correct per-card product context.
* Improved data ownership protection: WordPress-owned card fields are excluded from every sync write.
* Display name "Lemon Catalog Sync for Elementor"; the plugin folder, text domain, post type and meta keys are unchanged, so existing installations update in place.

= 3.0.0 upgrade notice =
Upload the ZIP and choose "Replace current with uploaded". Products, settings and Elementor designs are kept.

= 1.3.0 =
* Security: all API key access goes through one credential service (LCS\Credentials). LCS_LEMON_API_KEY in wp-config.php is the primary source; while it is defined the key is never shown, never stored in wp_options, and the settings field is replaced by "API key is configured securely via wp-config.php".
* Security: database fallback only without the constant — encrypted, not autoloaded, masked, never echoed back. Option to remove a leftover database key; the key is always deleted on uninstall.
* Security: the key is redacted from every error message, including a candidate key tested before saving.

= 1.2.0 =
* Added reliable AJAX-based per-product card metadata storage ("Kart Bilgilerini Kaydet" button, independent of the post Update).
* Fixed Compare Price persistence.
* Fixed Card Badge persistence.
* Added Card Subtitle persistence.
* Improved Elementor Loop Item card metadata context.

= 1.1.1 =
* Fixed Compare Price persistence and REST save issue.

= 1.1.0 =
* Added WordPress-managed card fields (compare price, subtitle, badge) with a "Kart Görünümü" box; the sync never changes them.
* Added LCS Compare Price, Card Subtitle, Card Badge and Product Link widgets and dynamic tags; Loop Grid / Loop Item support.
* Added matching shortcodes; [lcs_products] shows filled card fields (unchanged output otherwise).

= 1.0.0 =
* Initial release.
