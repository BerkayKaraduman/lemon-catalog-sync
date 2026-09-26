<?php
/**
 * Dynamic tag: Lemon Buy URL.
 *
 * @package LCS
 */

namespace LCS\Elementor\Dynamic_Tags;

use Elementor\Core\DynamicTags\Data_Tag;
use Elementor\Modules\DynamicTags\Module;
use LCS\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Official Lemon checkout URL (usable in any link field). In overlay mode add
 * the CSS class "lemonsqueezy-button" to the linking widget to open the overlay.
 */
class Buy_Url extends Data_Tag {

	use Product_Context;

	/**
	 * Name.
	 */
	public function get_name(): string {
		return 'lcs-buy-url';
	}

	/**
	 * Title.
	 */
	public function get_title(): string {
		return __( 'Lemon Buy URL', 'lemon-catalog-sync' );
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
		$url     = $product ? $product->buy_url() : '';

		if ( '' !== $url && Plugin::instance()->settings()->is_overlay_checkout() ) {
			$url = add_query_arg( 'embed', '1', $url );
			Plugin::instance()->renderer()->enqueue_lemonjs();
		}

		return esc_url( $url );
	}
}
