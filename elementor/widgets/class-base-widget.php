<?php
/**
 * Base class for LCS Elementor widgets.
 *
 * @package LCS
 */

namespace LCS\Elementor\Widgets;

use Elementor\Widget_Base;
use LCS\Compat;
use LCS\Elementor\Integration;
use LCS\Plugin;
use LCS\Product;
use LCS\Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves the product from context — never from a hardcoded ID.
 */
abstract class Base_Widget extends Widget_Base {

	/**
	 * Widget category.
	 *
	 * @return string[]
	 */
	public function get_categories(): array {
		return array( Integration::CATEGORY );
	}

	/**
	 * Stylesheet dependency.
	 *
	 * @return string[]
	 */
	public function get_style_depends(): array {
		return array( Renderer::STYLE_HANDLE );
	}

	/**
	 * Keywords.
	 *
	 * @return string[]
	 */
	public function get_keywords(): array {
		return array( 'lemon', 'lemonsqueezy', 'product', 'lcs', 'urun' );
	}

	/**
	 * Current product; in the editor falls back to the latest product so
	 * Theme Builder templates show real data while designing.
	 */
	protected function product(): ?Product {
		$product = Product::current();
		if ( ! $product && Compat::is_elementor_editor() ) {
			$product = Product::preview_fallback();
		}
		return $product;
	}

	/**
	 * Shared renderer.
	 */
	protected function renderer(): Renderer {
		return Plugin::instance()->renderer();
	}

	/**
	 * Prints a hint in the editor only; prints nothing on the live site.
	 *
	 * @param string $message Message.
	 */
	protected function editor_hint( string $message ): void {
		if ( Compat::is_elementor_editor() ) {
			echo $this->renderer()->placeholder( $message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Renderer.
		}
	}

	/**
	 * Prints component HTML or an editor hint when empty.
	 *
	 * @param string $html  Escaped HTML from Renderer.
	 * @param string $empty Hint shown in the editor when $html is empty.
	 */
	protected function output( string $html, string $empty ): void {
		if ( '' === $html ) {
			$this->editor_hint( $empty );
			return;
		}
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Renderer.
	}

	/**
	 * Responsive left/center/right alignment control.
	 *
	 * @param string $selector CSS selector (without {{WRAPPER}}).
	 * @param string $property CSS property.
	 * @param bool   $justify  Offer "justify/stretch" option.
	 */
	protected function add_alignment_control( string $selector, string $property = 'text-align', bool $justify = false ): void {
		$options = array(
			'left'   => array(
				'title' => __( 'Left', 'lemon-catalog-sync' ),
				'icon'  => 'eicon-text-align-left',
			),
			'center' => array(
				'title' => __( 'Center', 'lemon-catalog-sync' ),
				'icon'  => 'eicon-text-align-center',
			),
			'right'  => array(
				'title' => __( 'Right', 'lemon-catalog-sync' ),
				'icon'  => 'eicon-text-align-right',
			),
		);

		if ( $justify ) {
			$options['justify'] = array(
				'title' => __( 'Justified', 'lemon-catalog-sync' ),
				'icon'  => 'eicon-text-align-justify',
			);
		}

		$this->add_responsive_control(
			'align',
			array(
				'label'     => __( 'Alignment', 'lemon-catalog-sync' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => $options,
				'selectors' => array( '{{WRAPPER}} ' . $selector => $property . ': {{VALUE}};' ),
			)
		);
	}

	/**
	 * Standard "no product" hint.
	 */
	protected function no_product_hint(): void {
		$this->editor_hint( __( 'No Dijital Ürün in this context. Use this widget in a Single template for Dijital Ürünler or on a product page.', 'lemon-catalog-sync' ) );
	}
}
