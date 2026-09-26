# Changelog

Bu proje [Semantic Versioning](https://semver.org/lang/tr/) kurallarını izler.

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
