# Lemon Catalog Sync for Elementor

Lemon Squeezy ürün kataloğunu WordPress'e senkronize eden, Elementor ile tam uyumlu bir WordPress eklentisi. **WooCommerce gerektirmez.**

| Katman | Sorumluluk |
| --- | --- |
| **Lemon Squeezy** | Ürün verisinin tek kaynağı: ad, fiyat, açıklama, görsel, varyantlar, satın alma URL'si, durum |
| **WordPress** | Ürün sayfaları, URL'ler, kategoriler, SEO |
| **Elementor** | Ürün sayfası tasarımı ve her ürüne özel içerik |

Her Lemon Squeezy ürünü WordPress'te ayrı bir **Dijital Ürün** (`lcs_product`) kaydı ve ayrı bir URL olur:

```
Lemon Squeezy: "Lightroom Preset Pack"  →  https://site.com/urun/lightroom-preset-pack/
```

Senkronizasyon yalnızca Lemon'a ait alanları günceller. Elementor tasarımınız, `post_content`, URL slug'ı, kategoriler, Yoast / Rank Math verileri ve özel alanlarınız **hiçbir senkronizasyonda değiştirilmez.**

---

## Requirements / Gereksinimler

- WordPress 6.0+ (WordPress 7.1 ile test edildi)
- PHP 8.1+
- Bir Lemon Squeezy hesabı ve API anahtarı
- İsteğe bağlı: Elementor 3.5+ (Elementor 4.3 ile test edildi), Elementor Pro (Theme Builder için)
- Composer, npm veya build adımı **gerekmez**; paylaşımlı hostingde çalışır.

## Installation / Kurulum

1. `lemon-catalog-sync.zip` dosyasını **Eklentiler → Yeni Ekle → Eklenti Yükle** ile yükleyin (veya klasörü `wp-content/plugins/` içine kopyalayın).
2. Eklentiyi etkinleştirin. Etkinleştirmede özel yazı türü ve taksonomi kaydedilir, rewrite kuralları bir kez yenilenir.
3. Menüde **Lemon Catalog Sync** (Dashboard, Sync, Settings, Elementor Setup) ve **Dijital Ürünler** görünür.

Kaynak koddan zip üretmek için: `bash bin/build-zip.sh` → `build/lemon-catalog-sync.zip`.

## API Key Setup / API Anahtarı

Lemon Squeezy → **Settings → API** bölümünden bir anahtar oluşturun.

**Önerilen yöntem — `wp-config.php`:**

```php
define( 'LCS_LEMON_API_KEY', 'eyJ0eXAiOiJKV1Qi...' );
```

Bu sabit tanımlıysa veritabanındaki anahtar yok sayılır.

**Alternatif — Settings sayfası:** Anahtarı **Lemon Catalog Sync → Settings → API Key** alanına yapıştırın. Kaydetmeden önce anahtar Lemon API'ye karşı doğrulanır. Kaydedilen anahtar:

- `autoload` edilmeyen ayrı bir seçenekte (`lcs_api_key`) saklanır,
- sunucuda libsodium varsa sitenizin salt değerlerinden türetilen bir anahtarla şifrelenir,
- HTML'de asla tam haliyle gösterilmez (yalnızca `••••••••abcd`); input alanı her zaman boş gelir, boş bırakıp kaydetmek mevcut anahtarı korur.

## Store Connection / Mağaza Bağlantısı

1. **Test Connection** butonu `GET /v1/users/me` ile anahtarı doğrular ve `GET /v1/stores` ile erişilebilen mağazaları yükler.
2. Tek mağaza varsa otomatik seçilir; birden fazlaysa **Store** açılır listesinden seçin.
3. Tüm ürün istekleri `filter[store_id]` ile yalnızca bu mağazaya filtrelenir.

## First Sync / İlk Senkronizasyon

**Lemon Catalog Sync → Sync → SYNC NOW**

