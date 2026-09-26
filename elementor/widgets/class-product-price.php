<?php
/**
 * LCS Product Price widget.
 *
 * @package LCS
 */

namespace LCS\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

defined( 'ABSPATH' ) || exit;

/**
 * "$19", "From $19" or "$19 – $49".
 */
class Product_Price extends Base_Widget {

	/**
	 * Name.
	 */
	public function get_name(): string {
		return 'lcs-product-price';
	}

	/**
	 * Title.
	 */
	public function get_title(): string {
		return __( 'LCS Product Price', 'lemon-catalog-sync' );
	}

	/**
	 * Icon.
	 */
	public function get_icon(): string {
		return 'eicon-product-price';
	}

	/**
	 * Controls.
	 */
	protected function register_controls(): void {
		$this->start_controls_section( 'section_content', array( 'label' => __( 'Price', 'lemon-catalog-sync' ) ) );

		$this->add_control(
			'display_mode',
			array(
				'label'   => __( 'Price Display Mode', 'lemon-catalog-sync' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => array(
					'auto'   => __( 'Automatic', 'lemon-catalog-sync' ),
					'single' => __( 'Single price ($19)', 'lemon-catalog-sync' ),
					'from'   => __( 'From price (From $19)', 'lemon-catalog-sync' ),
					'range'  => __( 'Range ($19 – $49)', 'lemon-catalog-sync' ),
				),
			)
		);

		$this->add_control(
			'show_from',
			array(
				'label'       => __( 'Show From Price', 'lemon-catalog-sync' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'description' => __( 'For products with several prices: "From $19" when on, "$19 – $49" when off.', 'lemon-catalog-sync' ),
				'condition'   => array( 'display_mode' => 'auto' ),
			)
		);

		$this->add_control(
			'prefix',
			array(
				'label'   => __( 'Prefix', 'lemon-catalog-sync' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			)
		);

		$this->add_control(
			'suffix',
			array(
				'label'   => __( 'Suffix', 'lemon-catalog-sync' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			array(
				'label' => __( 'Price', 'lemon-catalog-sync' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'align',
			array(
				'label'     => __( 'Alignment', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
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
				),
				'selectors' => array( '{{WRAPPER}} .lcs-product-price' => 'text-align: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'price_color',
			array(
				'label'     => __( 'Color', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .lcs-product-price' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'price_typography',
				'selector' => '{{WRAPPER}} .lcs-product-price__amount',
			)
		);

		$this->add_control(
			'affix_heading',
			array(
				'label'     => __( 'Prefix / Suffix', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'affix_typography',
				'selector' => '{{WRAPPER}} .lcs-product-price__prefix, {{WRAPPER}} .lcs-product-price__suffix',
			)
		);

		$this->add_responsive_control(
			'margin',
			array(
				'label'      => __( 'Margin', 'lemon-catalog-sync' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .lcs-product-price' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
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
			$this->renderer()->price(
				$product,
				array(
					'mode'      => (string) ( $settings['display_mode'] ?? 'auto' ),
					'show_from' => 'yes' === ( $settings['show_from'] ?? 'yes' ),
					'prefix'    => (string) ( $settings['prefix'] ?? '' ),
					'suffix'    => (string) ( $settings['suffix'] ?? '' ),
				)
			),
			__( 'This product has no price yet. Run a sync.', 'lemon-catalog-sync' )
		);
	}
}
