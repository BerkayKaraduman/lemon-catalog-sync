# Lemon Catalog Sync for Elementor

[![Version](https://img.shields.io/badge/version-3.0.0-7047eb)](https://github.com/BerkayKaraduman/lemon-catalog-sync-for-elementor/releases/tag/v3.0.0)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb4)](https://www.php.net)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green)](LICENSE)

A WordPress plugin that syncs your Lemon Squeezy products into WordPress and lets you build fully custom product catalogs and product pages with Elementor — no WooCommerce required.

- Lemon Squeezy → WordPress product sync (automatic and manual)
- No WooCommerce required
- Elementor & Elementor Pro compatible (Theme Builder, Loop Grid)
- A real WordPress page per product, with its own URL and Elementor content
- Compare price, card badge and card subtitle per product
- Lemon Squeezy checkout (hosted page or overlay)

## How it works

```
Lemon Squeezy
     ↓
Lemon Catalog Sync for Elementor
     ↓
WordPress "Digital Products" (lcs_product)
     ↓
Elementor
     ↓
Lemon Squeezy Checkout
```

Commercial data comes from **Lemon Squeezy**: product name, current price, image, description, checkout URL and variants.
**WordPress** manages everything around it: categories, Elementor design, SEO, compare price, card badge, card subtitle and custom product content.

## Installation

1. Download the plugin ZIP from the [latest release](https://github.com/BerkayKaraduman/lemon-catalog-sync-for-elementor/releases/latest) and upload it in **Plugins → Add New → Upload Plugin**.
2. Activate the plugin.
3. Add your Lemon Squeezy API key to `wp-config.php` (see below).
4. Open **Lemon Catalog Sync → Settings** and click **Test Connection**.
5. Select your store and save.
6. Open **Lemon Catalog Sync → Sync** and click **Sync Now**. Your products appear under **Dijital Ürünler** (Digital Products).

Set **Automatic Sync Frequency** in Settings to keep prices and new products up to date.

## Secure API Key Setup

Storing the Lemon Squeezy API key in the WordPress database or in plugin source code is not recommended. For production, define it in `wp-config.php`, **above** the line `/* That's all, stop editing! Happy publishing. */`:

```php
define( 'LCS_LEMON_API_KEY', 'YOUR_LEMON_SQUEEZY_API_KEY' );
```

The Settings page then shows *"API key is configured securely via wp-config.php"* and the key is never displayed or stored in the database. It is only used server-side to call the Lemon Squeezy API.

- Never commit `wp-config.php` or the API key to GitHub.
- Never put the key in JavaScript, Elementor widgets or any public frontend code.
- Start with a Lemon Squeezy **test mode** key; switch to a **live mode** key when your store goes live.

## Elementor Setup

Elementor is optional. Without it, choose **Automatic Product Block** as the Frontend Display Mode or use the shortcodes.

**Single product template (Elementor Pro Theme Builder):** create a Single template with the condition **Dijital Ürünler → All**, add the LCS widgets you need and include the **Post Content** widget — it shows the content you design per product with *Edit with Elementor*.

Widgets in the **Lemon Catalog** category:

| Widget | Shows |
|---|---|
| LCS Product Image | Lemon product image |
| LCS Product Price | Current Lemon price |
| LCS Compare Price | Old / list price, optionally struck through |
| LCS Card Badge | Per-product badge |
| LCS Card Subtitle | Per-product short description |
| LCS Lemon Description | Lemon product description |
| LCS Buy Button | Lemon checkout button |
| LCS Variant Selector | Product variants |
| LCS Product Meta | Product details (ID, price, variants…) |
| LCS Product Link | Link to the product page or checkout |

Matching **Dynamic Tags** (group *Lemon Catalog*) are available for any Elementor field.

## Product Cards with Elementor Loop Grid

Design one product card as an Elementor Pro **Loop Item** and reuse it for every product. Each card automatically shows its own product's data.

```
[ Badge ]
[ Product Image ]
Product Category
Product Name
Card Subtitle
$50.00   $20.00
(old)    (Lemon price)
[ View Product ]
```

1. **Templates → Theme Builder → Loop Item**, preview source *Dijital Ürünler*.
2. Add *LCS Card Badge*, *LCS Product Image*, *Post Title*, *LCS Card Subtitle*, *LCS Compare Price*, *LCS Product Price* and *LCS Product Link*, and style them in the Style tab.
3. Add a **Loop Grid** widget to any page, select the template and set **Query → Source → Dijital Ürünler**.

- **Current price** comes from Lemon Squeezy automatically (*LCS Product Price*).
- **Old / compare price** is entered per product in WordPress and shown with *LCS Compare Price*, struck through by default (e.g. ~~$50.00~~ $20.00). It uses the same currency format as the Lemon price.

## Compare Price, Card Badge and Card Subtitle

Every product has a **Kart Görünümü** (Card Appearance) box on its edit screen:

| Field | Example |
|---|---|
| Eski / Liste Fiyatı (Compare Price) | `50` or `39.90` — numbers only, currency is added automatically |
| Kart Rozeti (Card Badge) | Best Seller, New, 60% Off, Featured — or *Çok Satan*, *Yeni*, *%60 İndirim* |
| Kart Alt Açıklaması (Card Subtitle) | *100+ Cinematic LUTs*, *Professional Lightroom Preset Collection* |

Click **Kart Bilgilerini Kaydet** (Save Card Details) — the values are saved instantly, without updating the page. Empty fields show nothing on the live site. These values are stored as per-product WordPress data and are never changed by the Lemon sync.

## Data Ownership

| Lemon Squeezy manages | WordPress manages |
|---|---|
| Product name | Categories |
| Current price | Compare price |
| Image | Card badge |
| Description | Card subtitle |
| Checkout | Elementor content & product page design |
| Product status | SEO |

The sync only updates Lemon data. It never overwrites WordPress-owned data, URL slugs, Elementor designs or SEO settings. Products removed from Lemon Squeezy are set to draft, never deleted.

## WooCommerce

WooCommerce is not required. The plugin uses its own `lcs_product` custom post type, and checkout is handled by Lemon Squeezy.

## Shortcodes

```
[lcs_products columns="3" limit="12"]   product grid
[lcs_product_price]  [lcs_compare_price]  [lcs_card_badge]  [lcs_card_subtitle]
[lcs_product_image]  [lcs_lemon_description]  [lcs_product_buy_button]
[lcs_product_variants]  [lcs_product_meta]  [lcs_product_link]
```

## Requirements

- WordPress 6.0+
- PHP 8.1+
- A Lemon Squeezy account and API key
- Elementor 3.5+ (optional) — Elementor Pro only for Theme Builder templates and Loop Grid

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).

## Author

Yahya Berkay Karaduman

- Instagram: [@yahyaberkay](https://www.instagram.com/yahyaberkay/)
