<?php
/**
 * Dynamic tag: Lemon Price.
 *
 * @package LCS
 */

namespace LCS\Elementor\Dynamic_Tags;

use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Lemon Price.
 */
class Price extends Tag {

	use Product_Context;

	/**
	 * Name.
	 */
	public function get_name(): string {
		return 'lcs-price';
	}

	/**
	 * Title.
	 */
	public function get_title(): string {
		return __( 'Lemon Price', 'lemon-catalog-sync' );
	}

	/**
	 * Categories.
	 *
	 * @return string[]
	 */
	public function get_categories(): array {
		return array( Module::NUMBER_CATEGORY, Module::TEXT_CATEGORY );
	}

	/**
	 * Render.
	 */
	public function render(): void {
		$product = $this->product();
		if ( ! $product ) {
			return;
		}
		$amount = $product->price_amount();
		if ( null !== $amount ) {
			echo esc_html( number_format( $amount, 2, '.', '' ) );
		}
	}
}
