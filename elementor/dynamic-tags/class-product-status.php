<?php
/**
 * Dynamic tag: Lemon Product Status.
 *
 * @package LCS
 */

namespace LCS\Elementor\Dynamic_Tags;

use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Lemon Product Status.
 */
class Product_Status extends Tag {

	use Product_Context;

	/**
	 * Name.
	 */
	public function get_name(): string {
		return 'lcs-product-status';
	}

	/**
	 * Title.
	 */
	public function get_title(): string {
		return __( 'Lemon Product Status', 'lemon-catalog-sync' );
	}

	/**
	 * Categories.
	 *
	 * @return string[]
	 */
	public function get_categories(): array {
		return array( Module::TEXT_CATEGORY );
	}

	/**
	 * Render.
	 */
	public function render(): void {
		$product = $this->product();
		if ( ! $product ) {
			return;
		}
		echo esc_html( $product->lemon_status_formatted() );
	}
}
