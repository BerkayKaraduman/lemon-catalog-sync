# Lemon Catalog Sync for Elementor

[![Version](https://img.shields.io/badge/version-1.0.0-7047eb)](https://github.com/BerkayKaraduman/lemon-catalog-sync/releases/tag/v1.0.0)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb4)](https://www.php.net)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green)](LICENSE)
[![Instagram](https://img.shields.io/badge/Instagram-@yahyaberkay-e4405f)](https://www.instagram.com/yahyaberkay/)

**English** · [Türkçe](#türkçe)

---

## English

### What it does

Sell digital products with **Lemon Squeezy** and show them on **WordPress** — without WooCommerce.

You add a product to Lemon Squeezy **once**. The plugin automatically creates a WordPress product page for it (e.g. `yoursite.com/urun/lightroom-preset-pack/`) and keeps the name, price, description, image, variants and buy link up to date. You design the page with **Elementor** (or use the built-in layout), and your design, URL, categories and SEO are **never overwritten** by a sync.

### Features

- 🔄 **Automatic Sync** — every 15 minutes, hourly, twice daily or daily (plus a manual "Sync Now" button)
- 🧩 **Elementor** — 6 widgets (Image, Price, Description, Buy Button, Variants, Meta), Dynamic Tags, Theme Builder support
- 🛡️ **Your content is safe** — sync only updates Lemon data; Elementor designs, URLs, categories and Yoast / Rank Math data are never touched
- 💳 **Checkout** — Lemon hosted checkout or the Lemon.js overlay
- 🧱 **Works without Elementor** — automatic product layout and shortcodes
- 🚫 **No WooCommerce, no Composer, no build step** — works on shared hosting

### Quick start

1. **Download** `lemon-catalog-sync.zip` from the [latest release](https://github.com/BerkayKaraduman/lemon-catalog-sync/releases/latest).
2. In WordPress go to **Plugins → Add New → Upload Plugin**, upload the ZIP and click **Activate**.
3. In Lemon Squeezy, turn on **Test mode** and create an API key under **Settings → API**.
4. In WordPress go to **Lemon Catalog Sync → Settings**, paste the key and click **Save**, then **Test Connection**.
5. Select your **Store** and save.
6. Go to **Lemon Catalog Sync → Sync** and click **SYNC NOW**. Your products appear under **Dijital Ürünler**.
7. Set **Automatic Sync Frequency** in Settings (e.g. *Hourly*) so new products and price changes appear automatically.
8. Design your product page:
   - **Elementor Pro:** Theme Builder → Single → condition *Dijital Ürünler → All*. Add *LCS Product Image*, *Post Title*, *LCS Product Price*, *LCS Buy Button* and the **Post Content** widget.
   - **No Elementor Pro:** set **Frontend Display Mode** to *Automatic Product Block*.
9. **Going live:** when your store is out of test mode, create a **live mode API key** in Lemon Squeezy and replace the test key in Settings (or in `wp-config.php`), then run **SYNC NOW** again.

> ⚠️ **API key:** test first with a **test mode** key. After your store goes live, **replace it with a live mode key** — test keys only return test products.

Optional, more secure: put the key in `wp-config.php` instead of the database:

```php
define( 'LCS_LEMON_API_KEY', 'your-api-key' );
```

### Shortcodes

```
[lcs_products columns="3" limit="12"]            product grid
[lcs_products category="preset" columns="4"]     grid by category
[lcs_product_price]  [lcs_product_image]  [lcs_lemon_description]
[lcs_product_buy_button]  [lcs_product_variants]  [lcs_product_meta]
```

### Security

**No known security issues.** The plugin was reviewed and tested for:

- **API key never exposed** — not in page HTML, JavaScript, REST API, error messages or logs; shown masked (`••••••••abcd`); stored encrypted and not autoloaded; `wp-config.php` constant supported
- **Admin protection** — every action requires the `manage_options` capability and a valid nonce
- **Safe input/output** — all input sanitized, all output escaped, Lemon HTML filtered with `wp_kses_post`
- **No data loss** — failed API calls change nothing; products removed from Lemon are set to draft, never deleted; uninstall keeps your products and content
- **Official APIs only** — WordPress HTTP API and the official Lemon Squeezy API / Lemon.js CDN

Found a problem? Please open an [issue](https://github.com/BerkayKaraduman/lemon-catalog-sync/issues).

### Requirements

WordPress 6.0+ · PHP 8.1+ · Lemon Squeezy account · Elementor 3.5+ *(optional)* · Elementor Pro *(optional, for Theme Builder)*

Tested with WordPress 7.1 and Elementor 4.3.

### Author

**Yahya Berkay Karaduman** · Instagram: [@yahyaberkay](https://www.instagram.com/yahyaberkay/)

License: [GPL-2.0-or-later](LICENSE)

---

## Türkçe

### Ne işe yarar?

**Lemon Squeezy** ile dijital ürün satın ve bu ürünleri **WordPress** sitenizde gösterin. WooCommerce gerekmez.

Ürünü Lemon Squeezy'ye **bir kere** eklersiniz. Eklenti otomatik olarak WordPress'te ürün sayfasını oluşturur (örn. `siteniz.com/urun/lightroom-preset-pack/`). Ad, fiyat, açıklama, görsel, varyantlar ve satın alma linki sürekli güncel kalır. Sayfayı **Elementor** ile tasarlarsınız (veya hazır düzeni kullanırsınız). Tasarımınız, URL'niz, kategorileriniz ve SEO ayarlarınız senkronizasyonda **asla bozulmaz**.

### Özellikler

- 🔄 **Otomatik Senkronizasyon** — 15 dakikada bir, saatlik, günde iki kez veya günlük (ayrıca manuel "Sync Now" butonu)
- 🧩 **Elementor** — 6 widget (Görsel, Fiyat, Açıklama, Satın Al Butonu, Varyantlar, Bilgiler), Dynamic Tag'ler, Theme Builder desteği
- 🛡️ **İçeriğiniz güvende** — sync sadece Lemon verisini günceller; Elementor tasarımı, URL, kategori ve Yoast / Rank Math verilerine dokunmaz
- 💳 **Ödeme** — Lemon hosted checkout veya Lemon.js overlay
- 🧱 **Elementor olmadan da çalışır** — otomatik ürün düzeni ve shortcode'lar
- 🚫 **WooCommerce, Composer veya build adımı yok** — paylaşımlı hostingde çalışır

### Adım adım kurulum

1. [Son sürümden](https://github.com/BerkayKaraduman/lemon-catalog-sync/releases/latest) `lemon-catalog-sync.zip` dosyasını **indirin**.
2. WordPress'te **Eklentiler → Yeni Ekle → Eklenti Yükle**'ye gidin, ZIP'i yükleyin ve **Etkinleştir**'e tıklayın.
3. Lemon Squeezy'de **Test mode**'u açın ve **Settings → API** bölümünden bir API anahtarı oluşturun.
4. WordPress'te **Lemon Catalog Sync → Settings**'e gidin, anahtarı yapıştırıp **Kaydet**'e, sonra **Test Connection**'a tıklayın.
5. **Store** (mağaza) seçip kaydedin.
6. **Lemon Catalog Sync → Sync** sayfasında **SYNC NOW**'a tıklayın. Ürünleriniz **Dijital Ürünler** menüsünde görünür.
7. Settings'te **Automatic Sync Frequency** seçin (örn. *Hourly*). Yeni ürünler ve fiyat değişiklikleri otomatik gelir.
8. Ürün sayfasını tasarlayın:
   - **Elementor Pro varsa:** Theme Builder → Single → koşul *Dijital Ürünler → All*. *LCS Product Image*, *Post Title*, *LCS Product Price*, *LCS Buy Button* ve **Post Content** widget'ını ekleyin.
   - **Elementor Pro yoksa:** **Frontend Display Mode** ayarını *Automatic Product Block* yapın.
9. **Yayına geçiş:** Mağazanız test modundan çıkınca Lemon Squeezy'de **live mode API anahtarı** oluşturun, Settings'teki (veya `wp-config.php`'deki) test anahtarını bununla değiştirin ve tekrar **SYNC NOW** yapın.

> ⚠️ **API anahtarı:** Önce **test mode** anahtarıyla deneyin. Mağazanız yayına alındıktan sonra anahtarı **live mode anahtarıyla güncelleyin** — test anahtarı yalnızca test ürünlerini getirir.

İsteğe bağlı, daha güvenli yöntem: anahtarı veritabanı yerine `wp-config.php` dosyasına yazın:

```php
define( 'LCS_LEMON_API_KEY', 'api-anahtariniz' );
```

**İpucu:** Her ürüne özel içerik (galeri, video, SSS…) eklemek için **Dijital Ürünler → ürün → Elementor ile Düzenle**. Bu içerik şablondaki **Post Content** alanında görünür ve sync tarafından asla değiştirilmez.

### Shortcode'lar

```
[lcs_products columns="3" limit="12"]            ürün listesi
[lcs_products category="preset" columns="4"]     kategoriye göre liste
[lcs_product_price]  [lcs_product_image]  [lcs_lemon_description]
[lcs_product_buy_button]  [lcs_product_variants]  [lcs_product_meta]
```

### Güvenlik

**Bilinen herhangi bir güvenlik sorunu yoktur.** Eklenti şu konularda incelendi ve test edildi:

- **API anahtarı hiçbir yerde görünmez** — sayfa HTML'i, JavaScript, REST API, hata mesajları ve loglarda yer almaz; maskeli gösterilir (`••••••••abcd`); şifreli ve autoload edilmeden saklanır; `wp-config.php` sabiti desteklenir
- **Yönetici koruması** — tüm işlemler `manage_options` yetkisi ve geçerli nonce ister
- **Güvenli girdi/çıktı** — tüm girdiler temizlenir, tüm çıktılar escape edilir, Lemon HTML'i `wp_kses_post` ile filtrelenir
- **Veri kaybı yok** — API hatasında hiçbir şey değişmez; Lemon'dan kaldırılan ürünler silinmez, taslağa alınır; eklenti kaldırılsa bile ürünler ve içerikler korunur
- **Sadece resmi API'ler** — WordPress HTTP API ve resmi Lemon Squeezy API / Lemon.js CDN

Bir sorun mu buldunuz? Lütfen bir [issue](https://github.com/BerkayKaraduman/lemon-catalog-sync/issues) açın.

### Gereksinimler

WordPress 6.0+ · PHP 8.1+ · Lemon Squeezy hesabı · Elementor 3.5+ *(isteğe bağlı)* · Elementor Pro *(isteğe bağlı, Theme Builder için)*

WordPress 7.1 ve Elementor 4.3 ile test edildi.

### Geliştirici

**Yahya Berkay Karaduman** · Instagram: [@yahyaberkay](https://www.instagram.com/yahyaberkay/)

Lisans: [GPL-2.0-or-later](LICENSE)
