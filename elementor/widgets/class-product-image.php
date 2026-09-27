<?php
/**
 * LCS Product Image widget.
 *
 * @package LCS
 */

namespace LCS\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

defined( 'ABSPATH' ) || exit;

/**
 * Remote mode → Lemon large_thumb_url; Media Library mode → featured image.
 */
class Product_Image extends Base_Widget {

	/**
	 * Name.
	 */
	public function get_name(): string {
		return 'lcs-product-image';
	}

	/**
	 * Title.
	 */
	public function get_title(): string {
		return __( 'LCS Product Image', 'lemon-catalog-sync' );
	}

	/**
	 * Icon.
	 */
	public function get_icon(): string {
		return 'eicon-image';
	}

	/**
	 * Controls.
	 */
	protected function register_controls(): void {
		$this->start_controls_section( 'section_content', array( 'label' => __( 'Image', 'lemon-catalog-sync' ) ) );

		$sizes = array( 'full' => __( 'Full', 'lemon-catalog-sync' ) );
		foreach ( get_intermediate_image_sizes() as $size ) {
			$sizes[ $size ] = ucwords( str_replace( array( '_', '-' ), ' ', $size ) );
		}

		$this->add_control(
			'image_size',
			array(
				'label'       => __( 'Image Size', 'lemon-catalog-sync' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'large',
				'options'     => $sizes,
				'description' => __( 'Applies in Media Library image mode. Remote mode always uses the Lemon large image.', 'lemon-catalog-sync' ),
			)
		);

		$this->add_control(
			'link_to_product',
			array(
				'label'   => __( 'Link to product page', 'lemon-catalog-sync' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => '',
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
				'selectors' => array( '{{WRAPPER}} .lcs-product-image' => 'text-align: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			array(
				'label' => __( 'Image', 'lemon-catalog-sync' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'width',
			array(
				'label'      => __( 'Width', 'lemon-catalog-sync' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%', 'px', 'vw' ),
				'range'      => array(
					'%'  => array(
						'min' => 1,
						'max' => 100,
					),
					'px' => array(
						'min' => 1,
						'max' => 1600,
					),
					'vw' => array(
						'min' => 1,
						'max' => 100,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .lcs-product-image__img' => 'width: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'max_width',
			array(
				'label'      => __( 'Max Width', 'lemon-catalog-sync' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%', 'px', 'vw' ),
				'range'      => array(
					'%'  => array(
						'min' => 1,
						'max' => 100,
					),
					'px' => array(
						'min' => 1,
						'max' => 1600,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .lcs-product-image__img' => 'max-width: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'height',
			array(
				'label'      => __( 'Height', 'lemon-catalog-sync' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh' ),
				'range'      => array(
					'px' => array(
						'min' => 1,
						'max' => 1200,
					),
					'vh' => array(
						'min' => 1,
						'max' => 100,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .lcs-product-image__img' => 'height: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'object_fit',
			array(
				'label'     => __( 'Object Fit', 'lemon-catalog-sync' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => array(
					''        => __( 'Default', 'lemon-catalog-sync' ),
					'fill'    => __( 'Fill', 'lemon-catalog-sync' ),
					'cover'   => __( 'Cover', 'lemon-catalog-sync' ),
					'contain' => __( 'Contain', 'lemon-catalog-sync' ),
				),
				'selectors' => array( '{{WRAPPER}} .lcs-product-image__img' => 'object-fit: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'image_border',
				'selector' => '{{WRAPPER}} .lcs-product-image__img',
			)
		);

		$this->add_responsive_control(
			'border_radius',
			array(
				'label'      => __( 'Border Radius', 'lemon-catalog-sync' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .lcs-product-image__img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'image_shadow',
				'selector' => '{{WRAPPER}} .lcs-product-image__img',
			)
		);

		$this->add_spacing_controls( '.lcs-product-image' );

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
			$this->renderer()->image(
				$product,
				array(
					'size' => (string) ( $settings['image_size'] ?? 'large' ),
					'link' => 'yes' === ( $settings['link_to_product'] ?? '' ),
				)
			),
			__( 'This product has no Lemon image yet.', 'lemon-catalog-sync' )
		);
	}
}
