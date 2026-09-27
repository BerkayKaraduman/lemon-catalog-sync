<?php
/**
 * LCS Card Subtitle widget.
 *
 * @package LCS
 */

namespace LCS\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

defined( 'ABSPATH' ) || exit;

/**
 * Short card description entered in WordPress.
 */
class Card_Subtitle extends Base_Widget {

	/**
	 * Name.
	 */
	public function get_name(): string {
		return 'lcs-card-subtitle';
	}

	/**
	 * Title.
	 */
	public function get_title(): string {
		return __( 'LCS Card Subtitle', 'lemon-catalog-sync' );
	}

	/**
	 * Icon.
	 */
	public function get_icon(): string {
		return 'eicon-t-letter';
	}

	/**
	 * Keywords.
	 *
	 * @return string[]
	 */
	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), array( 'subtitle', 'card', 'alt açıklama', 'kart' ) );
	}

	/**
	 * Controls.
	 */
	protected function register_controls(): void {
		$this->start_controls_section( 'section_content', array( 'label' => __( 'Card Subtitle', 'lemon-catalog-sync' ) ) );

		$this->add_control(
			'source_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Value comes from the "Kart Görünümü" box on each product.', 'lemon-catalog-sync' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->add_control(
			'html_tag',
			array(
				'label'   => __( 'HTML Tag', 'lemon-catalog-sync' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'p',
				'options' => array(
					'p'    => 'p',
					'div'  => 'div',
					'span' => 'span',
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			array(
				'label' => __( 'Card Subtitle', 'lemon-catalog-sync' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_alignment_control( '.lcs-card-subtitle', 'text-align', true );

		$this->add_control(
			'color',
			array(
				'label'     => __( 'Color', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .lcs-card-subtitle' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography',
				'selector' => '{{WRAPPER}} .lcs-card-subtitle',
			)
		);

		$this->add_spacing_controls( '.lcs-card-subtitle' );

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
			$this->renderer()->card_subtitle( $product, array( 'tag' => (string) ( $settings['html_tag'] ?? 'p' ) ) ),
			__( 'Card subtitle is empty for this preview product.', 'lemon-catalog-sync' )
		);
	}
}
