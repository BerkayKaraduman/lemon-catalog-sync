<?php
/**
 * Shortcodes — work with or without Elementor.
 *
 * @package LCS
 */

namespace LCS;

use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * [lcs_product_price] [lcs_product_image] [lcs_lemon_description]
 * [lcs_product_buy_button] [lcs_product_variants] [lcs_product_meta] [lcs_products]
 * [lcs_compare_price] [lcs_card_subtitle] [lcs_card_badge] [lcs_product_link]
 */
final class Shortcodes {

	/**
	 * Renderer.
	 *
	 * @var Renderer
	 */
	private Renderer $renderer;

	/**
	 * Constructor.
	 *
	 * @param Renderer $renderer Renderer.
	 */
	public function __construct( Renderer $renderer ) {
		$this->renderer = $renderer;
	}

	/**
	 * Hooks.
	 */
	public function hooks(): void {
		add_shortcode( 'lcs_product_price', array( $this, 'price' ) );
		add_shortcode( 'lcs_product_image', array( $this, 'image' ) );
		add_shortcode( 'lcs_lemon_description', array( $this, 'description' ) );
		add_shortcode( 'lcs_product_buy_button', array( $this, 'buy_button' ) );
		add_shortcode( 'lcs_product_variants', array( $this, 'variants' ) );
		add_shortcode( 'lcs_product_meta', array( $this, 'meta' ) );
		add_shortcode( 'lcs_products', array( $this, 'products' ) );
		add_shortcode( 'lcs_compare_price', array( $this, 'compare_price' ) );
		add_shortcode( 'lcs_card_subtitle', array( $this, 'card_subtitle' ) );
		add_shortcode( 'lcs_card_badge', array( $this, 'card_badge' ) );
		add_shortcode( 'lcs_product_link', array( $this, 'product_link' ) );
	}

	/**
	 * [lcs_product_price mode="auto|single|from|range" show_from="yes" prefix="" suffix="" id=""]
	 *
	 * @param array<string,string>|string $atts Attributes.
	 */
	public function price( $atts ): string {
		$atts    = shortcode_atts(
			array(
				'id'        => 0,
				'mode'      => 'auto',
				'show_from' => 'yes',
				'prefix'    => '',
				'suffix'    => '',
			),
			$atts,
			'lcs_product_price'
		);
		$product = Product_Context::get_current_product( absint( $atts['id'] ) );

		return $product ? $this->renderer->price(
			$product,
			array(
				'mode'      => sanitize_key( $atts['mode'] ),
				'show_from' => $this->bool( $atts['show_from'] ),
				'prefix'    => sanitize_text_field( $atts['prefix'] ),
				'suffix'    => sanitize_text_field( $atts['suffix'] ),
			)
		) : '';
	}

	/**
	 * [lcs_product_image size="large" id=""]
	 *
	 * @param array<string,string>|string $atts Attributes.
	 */
	public function image( $atts ): string {
		$atts    = shortcode_atts(
			array(
				'id'   => 0,
				'size' => 'large',
			),
			$atts,
			'lcs_product_image'
		);
		$product = Product_Context::get_current_product( absint( $atts['id'] ) );

		return $product ? $this->renderer->image( $product, array( 'size' => sanitize_key( $atts['size'] ) ) ) : '';
	}

	/**
	 * [lcs_lemon_description id=""]
	 *
	 * @param array<string,string>|string $atts Attributes.
	 */
	public function description( $atts ): string {
		$atts    = shortcode_atts( array( 'id' => 0 ), $atts, 'lcs_lemon_description' );
		$product = Product_Context::get_current_product( absint( $atts['id'] ) );

		return $product ? $this->renderer->description( $product ) : '';
	}

	/**
	 * [lcs_product_buy_button text="Satın Al" size="md" full_width="no" new_tab="no" id=""]
	 *
	 * @param array<string,string>|string $atts Attributes.
	 */
	public function buy_button( $atts ): string {
		$atts    = shortcode_atts(
			array(
				'id'         => 0,
				'text'       => '',
				'size'       => 'md',
				'full_width' => 'no',
				'new_tab'    => 'no',
				'class'      => '',
			),
			$atts,
			'lcs_product_buy_button'
		);
		$product = Product_Context::get_current_product( absint( $atts['id'] ) );

		return $product ? $this->renderer->buy_button(
			$product,
			array(
				'text'       => sanitize_text_field( $atts['text'] ),
				'size'       => sanitize_key( $atts['size'] ),
				'full_width' => $this->bool( $atts['full_width'] ),
				'new_tab'    => $this->bool( $atts['new_tab'] ),
				'class'      => sanitize_html_class( $atts['class'] ),
			)
		) : '';
	}

	/**
	 * [lcs_product_variants layout="list|grid" show_price="yes" show_description="yes" id=""]
	 *
	 * @param array<string,string>|string $atts Attributes.
	 */
	public function variants( $atts ): string {
		$atts    = shortcode_atts(
			array(
				'id'               => 0,
				'layout'           => 'list',
				'show_price'       => 'yes',
				'show_description' => 'yes',
			),
			$atts,
			'lcs_product_variants'
		);
		$product = Product_Context::get_current_product( absint( $atts['id'] ) );

		return $product ? $this->renderer->variants(
			$product,
			array(
				'layout'           => sanitize_key( $atts['layout'] ),
				'show_price'       => $this->bool( $atts['show_price'] ),
				'show_description' => $this->bool( $atts['show_description'] ),
			)
		) : '';
	}

