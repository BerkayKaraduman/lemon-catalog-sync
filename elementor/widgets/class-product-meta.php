<?php
/**
 * LCS Product Meta widget.
 *
 * @package LCS
 */

namespace LCS\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

defined( 'ABSPATH' ) || exit;

/**
 * Key/value list of Lemon product facts.
 */
class Product_Meta extends Base_Widget {

	/**
	 * Selectable items.
	 *
	 * @return array<string,string>
	 */
	private function items(): array {
		return array(
			'lemon_id'    => __( 'Product ID', 'lemon-catalog-sync' ),
			'price'       => __( 'Price', 'lemon-catalog-sync' ),
			'variants'    => __( 'Variant count', 'lemon-catalog-sync' ),
			'status'      => __( 'Lemon status', 'lemon-catalog-sync' ),
			'environment' => __( 'Test / Live', 'lemon-catalog-sync' ),
			'updated'     => __( 'Last updated', 'lemon-catalog-sync' ),
		);
	}

	/**
	 * Name.
	 */
	public function get_name(): string {
		return 'lcs-product-meta';
	}

	/**
	 * Title.
	 */
	public function get_title(): string {
		return __( 'LCS Product Meta', 'lemon-catalog-sync' );
	}

	/**
	 * Icon.
	 */
	public function get_icon(): string {
		return 'eicon-product-meta';
	}

	/**
	 * Controls.
	 */
	protected function register_controls(): void {
		$this->start_controls_section( 'section_content', array( 'label' => __( 'Items', 'lemon-catalog-sync' ) ) );

		$this->add_control(
			'items',
			array(
				'label'       => __( 'Show', 'lemon-catalog-sync' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'options'     => $this->items(),
				'default'     => array( 'price', 'variants', 'updated' ),
				'label_block' => true,
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			array(
				'label' => __( 'Meta', 'lemon-catalog-sync' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'label_typography',
				'label'    => __( 'Label Typography', 'lemon-catalog-sync' ),
				'selector' => '{{WRAPPER}} .lcs-product-meta dt',
			)
		);

		$this->add_control(
			'label_color',
			array(
				'label'     => __( 'Label Color', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .lcs-product-meta dt' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'value_typography',
				'label'    => __( 'Value Typography', 'lemon-catalog-sync' ),
				'selector' => '{{WRAPPER}} .lcs-product-meta dd',
			)
		);

		$this->add_control(
			'value_color',
			array(
				'label'     => __( 'Value Color', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .lcs-product-meta dd' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'divider_color',
			array(
				'label'     => __( 'Divider Color', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .lcs-product-meta__row' => 'border-bottom-color: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'row_spacing',
			array(
				'label'      => __( 'Row Spacing', 'lemon-catalog-sync' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .lcs-product-meta__row' => 'padding-top: {{SIZE}}{{UNIT}}; padding-bottom: {{SIZE}}{{UNIT}};' ),
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
		$items    = array_values( array_intersect( (array) ( $settings['items'] ?? array() ), array_keys( $this->items() ) ) );

		$this->output(
			$this->renderer()->meta( $product, $items ),
			__( 'Select at least one item to show.', 'lemon-catalog-sync' )
		);
	}
}
