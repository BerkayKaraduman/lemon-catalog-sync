<?php
/**
 * LCS Buy Button widget.
 *
 * @package LCS
 */

namespace LCS\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use LCS\Plugin;
use LCS\Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * Links to _lcs_buy_now_url. Overlay mode adds "lemonsqueezy-button" and loads Lemon.js.
 */
class Buy_Button extends Base_Widget {

	/**
	 * Name.
	 */
	public function get_name(): string {
		return 'lcs-buy-button';
	}

	/**
	 * Title.
	 */
	public function get_title(): string {
		return __( 'LCS Buy Button', 'lemon-catalog-sync' );
	}

	/**
	 * Icon.
	 */
	public function get_icon(): string {
		return 'eicon-button';
	}

	/**
	 * Lemon.js only where this widget is used, only in overlay mode.
	 *
	 * @return string[]
	 */
	public function get_script_depends(): array {
		return Plugin::instance()->settings()->is_overlay_checkout() ? array( Renderer::LEMONJS ) : array();
	}

	/**
	 * Controls.
	 */
	protected function register_controls(): void {
		$this->start_controls_section( 'section_content', array( 'label' => __( 'Button', 'lemon-catalog-sync' ) ) );

		$this->add_control(
			'text',
			array(
				'label'   => __( 'Button Text', 'lemon-catalog-sync' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Satın Al', 'lemon-catalog-sync' ),
			)
		);

		$this->add_control(
			'size',
			array(
				'label'   => __( 'Size', 'lemon-catalog-sync' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'md',
				'options' => array(
					'sm' => __( 'Small', 'lemon-catalog-sync' ),
					'md' => __( 'Medium', 'lemon-catalog-sync' ),
					'lg' => __( 'Large', 'lemon-catalog-sync' ),
				),
			)
		);

		$this->add_control(
			'width_mode',
			array(
				'label'   => __( 'Width', 'lemon-catalog-sync' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => array(
					'auto'   => __( 'Auto', 'lemon-catalog-sync' ),
					'full'   => __( 'Full width', 'lemon-catalog-sync' ),
					'custom' => __( 'Custom', 'lemon-catalog-sync' ),
				),
			)
		);

		$this->add_responsive_control(
			'custom_width',
			array(
				'label'      => __( 'Custom Width', 'lemon-catalog-sync' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array(
						'min' => 50,
						'max' => 800,
					),
					'%'  => array(
						'min' => 5,
						'max' => 100,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .lcs-buy-button' => 'width: {{SIZE}}{{UNIT}};' ),
				'condition'  => array( 'width_mode' => 'custom' ),
			)
		);

		$this->add_control(
			'new_tab',
			array(
				'label'       => __( 'Open in new tab', 'lemon-catalog-sync' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'description' => __( 'Hosted checkout only. The overlay always opens on the page.', 'lemon-catalog-sync' ),
			)
		);

		$this->add_alignment_control( '.lcs-buy-button-wrap' );

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			array(
				'label' => __( 'Button', 'lemon-catalog-sync' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography',
				'selector' => '{{WRAPPER}} .lcs-buy-button',
			)
		);

		$this->start_controls_tabs( 'tabs_button' );

		$this->start_controls_tab( 'tab_normal', array( 'label' => __( 'Normal', 'lemon-catalog-sync' ) ) );
		$this->add_control(
			'text_color',
			array(
				'label'     => __( 'Text Color', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .lcs-buy-button' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'background_color',
			array(
				'label'     => __( 'Background Color', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .lcs-buy-button' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab( 'tab_hover', array( 'label' => __( 'Hover', 'lemon-catalog-sync' ) ) );
		$this->add_control(
			'hover_text_color',
			array(
				'label'     => __( 'Text Color', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .lcs-buy-button:hover, {{WRAPPER}} .lcs-buy-button:focus' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'hover_background_color',
			array(
				'label'     => __( 'Background Color', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .lcs-buy-button:hover, {{WRAPPER}} .lcs-buy-button:focus' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'hover_border_color',
			array(
				'label'     => __( 'Border Color', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .lcs-buy-button:hover, {{WRAPPER}} .lcs-buy-button:focus' => 'border-color: {{VALUE}};' ),
			)
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'      => 'border',
				'selector'  => '{{WRAPPER}} .lcs-buy-button',
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'border_radius',
			array(
				'label'      => __( 'Border Radius', 'lemon-catalog-sync' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .lcs-buy-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'padding',
			array(
				'label'      => __( 'Padding', 'lemon-catalog-sync' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array( '{{WRAPPER}} .lcs-buy-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'shadow',
				'selector' => '{{WRAPPER}} .lcs-buy-button',
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
			$this->renderer()->buy_button(
				$product,
				array(
					'text'       => (string) ( $settings['text'] ?? '' ),
					'size'       => (string) ( $settings['size'] ?? 'md' ),
					'full_width' => 'full' === ( $settings['width_mode'] ?? 'auto' ),
					'new_tab'    => 'yes' === ( $settings['new_tab'] ?? '' ),
				)
			),
			__( 'This product has no Lemon Buy Now URL yet. Publish it in Lemon Squeezy and run a sync.', 'lemon-catalog-sync' )
		);
	}
}
