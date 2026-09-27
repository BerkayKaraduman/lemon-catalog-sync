<?php
/**
 * Dynamic tag: LCS Card Badge.
 *
 * @package LCS
 */

namespace LCS\Elementor\Dynamic_Tags;

use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Card badge entered in WordPress, e.g. "Yeni".
 */
class Card_Badge extends Tag {

	use Product_Context;

	/**
	 * Name.
	 */
	public function get_name(): string {
		return 'lcs-card-badge';
	}

	/**
	 * Title.
	 */
	public function get_title(): string {
		return __( 'LCS Card Badge', 'lemon-catalog-sync' );
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
			echo esc_html( $product->card_badge() );
		}
	}
}
