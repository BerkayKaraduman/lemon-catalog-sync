<?php
/**
 * Dynamic tag: Lemon Product ID.
 *
 * @package LCS
 */

namespace LCS\Elementor\Dynamic_Tags;

use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Lemon Product ID.
 */
class Product_Id extends Tag {

	use Product_Context;

	/**
	 * Name.
	 */
	public function get_name(): string {
		return 'lcs-product-id';
	}

	/**
	 * Title.
	 */
	public function get_title(): string {
		return __( 'Lemon Product ID', 'lemon-catalog-sync' );
	}

	/**
	 * Categories.
	 *
	 * @return string[]
	 */
	public function get_categories(): array {
		return array( Module::TEXT_CATEGORY, Module::NUMBER_CATEGORY );
	}

	/**
	 * Render.
	 */
	public function render(): void {
		$product = $this->product();
		if ( ! $product ) {
			return;
		}
		echo esc_html( $product->lemon_id() );
	}
}
