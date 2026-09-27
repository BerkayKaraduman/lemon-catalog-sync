<?php
/**
 * Elementor integration. Every hook here only fires when Elementor is active,
 * and no Elementor class is referenced before that — so no fatal without it.
 *
 * @package LCS
 */

namespace LCS\Elementor;

use LCS\Compat;
use LCS\Post_Type;

defined( 'ABSPATH' ) || exit;

/**
 * Registers editing support, the "Lemon Catalog" category, widgets and dynamic tags.
 */
final class Integration {

	public const CATEGORY  = 'lemon-catalog';
	public const TAG_GROUP = 'lemon-catalog';

	/**
	 * Option flag: our post type was merged into elementor_cpt_support once.
	 */
	private const CPT_MERGED_OPTION = 'lcs_elementor_cpt_merged';

	/**
	 * Hooks.
	 */
	public function hooks(): void {
		add_action( 'init', array( $this, 'add_post_type_support' ), 6 );
		add_action( 'admin_init', array( $this, 'merge_cpt_support_option' ) );
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/dynamic_tags/register', array( $this, 'register_dynamic_tags' ) );
	}

	/**
	 * Enables "Edit with Elementor" for lcs_product (runs after CPT registration at
	 * priority 5). Elementor itself adds support for the types listed in its
	 * elementor_cpt_support option; this covers the first requests before the
	 * option is merged, and afterwards follows the option so the owner stays in control.
	 */
	public function add_post_type_support(): void {
		if ( ! Compat::elementor_loaded() ) {
			return;
		}

		$enabled = get_option( 'elementor_cpt_support', array( 'page', 'post' ) );
		if ( ! get_option( self::CPT_MERGED_OPTION ) || ( is_array( $enabled ) && in_array( Post_Type::POST_TYPE, $enabled, true ) ) ) {
			add_post_type_support( Post_Type::POST_TYPE, 'elementor' );
		}
	}

	/**
	 * Adds lcs_product to Elementor's "Post Types" setting once, merging into
	 * the existing list — existing values are never overwritten. If the site
	 * owner removes it later, we do not add it back.
	 */
	public function merge_cpt_support_option(): void {
		if ( ! Compat::elementor_loaded() || get_option( self::CPT_MERGED_OPTION ) ) {
			return;
		}

		$current = get_option( 'elementor_cpt_support', array( 'page', 'post' ) );
		$current = is_array( $current ) ? $current : array( 'page', 'post' );

		if ( ! in_array( Post_Type::POST_TYPE, $current, true ) ) {
			$current[] = Post_Type::POST_TYPE;
			update_option( 'elementor_cpt_support', array_values( array_unique( $current ) ) );
		}

		update_option( self::CPT_MERGED_OPTION, 1, false );
	}

	/**
	 * Widget category.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Manager.
	 */
	public function register_category( $elements_manager ): void {
		$elements_manager->add_category(
			self::CATEGORY,
			array(
				'title' => __( 'Lemon Catalog', 'lemon-catalog-sync' ),
				'icon'  => 'eicon-cart',
			)
		);
	}

	/**
	 * Widgets (Elementor ≥ 3.5 API).
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Manager.
	 */
	public function register_widgets( $widgets_manager ): void {
		if ( ! Compat::elementor_supported() ) {
			return;
		}

		$widgets = array(
			Widgets\Product_Image::class,
			Widgets\Product_Price::class,
			Widgets\Lemon_Description::class,
			Widgets\Buy_Button::class,
			Widgets\Variant_Selector::class,
			Widgets\Product_Meta::class,
			Widgets\Compare_Price::class,
			Widgets\Card_Subtitle::class,
			Widgets\Card_Badge::class,
			Widgets\Product_Link::class,
		);

		foreach ( $widgets as $widget ) {
			$widgets_manager->register( new $widget() );
		}
	}

	/**
	 * Dynamic tags. Failure here must never break the site: widgets and
	 * shortcodes stay the primary integration.
	 *
	 * @param \Elementor\Core\DynamicTags\Manager $manager Manager.
	 */
	public function register_dynamic_tags( $manager ): void {
		if ( ! Compat::elementor_supported()
			|| ! class_exists( '\Elementor\Core\DynamicTags\Tag' )
			|| ! class_exists( '\Elementor\Core\DynamicTags\Data_Tag' )
			|| ! class_exists( '\Elementor\Modules\DynamicTags\Module' ) ) {
			return;
		}

		try {
			$manager->register_group( self::TAG_GROUP, array( 'title' => __( 'Lemon Catalog', 'lemon-catalog-sync' ) ) );

			$tags = array(
				Dynamic_Tags\Product_Id::class,
				Dynamic_Tags\Price::class,
				Dynamic_Tags\Formatted_Price::class,
				Dynamic_Tags\Description::class,
				Dynamic_Tags\Buy_Url::class,
				Dynamic_Tags\Image_Url::class,
				Dynamic_Tags\Image::class,
				Dynamic_Tags\Product_Status::class,
				Dynamic_Tags\Compare_Price::class,
				Dynamic_Tags\Card_Subtitle::class,
				Dynamic_Tags\Card_Badge::class,
				Dynamic_Tags\Product_Url::class,
			);

			foreach ( $tags as $tag ) {
				$manager->register( new $tag() );
			}
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- debug only, contains no secrets.
				error_log( 'Lemon Catalog Sync for Elementor: dynamic tags disabled — ' . $e->getMessage() );
			}
		}
	}
}
