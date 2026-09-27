# Changelog

This project follows [Semantic Versioning](https://semver.org/). Entries before 3.0.0 are kept in their original language.

## [3.0.0] - 2026-09-27

First public release since 1.0.0. It bundles the unreleased 1.1.0–1.3.0 work listed below.

### Added
- Product **Compare Price**, **Card Badge** and **Card Subtitle** per product (`_lcs_compare_price`, `_lcs_card_badge`, `_lcs_card_subtitle`), edited in the *Kart Görünümü* box and saved instantly with a dedicated AJAX button — independent of the WordPress post update.
- Elementor widgets **LCS Compare Price** (optional strikethrough, "only when higher than Lemon price"), **LCS Card Badge**, **LCS Card Subtitle**, **LCS Product Link**, plus matching dynamic tags and shortcodes.
- **Elementor Pro Loop Grid / Loop Item** support: every card resolves its own product through a central product context resolver; Theme Builder previews use the representative preview product.
- **Secure API key configuration** via `LCS_LEMON_API_KEY` in `wp-config.php`, managed by a single credential service (`LCS\Credentials`).

### Changed
- Display name is now **Lemon Catalog Sync for Elementor**; the GitHub repository is `lemon-catalog-sync-for-elementor`.
- The plugin folder (`lemon-catalog-sync`), text domain, `lcs_product` post type, taxonomy and `_lcs_*` meta keys are unchanged, so existing installations update in place.
- Compare price is formatted with the same currency symbol and separators as the Lemon price.

### Security
- The API key is never displayed, stored in the database while the constant is defined, sent to the browser, logged or included in error messages. The database fallback is encrypted and not autoloaded.
- WordPress-owned card fields are excluded from every write the Lemon sync performs.

## [1.3.0] - 2026-09-27 (unreleased)

### Security
- Tüm API key erişimi tek bir servis üzerinden: `LCS\Credentials` (`get_api_key()`, `has_constant_key()`, `store_key()`, `masked()`, `redact()`). Ham anahtarı döndüren tek metot `Credentials::get_api_key()`; `Authorization` başlığı yalnızca `Api_Client::request()` içinde, server-side `wp_remote_get()` için oluşturulur.
- Ana kaynak `wp-config.php` içindeki `LCS_LEMON_API_KEY`. Tanımlıyken: ayarlardaki API Key alanı gösterilmez, yerine "API key is configured securely via wp-config.php" mesajı çıkar; anahtarın hiçbir kısmı (maskeli son karakterler dahil) gösterilmez; gönderilen formdaki anahtar yok sayılır ve `wp_options`'a hiçbir şey yazılmaz.
- Veritabanı yedeği yalnızca constant tanımlı değilse kullanılır: libsodium ile şifreli, autoload kapalı (eski kurulumlarda bir kez zorlanır), input içinde asla geri gösterilmez, maskeli görünüm.
- wp-config.php'ye geçildikten sonra veritabanında kalan eski anahtar için "Remove the API key stored in the database" seçeneği.
- Kaydetmeden önce test edilen aday anahtar da dahil olmak üzere anahtar tüm hata mesajlarından `[redacted]` ile çıkarılır.
- Eklenti kaldırıldığında API anahtarı her durumda silinir (ürünler ve içerik yine korunur).

## [1.2.0] - 2026-09-27 (unreleased)

### Added
- Added reliable AJAX-based per-product card metadata storage: **Kart Görünümü** kutusunda **Kart Bilgilerini Kaydet** butonu (`admin-ajax.php`, action `lcs_save_card_data`). Gutenberg / REST post kaydından tamamen bağımsızdır; sayfayı güncellemek gerekmez. Nonce, oturum, `edit_post` yetkisi ve `lcs_product` post type kontrolleri; çift tıklamaya karşı koruma; "Kaydediliyor... / ✓ Kaydedildi / ✕ Kaydedilemedi" durumları.
- Added Card Subtitle persistence.
- İsteğe bağlı "Alandan çıkınca otomatik kaydet" seçeneği (varsayılan kapalı).
- Yöneticiler için salt okunur bilgi satırı (Product ID, kayıtlı eski fiyat / alt açıklama / rozet).
- Merkezi ürün çözümleyici `LCS\Product_Context::get_current_product_id()`: widget'lar, dynamic tag'ler ve shortcode'lar aynı mantıkla ürünü bulur.

### Fixed
- Fixed Compare Price persistence.
- Fixed Card Badge persistence.
- Improved Elementor Loop Item card metadata context.
- Normal "Güncelle" ile kayıt artık yalnızca kutu açıldığından beri değiştirilen alanları yazar; eski bir sekmedeki form yeni değerlerin üzerine yazamaz.
- Eski fiyat `50` veya `39.90` biçiminde saklanır; rozet ve alt açıklama `sanitize_text_field()` ile temizlenir ("%40" korunur).

### Changed
- Tek seferlik güvenli taşıma artık rozet (`_lcs_badge`, `card_badge`) ve alt açıklama (`_lcs_subtitle`, `card_subtitle`) anahtarlarını da kapsar.
- Elementor editör ipuçları: "… is empty for this preview product." (yalnızca editörde; canlı sitede hiçbir çıktı üretilmez).

## [1.1.1] - 2026-09-27 (unreleased)

### Fixed
- Fixed Compare Price persistence and REST save issue.
  - `_lcs_compare_price` artık yalnızca para birimi olmadan sayı olarak saklanır (`50`, `39.9`); metabox, REST, widget, dynamic tag ve shortcode aynı canonical anahtarı kullanır.
  - REST şeması string veya number kabul eder; sayı gönderen istemciler artık `rest_invalid_type` ile reddedilmez. Metabox kaydında nonce, yetki, autosave, revision ve post type kontrolleri; geçersiz girişte mevcut değer korunur.
  - Elementor editöründe LCS widget'ları ürünü Elementor Pro'nun dynamic tag bağlamında (Post Title'ın kullandığı temsili önizleme ürünü) çözer; önceden farklı bir ürün gösterilebiliyordu.
  - Eski fiyat, Lemon fiyatıyla aynı sembol ve ayraçlarla biçimlendirilir (`50` + `$15.00` → `$50.00`); "Only when higher" sayısal karşılaştırma yapar.
  - `_lcs_old_price`, `lcs_compare_price`, `compare_price` anahtarlarından, `_lcs_compare_price` boşsa tek seferlik güvenli taşıma (mevcut değerin üzerine yazılmaz, eski anahtarlar silinmez).

## [1.1.0] - 2026-09-27 (unreleased)

### Added
- WordPress tarafından yönetilen kart alanları: `_lcs_compare_price` (eski/liste fiyatı), `_lcs_card_subtitle` (kart alt açıklaması), `_lcs_card_badge` (rozet). Ürün düzenleme ekranında ayrı **Kart Görünümü** metabox'ı.
- Elementor widget'ları: **LCS Compare Price** (varsayılan olarak üstü çizili, çizgi rengi/kalınlığı ayarlanabilir), **LCS Card Subtitle**, **LCS Card Badge**, **LCS Product Link**.
- Dynamic tag'ler: LCS Compare Price, LCS Card Subtitle, LCS Card Badge (metin) ve LCS Product Link (URL).
- Elementor Pro Loop Grid / Loop Item: tüm LCS widget ve tag'leri mevcut loop item'ın `lcs_product` kaydından veri alır; Loop Item şablonu düzenlenirken şablonun önizleme ürünü kullanılır.
- Shortcode'lar: `[lcs_compare_price]`, `[lcs_card_subtitle]`, `[lcs_card_badge]`, `[lcs_product_link]`; `[lcs_products]` için `card_fields="yes|no"`.
- LCS Product Price widget'ına padding, LCS Product Image widget'ına margin/padding kontrolleri.

### Changed
- `[lcs_products]` kartları, dolu olan kart alanlarını (rozet, alt açıklama, eski fiyat) gösterir. Alanları boş ürünlerin çıktısı öncekiyle birebir aynıdır.

### Security
- Kart alanları `Meta::is_own()` dışında tutulur: senkronizasyon bu anahtarları hiçbir koşulda yazamaz veya silemez.

## [1.0.0] - 2026-09-26

İlk sürüm. Önceki WooCommerce tabanlı taslağın yerini alan, sıfırdan yazılmış mimari.

### Added
- `lcs_product` ("Dijital Ürünler") özel yazı türü ve `lcs_product_category` ("Ürün Kategorileri") taksonomisi; ayarlanabilir URL tabanları, yalnızca gerektiğinde rewrite flush.
- Lemon Squeezy JSON:API istemcisi (WordPress HTTP API): 401/403/404/429/5xx, geçersiz JSON ve bağlantı hataları; `Retry-After` desteği; hata mesajlarında API anahtarı gizleme.
- API anahtarı: `LCS_LEMON_API_KEY` sabiti desteği, autoload edilmeyen ve libsodium ile şifrelenen seçenek, maskeli gösterim.
- Stores API ile mağaza seçimi, `filter[store_id]` ile filtreli ürün çekme.
- Tüm sayfaları (`page[size]=100`) ve `include=variants` verisini işleyen senkronizasyon; Lemon Product ID ile duplicate'siz upsert; slug, içerik, Elementor, SEO ve taksonomi koruması.
- Eksik ürünleri silmeden taslağa alma (`_lcs_missing_from_lemon`), yalnızca eksiksiz ve hatasız çekimden sonra.
- Media Library görsel modu: tek seferlik içe aktarma, duplicate yok, elle seçilen öne çıkan görsel korunur.
- WP-Cron (15 dk / saatlik / günde iki / günlük), ortak `lcs_sync_lock` kilidi.
- Admin: Dashboard, Sync, Settings, Elementor Setup sayfaları; salt okunur "Lemon Squeezy Data" metabox'ı; ürün listesi sütunları.
- Elementor: düzenleme desteği, "Lemon Catalog" kategorisi, 6 widget, 8 dynamic tag.
- Shortcode'lar ve Elementor'suz siteler için otomatik ürün bloğu.
- Hosted Checkout ve Lemon.js Checkout Overlay.