Senkronizasyon şu isteği tüm sayfalar bitene kadar tekrarlar:

```
GET https://api.lemonsqueezy.com/v1/products
    ?filter[store_id]=STORE_ID&include=variants&page[size]=100&page[number]=N
```

- **Yeni ürün:** `lcs_product` oluşturulur. Başlık = Lemon adı, slug = Lemon slug'ı, Lemon `published` → `publish`, `draft` → `draft`. `post_content` boş bırakılır; Lemon açıklaması `_lcs_lemon_description` meta alanına yazılır.
- **Mevcut ürün:** Lemon Product ID ile eşleşen **aynı** kayıt güncellenir (duplicate oluşmaz). Yalnızca `post_title`, yayın durumu ve `_lcs_*` meta alanları değişir.
- **Değişmemiş ürün:** Lemon `updated_at` ve içerik parmak izi aynıysa meta yazımı atlanır.
- **Lemon'dan kaldırılmış ürün:** Tüm sayfalar hatasız çekildiyse ürün **silinmez**; `draft` yapılır ve `_lcs_missing_from_lemon = 1` işaretlenir. Ürün geri gelirse yeniden yayınlanır ve işaret kaldırılır.

Sync sayfası; bağlantı durumu, seçili mağaza, ortam (Test/Live), son deneme, son başarılı senkronizasyon, Created / Updated / Drafted / Unchanged / Failed sayıları, kilit durumu ve son hatayı gösterir.

### Veri sahipliği

| Lemon Squeezy yönetir (sync yazar) | WordPress yönetir (sync asla dokunmaz) |
| --- | --- |
| `_lcs_product_id`, `_lcs_store_id`, `_lcs_lemon_description`, `_lcs_price`, `_lcs_price_formatted`, `_lcs_from_price(_formatted)`, `_lcs_to_price(_formatted)`, `_lcs_pay_what_you_want`, `_lcs_buy_now_url`, `_lcs_thumb_url`, `_lcs_large_thumb_url`, `_lcs_lemon_status(_formatted)`, `_lcs_lemon_created_at`, `_lcs_lemon_updated_at`, `_lcs_test_mode`, `_lcs_variants`, `_lcs_last_sync`, `_lcs_missing_from_lemon` | URL slug'ı (`post_name`), `post_content`, `post_excerpt`, `menu_order`, sayfa şablonu, kategoriler, `_elementor_*`, `_wpseo_*`, `rank_math_*`, kendi özel alanlarınız, sizin seçtiğiniz öne çıkan görsel |
| `post_title`, yayın durumu (`publish` ↔ `draft`) | Çöp kutusu / özel / beklemede / zamanlanmış durumlar |

Bu kural kodda da zorunludur: sync'in meta yazan tek fonksiyonu `_lcs_` önekli olmayan her anahtarı reddeder. Başlık ve durum, `post_content`'i yeniden kaydetmeyen (dolayısıyla kses/içerik filtrelerinden geçirmeyen) sütun bazlı bir güncelleme ile yazılır.

## Automatic Sync / Otomatik Senkronizasyon

**Settings → Automatic Sync Frequency:** Disabled, Every 15 Minutes, Hourly, Twice Daily, Daily.

- WP-Cron ile çalışır; manuel ve otomatik senkronizasyon **aynı** servis sınıfını kullanır.
- Her zaman tek bir zamanlanmış olay vardır (`wp_next_scheduled` kontrolü).
- Manuel ve cron aynı anda çalışamaz: `lcs_sync_lock` kilidi 15 dakikada kendiliğinden düşer, bu yüzden bir hata sonrası kalıcı kilit oluşmaz.
- Lemon API `429` döndürürse `Retry-After` süresi kaydedilir ve cron bu süre dolana kadar istek atmaz (istek uyutulmaz/bloklanmaz).
- Az trafikli sitelerde gerçek bir sunucu cron'u ile `wp-cron.php`'yi çağırmanız önerilir.