	/**
	 * [lcs_product_meta items="lemon_id,price,variants,status,environment,updated" id=""]
	 *
	 * @param array<string,string>|string $atts Attributes.
	 */
	public function meta( $atts ): string {
		$atts    = shortcode_atts(
			array(
				'id'    => 0,
				'items' => 'lemon_id,price,variants',
			),
			$atts,
			'lcs_product_meta'
		);
		$product = Product_Context::get_current_product( absint( $atts['id'] ) );

		return $product ? $this->renderer->meta( $product, array_map( 'sanitize_key', array_map( 'trim', explode( ',', $atts['items'] ) ) ) ) : '';
	}

	/**
	 * [lcs_compare_price strike="yes" only_if_higher="no" prefix="" suffix="" id=""]
	 *
	 * @param array<string,string>|string $atts Attributes.
	 */
	public function compare_price( $atts ): string {
		$atts    = shortcode_atts(
			array(
				'id'             => 0,
				'strike'         => 'yes',
				'only_if_higher' => 'no',
				'prefix'         => '',
				'suffix'         => '',
			),
			$atts,
			'lcs_compare_price'
		);
		$product = Product_Context::get_current_product( absint( $atts['id'] ) );

		return $product ? $this->renderer->compare_price(
			$product,
			array(
				'strike'         => $this->bool( $atts['strike'] ),
				'only_if_higher' => $this->bool( $atts['only_if_higher'] ),
				'prefix'         => sanitize_text_field( $atts['prefix'] ),
				'suffix'         => sanitize_text_field( $atts['suffix'] ),
			)
		) : '';
	}

	/**
	 * [lcs_card_subtitle tag="p" id=""]
	 *
	 * @param array<string,string>|string $atts Attributes.
	 */
	public function card_subtitle( $atts ): string {
		$atts    = shortcode_atts(
			array(
				'id'  => 0,
				'tag' => 'p',
			),
			$atts,
			'lcs_card_subtitle'
		);
		$product = Product_Context::get_current_product( absint( $atts['id'] ) );

		return $product ? $this->renderer->card_subtitle( $product, array( 'tag' => sanitize_key( $atts['tag'] ) ) ) : '';
	}

	/**
	 * [lcs_card_badge id=""]
	 *
	 * @param array<string,string>|string $atts Attributes.
	 */
	public function card_badge( $atts ): string {
		$atts    = shortcode_atts( array( 'id' => 0 ), $atts, 'lcs_card_badge' );
		$product = Product_Context::get_current_product( absint( $atts['id'] ) );

		return $product ? $this->renderer->card_badge( $product ) : '';
	}

	/**
	 * [lcs_product_link text="İncele" target="product|buy" new_tab="no" id=""]
	 *
	 * @param array<string,string>|string $atts Attributes.
	 */
	public function product_link( $atts ): string {
		$atts    = shortcode_atts(
			array(
				'id'      => 0,
				'text'    => '',
				'target'  => 'product',
				'new_tab' => 'no',
			),
			$atts,
			'lcs_product_link'
		);
		$product = Product_Context::get_current_product( absint( $atts['id'] ) );

		return $product ? $this->renderer->product_link(
			$product,
			array(
				'text'    => sanitize_text_field( $atts['text'] ),
				'target'  => sanitize_key( $atts['target'] ),
				'new_tab' => $this->bool( $atts['new_tab'] ),
			)
		) : '';
	}

	/**
	 * [lcs_products category="preset" columns="3" limit="12" orderby="date" order="DESC" card_fields="yes"]
	 *
	 * @param array<string,string>|string $atts Attributes.
	 */
	public function products( $atts ): string {
		$atts = shortcode_atts(
			array(
				'category'    => '',
				'columns'     => 3,
				'limit'       => 12,
				'orderby'     => 'date',
				'order'       => 'DESC',
				'card_fields' => 'yes',
			),
			$atts,
			'lcs_products'
		);

		$orderby_map = array(
			'date'       => array( 'orderby' => 'date' ),
			'title'      => array( 'orderby' => 'title' ),
			'modified'   => array( 'orderby' => 'modified' ),
			'menu_order' => array( 'orderby' => 'menu_order' ),
			'rand'       => array( 'orderby' => 'rand' ),
			'price'      => array(
				'orderby'  => 'meta_value_num',
				'meta_key' => Meta::PRICE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			),
		);

		$orderby = sanitize_key( $atts['orderby'] );
		$order   = 'ASC' === strtoupper( (string) $atts['order'] ) ? 'ASC' : 'DESC';
		$limit   = max( 1, min( 100, absint( $atts['limit'] ) ) );

		$args = array_merge(
			array(
				'post_type'           => Post_Type::POST_TYPE,
				'post_status'         => 'publish',
				'posts_per_page'      => $limit,
				'order'               => $order,
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
			),
			$orderby_map[ $orderby ] ?? $orderby_map['date']
		);

		$categories = array_filter( array_map( 'sanitize_title', explode( ',', (string) $atts['category'] ) ) );
		if ( $categories ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			$args['tax_query'] = array(
				array(
					'taxonomy' => Post_Type::TAXONOMY,
					'field'    => 'slug',
					'terms'    => $categories,
				),
			);
		}

		/**
		 * Filters the [lcs_products] query arguments.
		 *
		 * @param array $args Query args.
		 * @param array $atts Shortcode attributes.
		 */
		$query = new WP_Query( apply_filters( 'lcs_products_query_args', $args, $atts ) );
		$html  = $this->renderer->grid( $query, absint( $atts['columns'] ), $this->bool( $atts['card_fields'] ) );
		wp_reset_postdata();

		return $html;
	}

	/**
	 * Parses yes/no/true/false/1/0.
	 *
	 * @param mixed $value Value.
	 */
	private function bool( $value ): bool {
		return in_array( strtolower( trim( (string) $value ) ), array( '1', 'yes', 'true', 'on' ), true );
	}
}
