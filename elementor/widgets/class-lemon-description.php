<?php
/**
 * LCS Lemon Description widget.
 *
 * @package LCS
 */

namespace LCS\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

defined( 'ABSPATH' ) || exit;

/**
 * Shows _lcs_lemon_description (wp_kses_post filtered). This is not the post
 * content — per-product Elementor content lives in the Post Content widget.
 */
class Lemon_Description extends Base_Widget {

	/**
	 * Name.
	 */
	public function get_name(): string {
		return 'lcs-lemon-description';
	}

	/**
	 * Title.
	 */
	public function get_title(): string {
		return __( 'LCS Lemon Description', 'lemon-catalog-sync' );
	}

	/**
	 * Icon.
	 */
	public function get_icon(): string {
		return 'eicon-text';
	}

	/**
	 * Controls.
	 */
	protected function register_controls(): void {
		$this->start_controls_section(
			'section_style',
			array(
				'label' => __( 'Description', 'lemon-catalog-sync' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_alignment_control( '.lcs-lemon-description', 'text-align', true );

		$this->add_control(
			'text_color',
			array(
				'label'     => __( 'Text Color', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .lcs-lemon-description' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'link_color',
			array(
				'label'     => __( 'Link Color', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .lcs-lemon-description a' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'text_typography',
				'selector' => '{{WRAPPER}} .lcs-lemon-description',
			)
		);

		$this->add_responsive_control(
			'paragraph_spacing',
			array(
				'label'      => __( 'Paragraph Spacing', 'lemon-catalog-sync' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 80,
					),
					'em' => array(
						'min'  => 0,
						'max'  => 5,
						'step' => 0.1,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .lcs-lemon-description p, {{WRAPPER}} .lcs-lemon-description ul, {{WRAPPER}} .lcs-lemon-description ol' => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'padding',
			array(
				'label'      => __( 'Padding', 'lemon-catalog-sync' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array( '{{WRAPPER}} .lcs-lemon-description' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'margin',
			array(
				'label'      => __( 'Margin', 'lemon-catalog-sync' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array( '{{WRAPPER}} .lcs-lemon-description' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
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
			$this->renderer()->description( $product ),
			__( 'This product has no Lemon description.', 'lemon-catalog-sync' )
		);
	}
}
