<?php
/**
 * Maps Lemon Squeezy JSON:API resources to WordPress data.
 *
 * @package LCS
 */

namespace LCS;

defined( 'ABSPATH' ) || exit;

/**
 * Pure mapping: no database access. Missing or null attributes never fatal.
 */
final class Product_Mapper {

	/**
	 * Maps a "products" resource.
	 *
	 * @param array<string,mixed>            $resource JSON:API resource object.
	 * @param array<int,array<string,mixed>> $variants Normalized variants for this product.
	 * @return array{id:string,title:string,slug:string,status:string,updated_at:string,test_mode:bool,image_url:string,meta:array<string,mixed>}|null
	 */
	public function map_product( array $resource, array $variants = array() ): ?array {
		$id = isset( $resource['id'] ) && is_scalar( $resource['id'] ) ? trim( (string) $resource['id'] ) : '';
		if ( '' === $id || ( $resource['type'] ?? 'products' ) !== 'products' ) {
			return null;
		}

		$a = is_array( $resource['attributes'] ?? null ) ? $resource['attributes'] : array();

		$name   = $this->text( $a, 'name' );
		$status = 'published' === $this->text( $a, 'status' ) ? 'published' : 'draft';

		usort(
			$variants,
			static function ( array $x, array $y ): int {
				return array( $x['sort'] ?? 0, (int) ( $x['id'] ?? 0 ) ) <=> array( $y['sort'] ?? 0, (int) ( $y['id'] ?? 0 ) );
			}
		);

		$meta = array(
			Meta::PRODUCT_ID           => $id,
			Meta::STORE_ID             => $this->scalar_string( $a, 'store_id' ),
			Meta::DESCRIPTION          => wp_kses_post( (string) ( $a['description'] ?? '' ) ),
			Meta::PRICE                => $this->int_string( $a, 'price' ),
			Meta::PRICE_FORMATTED      => $this->text( $a, 'price_formatted' ),
			Meta::FROM_PRICE           => $this->int_string( $a, 'from_price' ),
			Meta::FROM_PRICE_FORMATTED => $this->text( $a, 'from_price_formatted' ),
			Meta::TO_PRICE             => $this->int_string( $a, 'to_price' ),
			Meta::TO_PRICE_FORMATTED   => $this->text( $a, 'to_price_formatted' ),
			Meta::PAY_WHAT_YOU_WANT    => $this->bool_string( $a, 'pay_what_you_want' ),
			Meta::BUY_NOW_URL          => $this->url( $a, 'buy_now_url' ),
			Meta::THUMB_URL            => $this->url( $a, 'thumb_url' ),
			Meta::LARGE_THUMB_URL      => $this->url( $a, 'large_thumb_url' ),
			Meta::STATUS               => $status,
			Meta::STATUS_FORMATTED     => $this->text( $a, 'status_formatted' ),
			Meta::CREATED_AT           => $this->text( $a, 'created_at' ),
			Meta::UPDATED_AT           => $this->text( $a, 'updated_at' ),
			Meta::TEST_MODE            => $this->bool_string( $a, 'test_mode' ),
			Meta::VARIANTS             => array_values( $variants ),
		);

		$title = '' !== $name ? $name : sprintf(
			/* translators: %s: Lemon Squeezy product ID. */
			__( 'Lemon Product #%s', 'lemon-catalog-sync' ),
			$id
		);

		$slug = sanitize_title( $this->text( $a, 'slug' ) );

		return array(
			'id'         => $id,
			'title'      => $title,
			'slug'       => '' !== $slug ? $slug : sanitize_title( $title ),
			'status'     => $status,
			'updated_at' => $meta[ Meta::UPDATED_AT ],
			'test_mode'  => '1' === $meta[ Meta::TEST_MODE ],
			'image_url'  => '' !== $meta[ Meta::LARGE_THUMB_URL ] ? $meta[ Meta::LARGE_THUMB_URL ] : $meta[ Meta::THUMB_URL ],
			'meta'       => $meta,
		);
	}