## Product URLs / Ürün URL'leri

| Ayar | Varsayılan | Örnek |
| --- | --- | --- |
| Product URL Base | `urun` | `site.com/urun/lightroom-preset-pack/`, arşiv: `site.com/urun/` |
| Product Category URL Base | `urun-kategori` | `site.com/urun-kategori/preset/` |

- Taban değişince rewrite kuralları yalnızca **bir kez** yenilenir; `flush_rewrite_rules()` normal isteklerde çağrılmaz.
- **Slug koruması:** Lemon slug'ı yalnızca ürün ilk oluşturulurken kullanılır. Lemon'da ad veya slug sonradan değişse de WordPress URL'si aynı kalır. Slug'ı WordPress panelinden istediğiniz gibi değiştirebilirsiniz; sync buna dokunmaz.
- Kategoriler (**Dijital Ürünler → Ürün Kategorileri**) tamamen size aittir: Preset, Template, Video, LUT, E-Book, Motion Graphics… Sync kategori atamalarını asla değiştirmez.

## Elementor Setup

Elementor aktifse eklenti otomatik olarak:

- `lcs_product` için **Edit with Elementor** desteğini açar (Elementor'ün *Post Types* ayarına mevcut değerleri koruyarak ekler),
- **Lemon Catalog** widget kategorisini ve şu widget'ları kaydeder: **LCS Product Image**, **LCS Product Price**, **LCS Lemon Description**, **LCS Buy Button**, **LCS Variant Selector**, **LCS Product Meta**,
- Dynamic Tag'leri kaydeder (Lemon Catalog grubu): Lemon Product ID, Lemon Price, Lemon Formatted Price, Lemon Description, Lemon Buy URL, Lemon Image URL, Lemon Image, Lemon Product Status.

Widget'lar ürün ID'si istemez; bulundukları sayfadaki mevcut ürünü kullanır. Elementor editöründe tasarlanan şablonlarda gerçek veri görmeniz için en son ürün önizleme olarak kullanılır.

Elementor yoksa eklenti hatasız çalışır (shortcode'lar ve otomatik ürün bloğu ile). Durum için: **Lemon Catalog Sync → Elementor Setup**.

## Theme Builder Setup (Elementor Pro)

1. Elementor → **Theme Builder**
2. **Single** şablon oluşturun
3. Display Condition → **Dijital Ürünler → All**
4. **LCS Product Image** ekleyin
5. **Post Title** ekleyin
6. **LCS Product Price** ekleyin
7. **LCS Buy Button** ekleyin
8. **Post Content** widget'ını ekleyin ← **zorunlu**

> **Post Content widget'ı olmadan ürüne özel içerik görünmez.** Her ürünün *Edit with Elementor* ile oluşturulan içeriği, global şablonda Post Content widget'ının bulunduğu yerde gösterilir.

Örnek global şablon:

```
LCS Product Image | Post Title | LCS Product Price | LCS Variant Selector | LCS Buy Button | LCS Lemon Description
──────────────────────────────────────────────
POST CONTENT   ← ürüne özel Elementor içeriği
──────────────────────────────────────────────
Global FAQ | Related Products
```

Arşiv için: Theme Builder → **Archive** → Display Condition → Dijital Ürünler Archive (ve/veya Ürün Kategorileri).

Settings → **Frontend Display Mode = Elementor Theme Builder** seçin (Elementor Pro varken etkinleştirmede otomatik seçilir).

## Per Product Elementor Editing / Ürüne Özel Elementor İçeriği

**Dijital Ürünler → (ürün) → Edit with Elementor** ile her ürüne tamamen farklı içerik ekleyin:

- *Lightroom Preset Pack:* Before/After Slider, Preset Gallery, Installation Tutorial, FAQ
- *Premiere Transition Pack:* Demo Video, Transition Showcase, System Requirements
- *Social Media Templates:* Template Gallery, Canva Demo, Instagram Examples

Header, fiyat, görsel ve satın alma butonu global şablondan gelir; bu içerik Post Content alanında gösterilir. Lemon'da fiyat $24 → $29 olduğunda sonraki senkronizasyonda **yalnızca fiyat** değişir; tasarım, URL, kategori, SEO ve özel içerik aynı kalır.

## Checkout Modes / Ödeme Modları

| Mod | Davranış |
| --- | --- |
| **Hosted Checkout** | Buton doğrudan ürünün Lemon `buy_now_url` adresine gider. İsteğe bağlı yeni sekme. |
| **Checkout Overlay** | Butona `lemonsqueezy-button` sınıfı eklenir ve resmi `https://app.lemonsqueezy.com/js/lemon.js` yüklenir. Lemon.js self-host edilmez ve yalnızca satın alma butonu içeren sayfalarda yüklenir. |

Ödeme her zaman ürün seviyesindeki resmi `buy_now_url` ile yapılır. Varyant seçici bilgilendirme amaçlıdır; API'de belgelenmemiş varyant checkout URL'leri üretilmez.

**Frontend Display Mode = Automatic Product Block** (Elementor Pro olmayan siteler için): Tekil ürün sayfasında içerikten önce görsel, başlık, fiyat, Lemon açıklaması, varyantlar ve satın alma butonu otomatik eklenir, ardından WordPress/Elementor içeriği gelir. Blok yalnızca ana sorgudaki tekil ürün içeriğinde, bir kez çalışır; admin, REST, feed ve Elementor editöründe devre dışıdır.

## Shortcodes

Tümü Elementor olmadan da çalışır ve mevcut ürünü otomatik kullanır (isteğe bağlı `id="POST_ID"`).

| Shortcode | Seçenekler |
| --- | --- |
| `[lcs_product_price]` | `mode="auto\|single\|from\|range"`, `show_from="yes\|no"`, `prefix`, `suffix` |
| `[lcs_product_image]` | `size="large"` |
| `[lcs_lemon_description]` | — |
| `[lcs_product_buy_button]` | `text="Satın Al"`, `size="sm\|md\|lg"`, `full_width`, `new_tab`, `class` |
| `[lcs_product_variants]` | `layout="list\|grid"`, `show_price`, `show_description` |
| `[lcs_product_meta]` | `items="lemon_id,price,variants,status,environment,updated"` |
| `[lcs_products]` | `category`, `columns` (1–6), `limit` (1–100), `orderby="date\|title\|modified\|menu_order\|rand\|price"`, `order="ASC\|DESC"` |

```
[lcs_products columns="3" limit="12"]
[lcs_products category="preset" columns="4"]
```

## Troubleshooting / Sorun Giderme

| Belirti | Çözüm |
| --- | --- |
| Ürün sayfası 404 | **Ayarlar → Kalıcı Bağlantılar** sayfasını açıp kaydedin. URL tabanının bir sayfa slug'ıyla çakışmadığından emin olun. |
| "401 Unauthorized" | Anahtar yanlış veya iptal edilmiş. Yeni anahtar oluşturun. |
| "rate limit (429)" | Lemon'un belirttiği süre sonunda otomatik sync kendiliğinden devam eder. |
| "Another sync is already running" | Önceki çalışma bitene kadar bekleyin; kilit en geç 15 dakikada düşer. |
| Ürünler taslağa alındı | Sync sayfasındaki *Missing from Lemon* sayısına ve ürün listesindeki etikete bakın; ürün Lemon mağazasında yayında mı ve doğru mağaza mı seçili? |
| Ürüne özel Elementor içeriği görünmüyor | Single şablonunda **Post Content** widget'ı olmalı. |
| Widget'lar görünmüyor | Elementor 3.5+ gerekir; Elementor Setup sayfasını kontrol edin. |
| Otomatik sync çalışmıyor | WP-Cron ziyaretle tetiklenir; `DISABLE_WP_CRON` açıksa sunucu cron'u kurun. |
| Media Library modunda görsel yok | Görsel indirilemezse uzak Lemon görseli kullanılır; kendi seçtiğiniz öne çıkan görsel asla değiştirilmez. |

Geliştirici kancaları: `lcs_loaded`, `lcs_before_sync`, `lcs_after_sync`, `lcs_product_created`, `lcs_product_updated`, `lcs_product_marked_missing`, `lcs_enable_missing_detection`, `lcs_current_product_id`, `lcs_render_auto_block`, `lcs_product_summary_html`, `lcs_products_query_args`, `lcs_format_money`.

## Security / Güvenlik

- API anahtarı frontend HTML'inde, JavaScript'te, REST yanıtlarında, hata mesajlarında ve loglarda yer almaz; API hata mesajları anahtara karşı temizlenir.
- `wp-config.php` sabiti desteklenir; veritabanı seçeneği autoload edilmez ve mümkünse şifrelenir.
- Tüm admin işlemleri `manage_options` yetkisi ve nonce ile korunur.
- Girdiler `sanitize_text_field`, `sanitize_key`, `sanitize_title`, `absint`, `esc_url_raw` ile; çıktılar `esc_html`, `esc_attr`, `esc_url`, `wp_kses_post` ile işlenir. Lemon açıklaması `wp_kses_post` ile filtrelenir.
- HTTP istekleri yalnızca WordPress HTTP API (`wp_remote_get`) ile yapılır.
- Devre dışı bırakma yalnızca cron ve kilidi temizler. Kaldırma varsayılan olarak **tüm verileri korur**; *Delete plugin settings on uninstall* işaretliyse yalnızca eklenti ayarları silinir. Ürünler, kategoriler, Elementor içerikleri, SEO verileri ve medya asla silinmez.

## Mimari

```
lemon-catalog-sync/
├── lemon-catalog-sync.php          Başlık, sabitler, autoloader, aktivasyon kancaları
├── uninstall.php                   Varsayılan: veriyi koru
├── includes/
│   ├── class-plugin.php            Servis konteyneri, lcs_loaded
│   ├── class-settings.php          Ayarlar + şifreli, autoload edilmeyen API anahtarı
│   ├── class-api-client.php        JSON:API istemcisi, hata/429 yönetimi, anahtar gizleme
│   ├── class-store-service.php     Stores API
│   ├── class-post-type.php         lcs_product + lcs_product_category, akıllı rewrite flush
│   ├── class-product-mapper.php    Lemon → WordPress eşleme (null güvenli)
│   ├── class-product-repository.php Tek yazma noktası; yalnızca _lcs_* meta
│   ├── class-product-sync.php      Sayfalama, upsert, eksik ürün tespiti
│   ├── class-sync-lock.php / class-sync-state.php / class-cron.php
│   ├── class-image-manager.php     Media Library içe aktarma (duplicate yok)
│   ├── class-product.php           Okuma modeli (widget/shortcode/tag ortak)
│   ├── class-renderer.php          Ortak HTML bileşenleri
│   ├── class-shortcodes.php / class-frontend.php / class-compat.php / class-activator.php
├── admin/        Menü, sayfalar, admin-post işleyicileri, metabox, views/css/js
├── elementor/    Integration, widgets/, dynamic-tags/
└── public/css/   Frontend stilleri
```

Gelecekteki üyelik modülü (Lemon `order_created` → WordPress kullanıcısı → satın alınan ürünler) `lcs_loaded` kancasıyla `Plugin::instance()->api()`, `settings()` ve Lemon ID → ürün eşlemesini yeniden kullanabilir; bu sürüm yalnızca ürün kataloğu senkronizasyonu yapar.

## Lisans

GPL-2.0-or-later
