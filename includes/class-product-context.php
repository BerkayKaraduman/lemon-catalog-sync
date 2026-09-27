<?php
/**
 * Central "which product is this?" resolver.
 *
 * @package LCS
 */

namespace LCS;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves the current lcs_product for widgets, dynamic tags and shortcodes.
 *
 * Order:
 * 1. Explicit ID (shortcode id="").
 * 2. `lcs_current_product_id` filter.
 * 3. The current loop post — single product pages, Elementor Single templates
 *    and every Elementor Pro Loop Grid / Loop Item card (Pro runs the_post()
 *    for each item, so each card resolves its own product).
 * 4. The queried object.
 * 5. Elementor editor only: the template's representative preview product.
 */
final class Product_Context {

	/**
	 * Current product ID, or 0 when there is no lcs_product in context.
	 *
	 * @param int $explicit_id Explicit post ID (0 for automatic).
	 */
	public static function get_current_product_id( int $explicit_id = 0 ): int {
		$product = self::get_current_product( $explicit_id );
		return $product ? $product->id() : 0;
	}

	/**
	 * Current product, or null.
	 *
	 * @param int $explicit_id Explicit post ID (0 for automatic).
	 */
	public static function get_current_product( int $explicit_id = 0 ): ?Product {
		$product = Product::current( $explicit_id );

		if ( ! $product && $explicit_id <= 0 && Compat::is_elementor_editor() ) {
			$product = Product::preview_fallback();
		}

		return ( $product && Post_Type::POST_TYPE === get_post_type( $product->id() ) ) ? $product : null;
	}
}
