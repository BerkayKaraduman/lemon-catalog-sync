<?php
/**
 * Dynamic tag: LCS Compare Price.
 *
 * @package LCS
 */

namespace LCS\Elementor\Dynamic_Tags;

use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module;
use LCS\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Old / list price entered in WordPress (empty when not set). For the
 * automatic strikethrough use the "LCS Compare Price" widget.
 */
class Compare_Price extends Tag {

	use Product_Context;

	/**
	 * Name.
	 */
	public function get_name(): string {
		return 'lcs-compare-price';
	}

	/**
	 * Title.
	 */
	public function get_title(): string {
		return __( 'LCS Compare Price', 'lemon-catalog-sync' );
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
		if ( $product ) {
			echo esc_html( Plugin::instance()->renderer()->compare_price_text( $product ) );
		}
	}
}
