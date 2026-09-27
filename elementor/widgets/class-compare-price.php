<?php
/**
 * LCS Compare Price widget.
 *
 * @package LCS
 */

namespace LCS\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

defined( 'ABSPATH' ) || exit;

/**
 * Old / list price entered in WordPress, struck through by default.
 * Prints nothing on the live site when the field is empty.
 */
class Compare_Price extends Base_Widget {

	/**
	 * Name.
	 */
	public function get_name(): string {
		return 'lcs-compare-price';
	}

	/**
	 * Title.
	 */
	public function get_title(): string {
		return __( 'LCS Compare Price', 'lemon-catalog-sync' );
	}

	/**
	 * Icon.
	 */
	public function get_icon(): string {
		return 'eicon-price-list';
	}

	/**
	 * Keywords.
	 *
	 * @return string[]
	 */
	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), array( 'compare', 'old price', 'eski fiyat', 'indirim', 'sale' ) );
	}

	/**
	 * Controls.
	 */
	protected function register_controls(): void {
		$this->start_controls_section( 'section_content', array( 'label' => __( 'Compare Price', 'lemon-catalog-sync' ) ) );

		$this->add_control(
			'source_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Value comes from the "Kart Görünümü" box on each product. Empty → nothing is shown.', 'lemon-catalog-sync' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->add_control(
			'strike',
			array(
				'label'   => __( 'Strikethrough', 'lemon-catalog-sync' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'only_if_higher',
			array(
				'label'       => __( 'Only when higher than Lemon price', 'lemon-catalog-sync' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'description' => __( 'Numeric check: hides the old price unless it is greater than the current Lemon price.', 'lemon-catalog-sync' ),
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
				'label' => __( 'Compare Price', 'lemon-catalog-sync' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_alignment_control( '.lcs-compare-price' );

		$this->add_control(
			'color',
			array(
				'label'     => __( 'Color', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .lcs-compare-price' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography',
				'selector' => '{{WRAPPER}} .lcs-compare-price__amount',
			)
		);

		$this->add_control(
			'strike_heading',
			array(
				'label'     => __( 'Strikethrough Line', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array( 'strike' => 'yes' ),
			)
		);

		$this->add_control(
			'strike_color',
			array(
				'label'     => __( 'Line Color', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .lcs-compare-price--strike .lcs-compare-price__amount' => 'text-decoration-color: {{VALUE}};' ),
				'condition' => array( 'strike' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'strike_thickness',
			array(
				'label'      => __( 'Line Thickness', 'lemon-catalog-sync' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 1,
						'max' => 10,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .lcs-compare-price--strike .lcs-compare-price__amount' => 'text-decoration-thickness: {{SIZE}}{{UNIT}};' ),
				'condition'  => array( 'strike' => 'yes' ),
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
				'selector' => '{{WRAPPER}} .lcs-compare-price__prefix, {{WRAPPER}} .lcs-compare-price__suffix',
			)
		);

		$this->add_control(
			'spacing_heading',
			array(
				'label'     => __( 'Spacing', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_spacing_controls( '.lcs-compare-price' );

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
			$this->renderer()->compare_price(
				$product,
				array(
					'strike'         => 'yes' === ( $settings['strike'] ?? 'yes' ),
					'only_if_higher' => 'yes' === ( $settings['only_if_higher'] ?? '' ),
					'prefix'         => (string) ( $settings['prefix'] ?? '' ),
					'suffix'         => (string) ( $settings['suffix'] ?? '' ),
				)
			),
			__( 'Compare price is empty for this preview product.', 'lemon-catalog-sync' )
		);
	}
}
