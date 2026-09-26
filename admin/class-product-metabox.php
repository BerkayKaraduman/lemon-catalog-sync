<?php
/**
 * Read-only "Lemon Squeezy Data" metabox and list table columns.
 *
 * @package LCS
 */

namespace LCS\Admin;

use LCS\Meta;
use LCS\Post_Type;
use LCS\Product;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Shows Lemon-controlled data without offering inputs for it.
 */
final class Product_Metabox {

	/**
	 * Hooks.
	 */
	public function hooks(): void {
		add_action( 'add_meta_boxes_' . Post_Type::POST_TYPE, array( $this, 'register' ) );
		add_filter( 'manage_' . Post_Type::POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . Post_Type::POST_TYPE . '_posts_custom_column', array( $this, 'column' ), 10, 2 );
	}

	/**
	 * Registers the metabox.
	 */
	public function register(): void {
		add_meta_box(
			'lcs-lemon-data',
			__( 'Lemon Squeezy Data', 'lemon-catalog-sync' ),
			array( $this, 'render' ),
			Post_Type::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Renders the metabox.
	 *
	 * @param WP_Post $post Post.
	 */
	public function render( $post ): void {
		$product = $post instanceof WP_Post ? Product::from_id( (int) $post->ID ) : null;

		if ( ! $product || '' === $product->lemon_id() ) {
			echo '<p>' . esc_html__( 'This product has not been synced from Lemon Squeezy. Products are created automatically by the sync; manually created entries have no Lemon data.', 'lemon-catalog-sync' ) . '</p>';
			return;
		}

		$lemon_updated = strtotime( $product->meta( Meta::UPDATED_AT ) );
		$price         = static fn( string $key ): string => $product->meta( $key . '_formatted' ) !== '' ? $product->meta( $key . '_formatted' ) : '—';

		$rows = array(
			__( 'Lemon Product ID', 'lemon-catalog-sync' ) => $product->lemon_id(),
			__( 'Store ID', 'lemon-catalog-sync' )         => $product->store_id(),
			__( 'Lemon Status', 'lemon-catalog-sync' )     => $product->lemon_status_formatted(),
			__( 'Price', 'lemon-catalog-sync' )            => $price( Meta::PRICE ),
			__( 'From Price', 'lemon-catalog-sync' )       => $price( Meta::FROM_PRICE ),
			__( 'To Price', 'lemon-catalog-sync' )         => $price( Meta::TO_PRICE ),
			__( 'Last Lemon Update', 'lemon-catalog-sync' ) => $lemon_updated ? Admin::time( $lemon_updated ) : '—',
			__( 'Last Sync', 'lemon-catalog-sync' )        => Admin::time( $product->last_sync() ),
			__( 'Test / Live', 'lemon-catalog-sync' )      => $product->is_test_mode() ? __( 'Test mode', 'lemon-catalog-sync' ) : __( 'Live', 'lemon-catalog-sync' ),
			__( 'Variant Count', 'lemon-catalog-sync' )    => (string) $product->variant_count(),
		);

		echo '<p class="lcs-managed"><span class="dashicons dashicons-lock" aria-hidden="true"></span> ' . esc_html__( 'Managed by Lemon Squeezy', 'lemon-catalog-sync' ) . '</p>';

		if ( $product->is_missing() ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'This product was not returned by Lemon Squeezy during the last complete sync, so it was set to draft. It was not deleted and its WordPress/Elementor content is intact.', 'lemon-catalog-sync' ) . '</p></div>';
		}

		echo '<table class="widefat striped lcs-data-table"><tbody>';
		foreach ( $rows as $label => $value ) {
			echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>' . esc_html( '' !== $value ? $value : '—' ) . '</td></tr>';
		}

		$buy = $product->buy_url();
		echo '<tr><th scope="row">' . esc_html__( 'Buy Now URL', 'lemon-catalog-sync' ) . '</th><td>';
		if ( '' !== $buy ) {
			echo '<a href="' . esc_url( $buy ) . '" target="_blank" rel="noopener">' . esc_html( $buy ) . '</a>';
		} else {
			echo '—';
		}
		echo '</td></tr>';

		$image = $product->remote_image_url();
		if ( '' !== $image ) {
			echo '<tr><th scope="row">' . esc_html__( 'Lemon Image', 'lemon-catalog-sync' ) . '</th><td><img class="lcs-data-table__thumb" src="' . esc_url( $image ) . '" alt="" loading="lazy" /></td></tr>';
		}
		echo '</tbody></table>';

		$variants = $product->public_variants();
		if ( $variants ) {
			$renderer = \LCS\Plugin::instance()->renderer();
			echo '<h4>' . esc_html__( 'Variants', 'lemon-catalog-sync' ) . '</h4><ul class="lcs-variant-admin-list">';
			foreach ( $variants as $variant ) {
				echo '<li><strong>' . esc_html( (string) ( $variant['name'] ?? '' ) ) . '</strong> — ' . esc_html( $renderer->variant_price( $variant ) ) . ' <code>#' . esc_html( (string) ( $variant['id'] ?? '' ) ) . '</code></li>';
			}
			echo '</ul>';
		}

		echo '<p class="description">' . esc_html__( 'These values are overwritten by every sync. Edit them in Lemon Squeezy. Title changes from Lemon are applied, but the URL slug, content, Elementor design, categories and SEO settings are never changed by the sync.', 'lemon-catalog-sync' ) . '</p>';
	}

	/**
	 * Adds list columns.
	 *
	 * @param array<string,string> $columns Columns.
	 * @return array<string,string>
	 */
	public function columns( $columns ): array {
		$columns = is_array( $columns ) ? $columns : array();
		$result  = array();
		foreach ( $columns as $key => $label ) {
			$result[ $key ] = $label;
			if ( 'title' === $key ) {
				$result['lcs_price'] = __( 'Price', 'lemon-catalog-sync' );
				$result['lcs_lemon']  = __( 'Lemon', 'lemon-catalog-sync' );
			}
		}
		return $result;
	}

	/**
	 * Renders list columns.
	 *
	 * @param string $column  Column.
	 * @param int    $post_id Post ID.
	 */
	public function column( $column, $post_id ): void {
		$product = Product::from_id( (int) $post_id );
		if ( ! $product ) {
			return;
		}

		if ( 'lcs_price' === $column ) {
			echo esc_html( \LCS\Plugin::instance()->renderer()->price_text( $product ) );
		}

		if ( 'lcs_lemon' === $column ) {
			if ( '' === $product->lemon_id() ) {
				echo '—';
				return;
			}
			echo esc_html( $product->lemon_status_formatted() );
			if ( $product->is_test_mode() ) {
				echo ' <span class="lcs-badge lcs-badge--test">' . esc_html__( 'Test', 'lemon-catalog-sync' ) . '</span>';
			}
			if ( $product->is_missing() ) {
				echo ' <span class="lcs-badge lcs-badge--missing">' . esc_html__( 'Missing from Lemon', 'lemon-catalog-sync' ) . '</span>';
			}
		}
	}
}
