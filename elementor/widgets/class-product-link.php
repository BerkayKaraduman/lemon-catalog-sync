<?php
/**
 * LCS Product Link widget.
 *
 * @package LCS
 */

namespace LCS\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;

defined( 'ABSPATH' ) || exit;

/**
 * Link / button to the current product's page (or its Lemon checkout).
 */
class Product_Link extends Base_Widget {

	/**
	 * Name.
	 */
	public function get_name(): string {
		return 'lcs-product-link';
	}

	/**
	 * Title.
	 */
	public function get_title(): string {
		return __( 'LCS Product Link', 'lemon-catalog-sync' );
	}

	/**
	 * Icon.
	 */
	public function get_icon(): string {
		return 'eicon-link';
	}

	/**
	 * Keywords.
	 *
	 * @return string[]
	 */
	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), array( 'link', 'permalink', 'incele', 'detay', 'button' ) );
	}

	/**
	 * Controls.
	 */
	protected function register_controls(): void {
		$this->start_controls_section( 'section_content', array( 'label' => __( 'Product Link', 'lemon-catalog-sync' ) ) );

		$this->add_control(
			'text',
			array(
				'label'   => __( 'Text', 'lemon-catalog-sync' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'İncele', 'lemon-catalog-sync' ),
			)
		);

		$this->add_control(
			'target',
			array(
				'label'   => __( 'Link To', 'lemon-catalog-sync' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'product',
				'options' => array(
					'product' => __( 'Product page', 'lemon-catalog-sync' ),
					'buy'     => __( 'Lemon checkout (Buy Now URL)', 'lemon-catalog-sync' ),
				),
			)
		);

		$this->add_control(
			'new_tab',
			array(
				'label'   => __( 'Open in new tab', 'lemon-catalog-sync' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => '',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			array(
				'label' => __( 'Product Link', 'lemon-catalog-sync' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_alignment_control( '.lcs-product-link-wrap' );

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography',
				'selector' => '{{WRAPPER}} .lcs-product-link',
			)
		);

		$this->start_controls_tabs( 'tabs_link' );

		$this->start_controls_tab( 'tab_normal', array( 'label' => __( 'Normal', 'lemon-catalog-sync' ) ) );
		$this->add_control(
			'text_color',
			array(
				'label'     => __( 'Text Color', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .lcs-product-link' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'background_color',
			array(
				'label'     => __( 'Background Color', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .lcs-product-link' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab( 'tab_hover', array( 'label' => __( 'Hover', 'lemon-catalog-sync' ) ) );
		$this->add_control(
			'hover_text_color',
			array(
				'label'     => __( 'Text Color', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .lcs-product-link:hover, {{WRAPPER}} .lcs-product-link:focus' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'hover_background_color',
			array(
				'label'     => __( 'Background Color', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .lcs-product-link:hover, {{WRAPPER}} .lcs-product-link:focus' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'hover_border_color',
			array(
				'label'     => __( 'Border Color', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .lcs-product-link:hover, {{WRAPPER}} .lcs-product-link:focus' => 'border-color: {{VALUE}};' ),
			)
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'      => 'border',
				'selector'  => '{{WRAPPER}} .lcs-product-link',
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'border_radius',
			array(
				'label'      => __( 'Border Radius', 'lemon-catalog-sync' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .lcs-product-link' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'padding',
			array(
				'label'      => __( 'Padding', 'lemon-catalog-sync' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array( '{{WRAPPER}} .lcs-product-link' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'margin',
			array(
				'label'      => __( 'Margin', 'lemon-catalog-sync' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array( '{{WRAPPER}} .lcs-product-link-wrap' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
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

		$this->output(
			$this->renderer()->product_link(
				$product,
				array(
					'text'    => (string) ( $settings['text'] ?? '' ),
					'target'  => (string) ( $settings['target'] ?? 'product' ),
					'new_tab' => 'yes' === ( $settings['new_tab'] ?? '' ),
				)
			),
			__( 'This product has no link target yet.', 'lemon-catalog-sync' )
		);
	}
}
