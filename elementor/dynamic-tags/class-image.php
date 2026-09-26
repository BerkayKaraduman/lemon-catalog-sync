<?php
/**
 * Dynamic tag: Lemon Image (for Image widgets / backgrounds).
 *
 * @package LCS
 */

namespace LCS\Elementor\Dynamic_Tags;

use Elementor\Core\DynamicTags\Data_Tag;
use Elementor\Modules\DynamicTags\Module;
use LCS\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Image value: attachment in Media Library mode, remote URL otherwise.
 */
class Image extends Data_Tag {

	use Product_Context;

	/**
	 * Name.
	 */
	public function get_name(): string {
		return 'lcs-image';
	}

	/**
	 * Title.
	 */
	public function get_title(): string {
		return __( 'Lemon Image', 'lemon-catalog-sync' );
	}

	/**
	 * Categories.
	 *
	 * @return string[]
	 */
	public function get_categories(): array {
		return array( Module::IMAGE_CATEGORY );
	}

	/**
	 * Value.
	 *
	 * @param array<string,mixed> $options Options.
	 * @return array{id:int|string,url:string}
	 */
	protected function get_value( array $options = array() ): array {
		$product = $this->product();
		if ( ! $product ) {
			return array(
				'id'  => '',
				'url' => '',
			);
		}

		$media = Plugin::instance()->settings()->is_media_image_mode();

		return array(
			'id'  => $media && $product->thumbnail_id() ? $product->thumbnail_id() : '',
			'url' => $product->image_url( $media, 'full' ),
		);
	}
}
