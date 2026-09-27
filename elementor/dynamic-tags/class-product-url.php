<?php
/**
 * Dynamic tag: LCS Product Link.
 *
 * @package LCS
 */

namespace LCS\Elementor\Dynamic_Tags;

use Elementor\Core\DynamicTags\Data_Tag;
use Elementor\Modules\DynamicTags\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Permalink of the current product — e.g. to make a whole Loop Item card clickable.
 */
class Product_Url extends Data_Tag {

	use Product_Context;

	/**
	 * Name.
	 */
	public function get_name(): string {
		return 'lcs-product-url';
	}

	/**
	 * Title.
	 */
	public function get_title(): string {
		return __( 'LCS Product Link', 'lemon-catalog-sync' );
	}

	/**
	 * Categories.
	 *
	 * @return string[]
	 */
	public function get_categories(): array {
		return array( Module::URL_CATEGORY );
	}

	/**
	 * Value.
	 *
	 * @param array<string,mixed> $options Options.
	 */
	protected function get_value( array $options = array() ): string {
		$product = $this->product();
		return $product ? esc_url( $product->permalink() ) : '';
	}
}
