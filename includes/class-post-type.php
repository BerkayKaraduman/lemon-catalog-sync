<?php
/**
 * "Dijital Ürünler" custom post type and "Ürün Kategorileri" taxonomy.
 *
 * @package LCS
 */

namespace LCS;

defined( 'ABSPATH' ) || exit;

/**
 * Registers lcs_product / lcs_product_category and flushes rewrite rules only
 * when their bases (or the plugin version) change.
 */
final class Post_Type {

	public const POST_TYPE = 'lcs_product';
	public const TAXONOMY  = 'lcs_product_category';

	/**
	 * Option holding the signature of the last rewrite flush.
	 */
	public const REWRITE_SIGNATURE_OPTION = 'lcs_rewrite_signature';

	/**
	 * Settings.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Hooks.
	 */
	public function hooks(): void {
		add_action( 'init', array( $this, 'register' ), 5 );
		add_action( 'init', array( $this, 'maybe_flush_rewrite_rules' ), 99 );
	}

	/**
	 * Registers the post type and taxonomy.
	 */
	public function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'                  => __( 'Dijital Ürünler', 'lemon-catalog-sync' ),
					'singular_name'         => __( 'Dijital Ürün', 'lemon-catalog-sync' ),
					'menu_name'             => __( 'Dijital Ürünler', 'lemon-catalog-sync' ),
					'all_items'             => __( 'Tüm Dijital Ürünler', 'lemon-catalog-sync' ),
					'add_new'               => __( 'Yeni Ekle', 'lemon-catalog-sync' ),
					'add_new_item'          => __( 'Yeni Dijital Ürün Ekle', 'lemon-catalog-sync' ),
					'edit_item'             => __( 'Dijital Ürünü Düzenle', 'lemon-catalog-sync' ),
					'new_item'              => __( 'Yeni Dijital Ürün', 'lemon-catalog-sync' ),
					'view_item'             => __( 'Dijital Ürünü Görüntüle', 'lemon-catalog-sync' ),
					'view_items'            => __( 'Dijital Ürünleri Görüntüle', 'lemon-catalog-sync' ),
					'search_items'          => __( 'Dijital Ürün Ara', 'lemon-catalog-sync' ),
					'not_found'             => __( 'Dijital ürün bulunamadı.', 'lemon-catalog-sync' ),
					'not_found_in_trash'    => __( 'Çöp kutusunda dijital ürün yok.', 'lemon-catalog-sync' ),
					'archives'              => __( 'Dijital Ürün Arşivi', 'lemon-catalog-sync' ),
					'featured_image'        => __( 'Ürün Görseli', 'lemon-catalog-sync' ),
					'set_featured_image'    => __( 'Ürün görselini ayarla', 'lemon-catalog-sync' ),
					'remove_featured_image' => __( 'Ürün görselini kaldır', 'lemon-catalog-sync' ),
					'use_featured_image'    => __( 'Ürün görseli olarak kullan', 'lemon-catalog-sync' ),
				),
				'description'         => __( 'Digital products synchronized from Lemon Squeezy.', 'lemon-catalog-sync' ),
				'public'              => true,
				'publicly_queryable'  => true,
				'exclude_from_search' => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => true,
				'show_in_admin_bar'   => true,
				'show_in_rest'        => true,
				'rest_base'           => 'lcs-products',
				'menu_position'       => 57,
				'menu_icon'           => 'dashicons-cart',
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'has_archive'         => true,
				'query_var'           => true,
				'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
				'taxonomies'          => array( self::TAXONOMY ),
				'rewrite'             => array(
					'slug'       => $this->product_base(),
					'with_front' => false,
					'feeds'      => true,
					'pages'      => true,
				),
			)
		);

		register_taxonomy(
			self::TAXONOMY,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Ürün Kategorileri', 'lemon-catalog-sync' ),
					'singular_name' => __( 'Ürün Kategorisi', 'lemon-catalog-sync' ),
					'menu_name'     => __( 'Ürün Kategorileri', 'lemon-catalog-sync' ),
					'all_items'     => __( 'Tüm Kategoriler', 'lemon-catalog-sync' ),
					'edit_item'     => __( 'Kategoriyi Düzenle', 'lemon-catalog-sync' ),
					'view_item'     => __( 'Kategoriyi Görüntüle', 'lemon-catalog-sync' ),
					'update_item'   => __( 'Kategoriyi Güncelle', 'lemon-catalog-sync' ),
					'add_new_item'  => __( 'Yeni Kategori Ekle', 'lemon-catalog-sync' ),
					'new_item_name' => __( 'Yeni Kategori Adı', 'lemon-catalog-sync' ),
					'parent_item'   => __( 'Üst Kategori', 'lemon-catalog-sync' ),
					'search_items'  => __( 'Kategori Ara', 'lemon-catalog-sync' ),
					'not_found'     => __( 'Kategori bulunamadı.', 'lemon-catalog-sync' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'show_in_nav_menus' => true,
				'query_var'         => true,
				'rewrite'           => array(
					'slug'         => $this->category_base(),
					'with_front'   => false,
					'hierarchical' => true,
				),
			)
		);
	}

	/**
	 * Flushes rewrite rules only when the permalink signature changed.
	 * Never runs flush_rewrite_rules() on ordinary requests.
	 */
	public function maybe_flush_rewrite_rules(): void {
		$signature = $this->rewrite_signature();
		if ( get_option( self::REWRITE_SIGNATURE_OPTION ) === $signature ) {
			return;
		}

		flush_rewrite_rules( false );
		update_option( self::REWRITE_SIGNATURE_OPTION, $signature, true );
	}

	/**
	 * Signature of everything that affects our rewrite rules.
	 */
	public function rewrite_signature(): string {
		return LCS_VERSION . '|' . $this->product_base() . '|' . $this->category_base();
	}

	/**
	 * Product URL base.
	 */
	public function product_base(): string {
		$base = sanitize_title( (string) $this->settings->get( 'product_base' ) );
		return '' !== $base ? $base : 'urun';
	}

	/**
	 * Category URL base.
	 */
	public function category_base(): string {
		$base = sanitize_title( (string) $this->settings->get( 'category_base' ) );
		return '' !== $base ? $base : 'urun-kategori';
	}
}