	/**
	 * Normalizes a "variants" resource from the `included` array.
	 *
	 * @param array<string,mixed> $resource JSON:API resource object.
	 * @return array<string,mixed>|null Null when it is not a usable variant.
	 */
	public function map_variant( array $resource ): ?array {
		if ( ( $resource['type'] ?? '' ) !== 'variants' || ! isset( $resource['id'] ) || ! is_scalar( $resource['id'] ) ) {
			return null;
		}

		$a          = is_array( $resource['attributes'] ?? null ) ? $resource['attributes'] : array();
		$product_id = $this->scalar_string( $a, 'product_id' );

		if ( '' === $product_id ) {
			// Fall back to the relationship linkage when the attribute is absent.
			$linked     = $resource['relationships']['product']['data']['id'] ?? '';
			$product_id = is_scalar( $linked ) ? (string) $linked : '';
		}

		if ( '' === $product_id ) {
			return null;
		}

		return array(
			'id'                   => (string) $resource['id'],
			'product_id'           => $product_id,
			'name'                 => $this->text( $a, 'name' ),
			'slug'                 => sanitize_title( $this->text( $a, 'slug' ) ),
			'description'          => wp_kses_post( (string) ( $a['description'] ?? '' ) ),
			'price'                => $this->int_or_null( $a, 'price' ),
			'is_subscription'      => ! empty( $a['is_subscription'] ),
			'interval'             => $this->text( $a, 'interval' ),
			'interval_count'       => $this->int_or_null( $a, 'interval_count' ),
			'has_free_trial'       => ! empty( $a['has_free_trial'] ),
			'trial_interval'       => $this->text( $a, 'trial_interval' ),
			'trial_interval_count' => $this->int_or_null( $a, 'trial_interval_count' ),
			'pay_what_you_want'    => ! empty( $a['pay_what_you_want'] ),
			'min_price'            => $this->int_or_null( $a, 'min_price' ),
			'suggested_price'      => $this->int_or_null( $a, 'suggested_price' ),
			'status'               => $this->text( $a, 'status' ),
			'status_formatted'     => $this->text( $a, 'status_formatted' ),
			'sort'                 => (int) ( $this->int_or_null( $a, 'sort' ) ?? 0 ),
			'test_mode'            => ! empty( $a['test_mode'] ),
			'created_at'           => $this->text( $a, 'created_at' ),
			'updated_at'           => $this->text( $a, 'updated_at' ),
		);
	}

	/**
	 * Fingerprint of everything the sync writes, used to skip unchanged products.
	 *
	 * @param array<string,mixed> $mapped Output of map_product().
	 */
	public function hash( array $mapped ): string {
		return md5( (string) wp_json_encode( array( $mapped['title'], $mapped['status'], $mapped['meta'] ) ) );
	}

	/*
	 * ---------------------------------------------------------------------
	 * Attribute helpers
	 * ---------------------------------------------------------------------
	 */

	/**
	 * Sanitized single-line text.
	 *
	 * @param array<string,mixed> $a   Attributes.
	 * @param string              $key Key.
	 */
	private function text( array $a, string $key ): string {
		return isset( $a[ $key ] ) && is_scalar( $a[ $key ] ) ? sanitize_text_field( (string) $a[ $key ] ) : '';
	}

	/**
	 * Scalar as string (IDs).
	 *
	 * @param array<string,mixed> $a   Attributes.
	 * @param string              $key Key.
	 */
	private function scalar_string( array $a, string $key ): string {
		return isset( $a[ $key ] ) && is_scalar( $a[ $key ] ) ? preg_replace( '/[^0-9A-Za-z_-]/', '', (string) $a[ $key ] ) : '';
	}

	/**
	 * Integer or null.
	 *
	 * @param array<string,mixed> $a   Attributes.
	 * @param string              $key Key.
	 */
	private function int_or_null( array $a, string $key ): ?int {
		return isset( $a[ $key ] ) && is_numeric( $a[ $key ] ) ? (int) $a[ $key ] : null;
	}

	/**
	 * Integer as string, '' when missing (keeps "0" distinct from "unknown").
	 *
	 * @param array<string,mixed> $a   Attributes.
	 * @param string              $key Key.
	 */
	private function int_string( array $a, string $key ): string {
		$int = $this->int_or_null( $a, $key );
		return null === $int ? '' : (string) $int;
	}

	/**
	 * Boolean as "1"/"0".
	 *
	 * @param array<string,mixed> $a   Attributes.
	 * @param string              $key Key.
	 */
	private function bool_string( array $a, string $key ): string {
		return ! empty( $a[ $key ] ) ? '1' : '0';
	}

	/**
	 * Sanitized http(s) URL.
	 *
	 * @param array<string,mixed> $a   Attributes.
	 * @param string              $key Key.
	 */
	private function url( array $a, string $key ): string {
		return isset( $a[ $key ] ) && is_string( $a[ $key ] ) ? esc_url_raw( $a[ $key ], array( 'http', 'https' ) ) : '';
	}
}
