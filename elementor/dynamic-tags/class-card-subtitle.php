<?php
/**
 * Dynamic tag: LCS Card Subtitle.
 *
 * @package LCS
 */

namespace LCS\Elementor\Dynamic_Tags;

use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Card subtitle entered in WordPress.
 */
class Card_Subtitle extends Tag {

	use Product_Context;

	/**
	 * Name.
	 */
	public function get_name(): string {
		return 'lcs-card-subtitle';
	}

	/**
	 * Title.
	 */
	public function get_title(): string {
		return __( 'LCS Card Subtitle', 'lemon-catalog-sync' );
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
			echo esc_html( $product->card_subtitle() );
		}
	}
}
