<?php
/**
 * Dynamic tag: Lemon Formatted Price.
 *
 * @package LCS
 */

namespace LCS\Elementor\Dynamic_Tags;

use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Lemon Formatted Price.
 */
class Formatted_Price extends Tag {

	use Product_Context;

	/**
	 * Name.
	 */
	public function get_name(): string {
		return 'lcs-formatted-price';
	}

	/**
	 * Title.
	 */
	public function get_title(): string {
		return __( 'Lemon Formatted Price', 'lemon-catalog-sync' );
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
		echo esc_html( \LCS\Plugin::instance()->renderer()->price_text( $product ) );
	}
}
