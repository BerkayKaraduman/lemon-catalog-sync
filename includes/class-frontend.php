<?php
/**
 * Frontend: asset loading and the automatic product block (Mode 2).
 *
 * @package LCS
 */

namespace LCS;

defined( 'ABSPATH' ) || exit;

/**
 * Frontend hooks.
 */
final class Frontend {

	/**
	 * Settings.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Renderer.
	 *
	 * @var Renderer
	 */
	private Renderer $renderer;

	/**
	 * Re-entrancy guard for nested the_content calls.
	 *
	 * @var bool
	 */
	private bool $rendering = false;

	/**
	 * Post IDs whose block was already printed this request.
	 *
	 * @var array<int,bool>
	 */
	private array $rendered = array();

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings.
	 * @param Renderer $renderer Renderer.
	 */
	public function __construct( Settings $settings, Renderer $renderer ) {
		$this->settings = $settings;
		$this->renderer = $renderer;
	}

	/**
	 * Hooks.
	 */
	public function hooks(): void {
		add_action( 'init', array( $this->renderer, 'register_assets' ), 20 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		// After Elementor's builder content filter (priority 9) so the block is prepended to it.
		add_filter( 'the_content', array( $this, 'prepend_product_block' ), 20 );
	}

	/**
	 * Enqueues the stylesheet on product screens and Lemon.js where the
	 * automatic block will print an overlay button. Widgets/shortcodes enqueue
	 * on demand elsewhere.
	 */
	public function enqueue(): void {
		if ( ! is_singular( Post_Type::POST_TYPE ) && ! is_post_type_archive( Post_Type::POST_TYPE ) && ! is_tax( Post_Type::TAXONOMY ) ) {
			return;
		}

		$this->renderer->enqueue_style();

		if ( is_singular( Post_Type::POST_TYPE ) && $this->settings->is_auto_block_mode() && $this->settings->is_overlay_checkout() ) {
			$this->renderer->enqueue_lemonjs();
		}
	}

	/**
	 * Prepends the product summary to the main product content.
	 *
	 * @param string $content Content.
	 */
	public function prepend_product_block( $content ) {
		if ( ! is_string( $content ) || ! $this->should_render_block() ) {
			return $content;
		}

		$post_id = (int) get_the_ID();
		$product = Product::from_id( $post_id );
		if ( ! $product ) {
			return $content;
		}

		$this->rendering            = true;
		$this->rendered[ $post_id ] = true;

		try {
			$block = $this->renderer->summary( $product );
		} finally {
			$this->rendering = false;
		}

		return $block . $content;
	}

	/**
	 * All safety conditions for the automatic block.
	 */
	private function should_render_block(): bool {
		if ( ! $this->settings->is_auto_block_mode() || $this->rendering ) {
			return false;
		}

		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_feed() || is_embed() ) {
			return false;
		}

		if ( ! is_singular( Post_Type::POST_TYPE ) || ! is_main_query() ) {
			return false;
		}

		$post_id = (int) get_the_ID();
		if ( $post_id <= 0 || $post_id !== (int) get_queried_object_id() || isset( $this->rendered[ $post_id ] ) ) {
			return false;
		}

		// Classic themes: only inside the main loop. Block themes render post content outside of it.
		if ( ! in_the_loop() && ! wp_is_block_theme() ) {
			return false;
		}

		if ( post_password_required( $post_id ) || Compat::is_elementor_editor() ) {
			return false;
		}

		/**
		 * Last chance to disable the automatic block for a request.
		 *
		 * @param bool $render  Whether to render.
		 * @param int  $post_id Product post ID.
		 */
		return (bool) apply_filters( 'lcs_render_auto_block', true, $post_id );
	}
}
