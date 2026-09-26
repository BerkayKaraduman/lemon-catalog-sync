<?php
/**
 * LCS Variant Selector widget.
 *
 * @package LCS
 */

namespace LCS\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;

defined( 'ABSPATH' ) || exit;

/**
 * Displays variants (names, prices, descriptions). It deliberately adds no
 * custom checkout logic: purchases go through the product's official Buy Now URL.
 */
class Variant_Selector extends Base_Widget {

	/**
	 * Name.
	 */
	public function get_name(): string {
		return 'lcs-variant-selector';
	}

	/**
	 * Title.
	 */
	public function get_title(): string {
		return __( 'LCS Variant Selector', 'lemon-catalog-sync' );
	}

	/**
	 * Icon.
	 */
	public function get_icon(): string {
		return 'eicon-bullet-list';
	}

	/**
	 * Controls.
	 */
	protected function register_controls(): void {
		$this->start_controls_section( 'section_content', array( 'label' => __( 'Variants', 'lemon-catalog-sync' ) ) );

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Layout', 'lemon-catalog-sync' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'list',
				'options' => array(
					'list' => __( 'List', 'lemon-catalog-sync' ),
					'grid' => __( 'Grid', 'lemon-catalog-sync' ),
				),
			)
		);

		$this->add_control(
			'show_price',
			array(
				'label'   => __( 'Show price', 'lemon-catalog-sync' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'show_description',
			array(
				'label'   => __( 'Show description', 'lemon-catalog-sync' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'hide_single',
			array(
				'label'       => __( 'Hide when only one variant', 'lemon-catalog-sync' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'description' => __( 'Variants are informational; checkout uses the product Buy Now URL.', 'lemon-catalog-sync' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			array(
				'label' => __( 'Variant', 'lemon-catalog-sync' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'      => __( 'Gap', 'lemon-catalog-sync' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .lcs-variants' => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'card_background',
			array(
				'label'     => __( 'Background Color', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .lcs-variant' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'card_border',
				'selector' => '{{WRAPPER}} .lcs-variant',
			)
		);

		$this->add_responsive_control(
			'card_radius',
			array(
				'label'      => __( 'Border Radius', 'lemon-catalog-sync' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .lcs-variant' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'card_padding',
			array(
				'label'      => __( 'Padding', 'lemon-catalog-sync' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array( '{{WRAPPER}} .lcs-variant' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'name_typography',
				'label'    => __( 'Name Typography', 'lemon-catalog-sync' ),
				'selector' => '{{WRAPPER}} .lcs-variant__name',
			)
		);

		$this->add_control(
			'name_color',
			array(
				'label'     => __( 'Name Color', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .lcs-variant__name' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'price_typography',
				'label'    => __( 'Price Typography', 'lemon-catalog-sync' ),
				'selector' => '{{WRAPPER}} .lcs-variant__price',
			)
		);

		$this->add_control(
			'price_color',
			array(
				'label'     => __( 'Price Color', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .lcs-variant__price' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'description_typography',
				'label'    => __( 'Description Typography', 'lemon-catalog-sync' ),
				'selector' => '{{WRAPPER}} .lcs-variant__description',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render.
	 */
	protected function render(): void {
		$product = $this->product();
		if ( ! $product ) {
			$this->no_product_hint();
			return;
		}

		$settings = $this->get_settings_for_display();

		if ( 'yes' === ( $settings['hide_single'] ?? 'yes' ) && count( $product->public_variants() ) < 2 ) {
			$this->editor_hint( __( 'This product has a single variant, so the selector is hidden (see "Hide when only one variant").', 'lemon-catalog-sync' ) );
			return;
		}

		$this->output(
			$this->renderer()->variants(
				$product,
				array(
					'layout'           => (string) ( $settings['layout'] ?? 'list' ),
					'show_price'       => 'yes' === ( $settings['show_price'] ?? 'yes' ),
					'show_description' => 'yes' === ( $settings['show_description'] ?? 'yes' ),
				)
			),
			__( 'This product has no published variants.', 'lemon-catalog-sync' )
		);
	}
}
