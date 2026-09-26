<?php
/**
 * Shared helpers for LCS dynamic tags.
 *
 * @package LCS
 */

namespace LCS\Elementor\Dynamic_Tags;

use LCS\Compat;
use LCS\Elementor\Integration;
use LCS\Product;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves the current product (with an editor-only preview fallback).
 */
trait Product_Context {

	/**
	 * Tag group.
	 */
	public function get_group(): string {
		return Integration::TAG_GROUP;
	}

	/**
	 * Current product.
	 */
	protected function product(): ?Product {
		$product = Product::current();
		if ( ! $product && Compat::is_elementor_editor() ) {
			$product = Product::preview_fallback();
		}
		return $product;
	}
}
