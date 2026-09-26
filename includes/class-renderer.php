<?php
/**
 * Shared HTML components used by Elementor widgets, shortcodes and the
 * automatic product block — one implementation, consistent markup.
 *
 * @package LCS
 */

namespace LCS;

use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Every method returns escaped HTML.
 */
final class Renderer {

	public const STYLE_HANDLE = 'lcs-frontend';
	public const LEMONJS      = 'lcs-lemon-js';
	public const LEMONJS_SRC  = 'https://app.lemonsqueezy.com/js/lemon.js';

	/**
	 * Settings.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Settings accessor for widgets.
	 */
	public function settings(): Settings {
		return $this->settings;
	}

	/**
	 * Registers frontend assets (enqueued only when needed).
	 */
	public function register_assets(): void {
		wp_register_style( self::STYLE_HANDLE, LCS_URL . 'public/css/lcs-frontend.css', array(), LCS_VERSION );

		// Official CDN, never self-hosted. Version null so the URL stays exactly as documented.
		wp_register_script(
			self::LEMONJS,
			self::LEMONJS_SRC,
			array(),
			null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- external versionless CDN file.
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Enqueues the stylesheet.
	 */
	public function enqueue_style(): void {
		if ( ! wp_style_is( self::STYLE_HANDLE, 'registered' ) ) {
			$this->register_assets();
		}
		wp_enqueue_style( self::STYLE_HANDLE );
	}

	/**
	 * Enqueues Lemon.js (overlay checkout only).
	 */
	public function enqueue_lemonjs(): void {
		if ( ! wp_script_is( self::LEMONJS, 'registered' ) ) {
			$this->register_assets();
		}
		wp_enqueue_script( self::LEMONJS );
	}

	/*
	 * ---------------------------------------------------------------------
	 * Components
	 * ---------------------------------------------------------------------
	 */

	/**
	 * Product image.
	 *
	 * @param Product             $product Product.
	 * @param array<string,mixed> $args    size, class, link (bool).
	 */
	public function image( Product $product, array $args = array() ): string {
		$args = wp_parse_args(
			$args,
			array(
				'size'  => 'large',
				'class' => '',
				'link'  => false,
			)
		);

		$this->enqueue_style();
		$alt = $product->title();
		$img = '';

		if ( $this->settings->is_media_image_mode() && $product->thumbnail_id() ) {
			$img = wp_get_attachment_image(
				$product->thumbnail_id(),
				(string) $args['size'],
				false,
				array(
					'class'   => 'lcs-product-image__img',
					'loading' => 'lazy',
				)
			);
		}

		if ( '' === $img ) {
			$url = $product->remote_image_url();
			if ( '' === $url ) {
				return '';
			}
			$img = sprintf(
				'<img class="lcs-product-image__img" src="%s" alt="%s" loading="lazy" decoding="async" />',
				esc_url( $url ),
				esc_attr( $alt )
			);
		}

		if ( ! empty( $args['link'] ) ) {
			$img = sprintf( '<a href="%s">%s</a>', esc_url( $product->permalink() ), $img );
		}

		return sprintf( '<div class="%s">%s</div>', esc_attr( trim( 'lcs-product-image ' . $args['class'] ) ), $img );
	}

	/**
	 * Price text (unescaped plain text).
	 *
	 * @param Product $product   Product.
	 * @param string  $mode      auto|single|from|range.
	 * @param bool    $show_from In auto mode: "From X" (true) or "X – Y" (false) for ranges.
	 */
	public function price_text( Product $product, string $mode = 'auto', bool $show_from = true ): string {
		$single = $product->price_formatted();
		$from   = $product->from_price_formatted();
		$to     = $product->to_price_formatted();
		$range  = $product->has_price_range() && '' !== $from && '' !== $to;

		/* translators: %s: formatted price. */
		$from_label = __( 'From %s', 'lemon-catalog-sync' );

		switch ( $mode ) {
			case 'single':
				return '' !== $single ? $single : $from;
			case 'from':
				return '' !== $from ? sprintf( $from_label, $from ) : $single;
			case 'range':
				return $range ? $from . ' – ' . $to : ( '' !== $single ? $single : $from );
			default:
				if ( $range ) {
					return $show_from ? sprintf( $from_label, $from ) : $from . ' – ' . $to;
				}
				return '' !== $single ? $single : $from;
		}
	}

	/**
	 * Price block.
	 *
	 * @param Product             $product Product.
	 * @param array<string,mixed> $args    mode, show_from, prefix, suffix.
	 */
	public function price( Product $product, array $args = array() ): string {
		$args = wp_parse_args(
			$args,
			array(
				'mode'      => 'auto',
				'show_from' => true,
				'prefix'    => '',
				'suffix'    => '',
			)
		);

		$text = $this->price_text( $product, (string) $args['mode'], (bool) $args['show_from'] );
		if ( '' === $text ) {
			return '';
		}

		$this->enqueue_style();

		$html = '<div class="lcs-product-price">';
		if ( '' !== (string) $args['prefix'] ) {
			$html .= '<span class="lcs-product-price__prefix">' . esc_html( (string) $args['prefix'] ) . '</span> ';
		}
		$html .= '<span class="lcs-product-price__amount">' . esc_html( $text ) . '</span>';
		if ( '' !== (string) $args['suffix'] ) {
			$html .= ' <span class="lcs-product-price__suffix">' . esc_html( (string) $args['suffix'] ) . '</span>';
		}
		if ( $product->is_pay_what_you_want() ) {
			$html .= ' <span class="lcs-product-price__pwyw">' . esc_html__( 'Pay what you want', 'lemon-catalog-sync' ) . '</span>';
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Lemon description (HTML filtered with wp_kses_post).
	 *
	 * @param Product $product Product.
	 */
	public function description( Product $product ): string {
		$description = $product->description();
		if ( '' === trim( wp_strip_all_tags( $description ) ) ) {
			return '';
		}

		$this->enqueue_style();
		return '<div class="lcs-lemon-description">' . wp_kses_post( wpautop( $description ) ) . '</div>';
	}

	/**
	 * Buy button pointing to the official Lemon checkout URL.
	 *
	 * @param Product             $product Product.
	 * @param array<string,mixed> $args    text, size (sm|md|lg), full_width, new_tab, class.
	 */
	public function buy_button( Product $product, array $args = array() ): string {
		$args = wp_parse_args(
			$args,
			array(
				'text'       => '',
				'size'       => 'md',
				'full_width' => false,
				'new_tab'    => false,
				'class'      => '',
			)
		);

		$url = $product->buy_url();
		if ( '' === $url ) {
			return '';
		}

		$this->enqueue_style();

		$text    = '' !== trim( (string) $args['text'] ) ? (string) $args['text'] : __( 'Satın Al', 'lemon-catalog-sync' );
		$size    = in_array( $args['size'], array( 'sm', 'md', 'lg' ), true ) ? $args['size'] : 'md';
		$classes = array( 'lcs-buy-button', 'lcs-buy-button--' . $size );

		if ( ! empty( $args['full_width'] ) ) {
			$classes[] = 'lcs-buy-button--full';
		}
		if ( '' !== (string) $args['class'] ) {
			$classes[] = (string) $args['class'];
		}

		$attrs = '';

		if ( $this->settings->is_overlay_checkout() ) {
			$classes[] = 'lemonsqueezy-button';
			$url       = add_query_arg( 'embed', '1', $url );
			$this->enqueue_lemonjs();
		} elseif ( ! empty( $args['new_tab'] ) ) {
			$attrs = ' target="_blank" rel="noopener"';
		}

		return sprintf(
			'<div class="lcs-buy-button-wrap"><a href="%1$s" class="%2$s"%3$s>%4$s</a></div>',
			esc_url( $url ),
			esc_attr( implode( ' ', array_map( 'sanitize_html_class', $classes ) ) ),
			$attrs,
			esc_html( $text )
		);
	}

	/**
	 * Variant list (display only — checkout stays on the product Buy Now URL).
	 *
	 * @param Product             $product Product.
	 * @param array<string,mixed> $args    layout (list|grid), show_price, show_description.
	 */
	public function variants( Product $product, array $args = array() ): string {
		$args = wp_parse_args(
			$args,
			array(
				'layout'           => 'list',
				'show_price'       => true,
				'show_description' => true,
			)
		);

		$variants = $product->public_variants();
		if ( count( $variants ) < 1 ) {
			return '';
		}

		$this->enqueue_style();

		$layout = 'grid' === $args['layout'] ? 'grid' : 'list';
		$html   = '<ul class="lcs-variants lcs-variants--' . esc_attr( $layout ) . '">';

		foreach ( $variants as $variant ) {
			$html .= '<li class="lcs-variant">';
			$html .= '<span class="lcs-variant__name">' . esc_html( (string) ( $variant['name'] ?? '' ) ) . '</span>';

			if ( ! empty( $args['show_price'] ) ) {
				$price = $this->variant_price( $variant );
				if ( '' !== $price ) {
					$html .= '<span class="lcs-variant__price">' . esc_html( $price ) . '</span>';
				}
			}

			if ( ! empty( $args['show_description'] ) && '' !== trim( wp_strip_all_tags( (string) ( $variant['description'] ?? '' ) ) ) ) {
				$html .= '<div class="lcs-variant__description">' . wp_kses_post( (string) $variant['description'] ) . '</div>';
			}

			$html .= '</li>';
		}

		return $html . '</ul>';
	}

	/**
	 * Product meta list.
	 *
	 * @param Product  $product Product.
	 * @param string[] $items   lemon_id, status, price, variants, environment, updated.
	 */
	public function meta( Product $product, array $items = array( 'lemon_id', 'price', 'variants' ) ): string {
		$rows = array();

		foreach ( $items as $item ) {
			switch ( $item ) {
				case 'lemon_id':
					$rows[] = array( __( 'Product ID', 'lemon-catalog-sync' ), $product->lemon_id() );
					break;
				case 'status':
					$rows[] = array( __( 'Status', 'lemon-catalog-sync' ), $product->lemon_status_formatted() );
					break;
				case 'price':
					$rows[] = array( __( 'Price', 'lemon-catalog-sync' ), $this->price_text( $product ) );
					break;
				case 'variants':
					$rows[] = array( __( 'Variants', 'lemon-catalog-sync' ), (string) $product->variant_count() );
					break;
				case 'environment':
					$rows[] = array( __( 'Mode', 'lemon-catalog-sync' ), $product->is_test_mode() ? __( 'Test', 'lemon-catalog-sync' ) : __( 'Live', 'lemon-catalog-sync' ) );
					break;
				case 'updated':
					$updated = strtotime( $product->meta( Meta::UPDATED_AT ) );
					$rows[]  = array( __( 'Last updated', 'lemon-catalog-sync' ), $updated ? wp_date( get_option( 'date_format' ), $updated ) : '' );
					break;
			}
		}

		$rows = array_filter( $rows, static fn( array $row ): bool => '' !== $row[1] );
		if ( ! $rows ) {
			return '';
		}

		$this->enqueue_style();

		$html = '<dl class="lcs-product-meta">';
		foreach ( $rows as $row ) {
			$html .= '<div class="lcs-product-meta__row"><dt>' . esc_html( $row[0] ) . '</dt><dd>' . esc_html( $row[1] ) . '</dd></div>';
		}
		return $html . '</dl>';
	}

	/**
	 * Automatic product summary block (Mode 2).
	 *
	 * @param Product $product Product.
	 */
	public function summary( Product $product ): string {
		$this->enqueue_style();

		$html  = '<section class="lcs-product-summary">';
		$html .= $this->image( $product, array( 'class' => 'lcs-product-summary__media' ) );
		$html .= '<div class="lcs-product-summary__info">';
		$html .= '<h2 class="lcs-product-summary__title">' . esc_html( $product->title() ) . '</h2>';
		$html .= $this->price( $product );
		$html .= $this->description( $product );
		$html .= $this->variants( $product );
		$html .= $this->buy_button( $product );
		$html .= '</div></section>';

		/**
		 * Filters the automatic product block HTML.
		 *
		 * @param string  $html    HTML.
		 * @param Product $product Product.
		 */
		return (string) apply_filters( 'lcs_product_summary_html', $html, $product );
	}

	/**
	 * Product grid.
	 *
	 * @param WP_Query $query   Query.
	 * @param int      $columns Columns (1-6).
	 */
	public function grid( WP_Query $query, int $columns ): string {
		if ( ! $query->have_posts() ) {
			return '<p class="lcs-products-empty">' . esc_html__( 'Henüz ürün yok.', 'lemon-catalog-sync' ) . '</p>';
		}

		$this->enqueue_style();

		$columns = max( 1, min( 6, $columns ) );
		$html    = '<div class="lcs-products lcs-products--cols-' . $columns . '" style="--lcs-columns:' . $columns . '">';

		foreach ( $query->posts as $post ) {
			$product = Product::from_id( (int) ( $post->ID ?? $post ) );
			if ( ! $product ) {
				continue;
			}

			$html .= '<article class="lcs-product-card">';
			$html .= $this->image(
				$product,
				array(
					'size'  => 'medium_large',
					'class' => 'lcs-product-card__media',
					'link'  => true,
				)
			);
			$html .= '<div class="lcs-product-card__body">';
			$html .= '<h3 class="lcs-product-card__title"><a href="' . esc_url( $product->permalink() ) . '">' . esc_html( $product->title() ) . '</a></h3>';
			$html .= $this->price( $product );
			$html .= '<a class="lcs-product-card__link" href="' . esc_url( $product->permalink() ) . '">' . esc_html__( 'İncele', 'lemon-catalog-sync' ) . '</a>';
			$html .= '</div></article>';
		}

		return $html . '</div>';
	}

	/**
	 * Editor-only placeholder.
	 *
	 * @param string $message Message.
	 */
	public function placeholder( string $message ): string {
		$this->enqueue_style();
		return '<div class="lcs-placeholder">' . esc_html( $message ) . '</div>';
	}

	/*
	 * ---------------------------------------------------------------------
	 * Formatting
	 * ---------------------------------------------------------------------
	 */

	/**
	 * Variant price label, incl. subscription interval.
	 *
	 * @param array<string,mixed> $variant Variant.
	 */
	public function variant_price( array $variant ): string {
		$price = isset( $variant['price'] ) && is_numeric( $variant['price'] ) ? (int) $variant['price'] : null;

		if ( ! empty( $variant['pay_what_you_want'] ) ) {
			$min = isset( $variant['min_price'] ) && is_numeric( $variant['min_price'] ) ? (int) $variant['min_price'] : null;
			return null !== $min && $min > 0
				/* translators: %s: minimum price. */
				? sprintf( __( 'From %s (pay what you want)', 'lemon-catalog-sync' ), $this->format_money( $min ) )
				: __( 'Pay what you want', 'lemon-catalog-sync' );
		}

		if ( null === $price ) {
			return '';
		}

		$label = $this->format_money( $price );

		if ( ! empty( $variant['is_subscription'] ) && ! empty( $variant['interval'] ) ) {
			$label .= ' ' . $this->interval_label( (string) $variant['interval'], (int) ( $variant['interval_count'] ?? 1 ) );
		}

		return $label;
	}

	/**
	 * Formats cents in the store currency.
	 *
	 * @param int $cents Amount in cents.
	 */
	public function format_money( int $cents ): string {
		$currency = strtoupper( (string) $this->settings->get( 'store_currency' ) );
		$symbols  = array(
			'USD' => '$',
			'EUR' => '€',
			'GBP' => '£',
			'TRY' => '₺',
			'JPY' => '¥',
			'INR' => '₹',
			'AUD' => 'A$',
			'CAD' => 'CA$',
		);
		$decimals = 'JPY' === $currency ? 0 : 2;
		$amount   = number_format_i18n( $cents / ( 0 === $decimals ? 1 : 100 ), $decimals );
		$label    = isset( $symbols[ $currency ] ) ? $symbols[ $currency ] . $amount : $amount . ' ' . $currency;

		/**
		 * Filters a formatted variant price.
		 *
		 * @param string $label    Formatted price.
		 * @param int    $cents    Amount in cents.
		 * @param string $currency Store currency.
		 */
		return (string) apply_filters( 'lcs_format_money', $label, $cents, $currency );
	}

	/**
	 * "/ month", "/ 3 months" …
	 *
	 * @param string $interval day|week|month|year.
	 * @param int    $count    Interval count.
	 */
	private function interval_label( string $interval, int $count ): string {
		$count = max( 1, $count );
		$unit  = match ( $interval ) {
			/* translators: subscription interval unit. */
			'day'   => _n( 'day', 'days', $count, 'lemon-catalog-sync' ),
			/* translators: subscription interval unit. */
			'week'  => _n( 'week', 'weeks', $count, 'lemon-catalog-sync' ),
			/* translators: subscription interval unit. */
			'year'  => _n( 'year', 'years', $count, 'lemon-catalog-sync' ),
			/* translators: subscription interval unit. */
			default => _n( 'month', 'months', $count, 'lemon-catalog-sync' ),
		};
		return '/ ' . ( $count > 1 ? $count . ' ' : '' ) . $unit;
	}
}
