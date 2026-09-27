<?php
/**
 * LCS Card Badge widget.
 *
 * @package LCS
 */

namespace LCS\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

defined( 'ABSPATH' ) || exit;

/**
 * Badge such as "%40 İndirim", "Yeni", "Çok Satan" entered in WordPress.
 */
class Card_Badge extends Base_Widget {

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
	 * Icon.
	 */
	public function get_icon(): string {
		return 'eicon-info-circle-o';
	}

	/**
	 * Keywords.
	 *
	 * @return string[]
	 */
	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), array( 'badge', 'label', 'rozet', 'etiket', 'sale', 'indirim' ) );
	}

	/**
	 * Controls.
	 */
	protected function register_controls(): void {
		$this->start_controls_section( 'section_content', array( 'label' => __( 'Card Badge', 'lemon-catalog-sync' ) ) );

		$this->add_control(
			'source_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Value comes from the "Kart Görünümü" box on each product. Tip: Advanced → Position → Absolute places the badge over the product image.', 'lemon-catalog-sync' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			array(
				'label' => __( 'Card Badge', 'lemon-catalog-sync' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_alignment_control( '.lcs-card-badge-wrap' );

		$this->add_control(
			'text_color',
			array(
				'label'     => __( 'Text Color', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .lcs-card-badge' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'background_color',
			array(
				'label'     => __( 'Background Color', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .lcs-card-badge' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography',
				'selector' => '{{WRAPPER}} .lcs-card-badge',
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'      => 'border',
				'selector'  => '{{WRAPPER}} .lcs-card-badge',
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'border_radius',
			array(
				'label'      => __( 'Border Radius', 'lemon-catalog-sync' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .lcs-card-badge' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'shadow',
				'selector' => '{{WRAPPER}} .lcs-card-badge',
			)
		);

		$this->add_responsive_control(
			'padding',
			array(
				'label'      => __( 'Padding', 'lemon-catalog-sync' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'separator'  => 'before',
				'selectors'  => array( '{{WRAPPER}} .lcs-card-badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'margin',
			array(
				'label'      => __( 'Margin', 'lemon-catalog-sync' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array( '{{WRAPPER}} .lcs-card-badge-wrap' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
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

		$this->output(
			$this->renderer()->card_badge( $product ),
			__( 'Card badge is empty for this preview product.', 'lemon-catalog-sync' )
		);
	}
}
