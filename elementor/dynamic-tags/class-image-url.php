<?php
/**
 * Dynamic tag: Lemon Image URL.
 *
 * @package LCS
 */

namespace LCS\Elementor\Dynamic_Tags;

use Elementor\Core\DynamicTags\Data_Tag;
use Elementor\Modules\DynamicTags\Module;
use LCS\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Product image URL (respects the Image Sync Mode).
 */
class Image_Url extends Data_Tag {

	use Product_Context;

	/**
	 * Name.
	 */
	public function get_name(): string {
		return 'lcs-image-url';
	}

	/**
	 * Title.
	 */
	public function get_title(): string {
		return __( 'Lemon Image URL', 'lemon-catalog-sync' );
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
		return $product ? esc_url( $product->image_url( Plugin::instance()->settings()->is_media_image_mode() ) ) : '';
	}
}
