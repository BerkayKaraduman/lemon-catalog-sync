<?php
/**
 * Read model for a synced product (used by widgets, dynamic tags, shortcodes).
 *
 * @package LCS
 */

namespace LCS;

use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Read-only wrapper around an lcs_product post and its _lcs_* meta.
 */
final class Product {

	/**
	 * Post.
	 *
	 * @var WP_Post
	 */
	private WP_Post $post;

	/**
	 * Constructor.
	 *
	 * @param WP_Post $post Product post.
	 */
	private function __construct( WP_Post $post ) {
		$this->post = $post;
	}

	/**
	 * Product by post ID.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function from_id( int $post_id ): ?self {
		$post = $post_id > 0 ? get_post( $post_id ) : null;
		return ( $post instanceof WP_Post && Post_Type::POST_TYPE === $post->post_type ) ? new self( $post ) : null;
	}

	/**
	 * The product in the current context: the loop post, then the queried object.
	 * No hardcoded IDs are needed in templates.
	 *
	 * @param int $post_id Optional explicit post ID.
	 */
	public static function current( int $post_id = 0 ): ?self {
		if ( $post_id > 0 ) {
			return self::from_id( $post_id );
		}

		/**
		 * Filters the product post ID used by LCS widgets/shortcodes/tags.
		 *
		 * @param int $post_id Post ID or 0 for automatic detection.
		 */
		$filtered = (int) apply_filters( 'lcs_current_product_id', 0 );
		if ( $filtered > 0 ) {
			return self::from_id( $filtered );
		}

		$loop_id = (int) get_the_ID();
		if ( $loop_id > 0 && Post_Type::POST_TYPE === get_post_type( $loop_id ) ) {
			return self::from_id( $loop_id );
		}

		$queried = get_queried_object();
		if ( $queried instanceof WP_Post && Post_Type::POST_TYPE === $queried->post_type ) {
			return new self( $queried );
		}

		return null;
	}

	/**
	 * Most recent product, used only so that Theme Builder templates show real
	 * data while being designed in the Elementor editor.
	 */
	public static function preview_fallback(): ?self {
		// Theme Builder / Loop Item templates: the representative preview product,
		// resolved exactly like Elementor Pro's own dynamic tags (e.g. Post Title).
		if ( Compat::is_elementor_editor() ) {
			try {
				$preview = Compat::in_dynamic_context( static fn() => self::current() );
			} catch ( \Throwable $e ) {
				$preview = null;
			}
			if ( $preview ) {
				return $preview;
			}

			$preview = self::from_id( Compat::editor_preview_post_id() );
			if ( $preview ) {
				return $preview;
			}
		}

		$ids = get_posts(
			array(
				'post_type'        => Post_Type::POST_TYPE,
				'post_status'      => array( 'publish', 'draft' ),
				'posts_per_page'   => 1,
				'orderby'          => 'date',
				'order'            => 'DESC',
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => false,
			)
		);
		return $ids ? self::from_id( (int) $ids[0] ) : null;
	}

	/*
	 * ---------------------------------------------------------------------
	 * Accessors
	 * ---------------------------------------------------------------------
	 */

	/**
	 * WordPress post.
	 */
	public function post(): WP_Post {
		return $this->post;
	}

	/**
	 * WordPress post ID.
	 */
	public function id(): int {
		return (int) $this->post->ID;
	}

	/**
	 * Post title.
	 */
	public function title(): string {
		return get_the_title( $this->post );
	}

	/**
	 * Permalink.
	 */
	public function permalink(): string {
		return (string) get_permalink( $this->post );
	}

	/**
	 * Raw meta string.
	 *
	 * @param string $key Meta key.
	 */
	public function meta( string $key ): string {
		$value = get_post_meta( $this->id(), $key, true );
		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * Lemon product ID.
	 */
	public function lemon_id(): string {
		return $this->meta( Meta::PRODUCT_ID );
	}

	/**
	 * Lemon store ID.
	 */
	public function store_id(): string {
		return $this->meta( Meta::STORE_ID );
	}

	/**
	 * Price in cents (null when unknown).
	 */
	public function price(): ?int {
		$raw = $this->meta( Meta::PRICE );
		return '' === $raw ? null : (int) $raw;
	}

	/**
	 * Price as a decimal amount (e.g. 19.0) or null.
	 */
	public function price_amount(): ?float {
		$cents = $this->price();
		return null === $cents ? null : $cents / 100;
	}

	/**
	 * Lemon formatted price, e.g. "$19.00".
	 */
	public function price_formatted(): string {
		return $this->meta( Meta::PRICE_FORMATTED );
	}

	/**
	 * Lemon formatted "from" price.
	 */
	public function from_price_formatted(): string {
		return $this->meta( Meta::FROM_PRICE_FORMATTED );
	}

	/**
	 * Lemon formatted "to" price.
	 */
	public function to_price_formatted(): string {
		return $this->meta( Meta::TO_PRICE_FORMATTED );
	}

	/**
	 * Whether from/to describe a real range.
	 */
	public function has_price_range(): bool {
		$from = $this->meta( Meta::FROM_PRICE );
		$to   = $this->meta( Meta::TO_PRICE );
		return '' !== $from && '' !== $to && (int) $from !== (int) $to;
	}

	/**
	 * Pay what you want.
	 */
	public function is_pay_what_you_want(): bool {
		return '1' === $this->meta( Meta::PAY_WHAT_YOU_WANT );
	}

	/**
	 * Official Lemon checkout URL.
	 */
	public function buy_url(): string {
		return $this->meta( Meta::BUY_NOW_URL );
	}

	/**
	 * Lemon description HTML (sanitized on output by callers).
	 */
	public function description(): string {
		return $this->meta( Meta::DESCRIPTION );
	}

	/**
	 * Lemon status: "published" or "draft".
	 */
	public function lemon_status(): string {
		return $this->meta( Meta::STATUS );
	}

	/**
	 * Human readable Lemon status.
	 */
	public function lemon_status_formatted(): string {
		$formatted = $this->meta( Meta::STATUS_FORMATTED );
		return '' !== $formatted ? $formatted : ucfirst( $this->lemon_status() );
	}

	/**
	 * Test mode product.
	 */
	public function is_test_mode(): bool {
		return '1' === $this->meta( Meta::TEST_MODE );
	}

	/**
	 * Flagged as missing from the Lemon store.
	 */
	public function is_missing(): bool {
		return '1' === $this->meta( Meta::MISSING );
	}

	/**
	 * Remote Lemon image URL (large preferred).
	 */
	public function remote_image_url(): string {
		$large = $this->meta( Meta::LARGE_THUMB_URL );
		return '' !== $large ? $large : $this->meta( Meta::THUMB_URL );
	}

	/**
	 * Featured image attachment ID (0 if none).
	 */
	public function thumbnail_id(): int {
		return (int) get_post_thumbnail_id( $this->post );
	}

	/**
	 * Image URL according to the Image Sync Mode.
	 *
	 * @param bool   $media_mode Whether Media Library mode is enabled.
	 * @param string $size       Attachment size for Media Library mode.
	 */
	public function image_url( bool $media_mode, string $size = 'large' ): string {
		if ( $media_mode && $this->thumbnail_id() ) {
			$url = wp_get_attachment_image_url( $this->thumbnail_id(), $size );
			if ( $url ) {
				return $url;
			}
		}
		return $this->remote_image_url();
	}

	/**
	 * All normalized variants.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function variants(): array {
		$variants = get_post_meta( $this->id(), Meta::VARIANTS, true );
		return is_array( $variants ) ? array_values( array_filter( $variants, 'is_array' ) ) : array();
	}

	/**
	 * Variants a visitor can buy: published ones. A product without real
	 * variants only has Lemon's hidden default ("pending") variant, which is
	 * not listed.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function public_variants(): array {
		return array_values(
			array_filter(
				$this->variants(),
				static fn( array $variant ): bool => 'published' === ( $variant['status'] ?? '' )
			)
		);
	}

	/**
	 * Number of purchasable variants (at least 1 for a product with only the default variant).
	 */
	public function variant_count(): int {
		$public = count( $this->public_variants() );
		if ( $public > 0 ) {
			return $public;
		}
		return $this->variants() ? 1 : 0;
	}

	/**
	 * Compare-at / list price as stored in _lcs_compare_price ('' when empty).
	 */
	public function compare_price(): string {
		return trim( $this->meta( Meta::COMPARE_PRICE ) );
	}

	/**
	 * Compare-at price in cents, or null when empty / not a number.
	 */
	public function compare_price_cents(): ?int {
		$amount = Meta::parse_price( $this->compare_price() );
		return null === $amount ? null : (int) round( $amount * 100 );
	}

	/**
	 * Card subtitle entered in WordPress.
	 */
	public function card_subtitle(): string {
		return trim( $this->meta( Meta::CARD_SUBTITLE ) );
	}

	/**
	 * Card badge entered in WordPress, e.g. "%40 İndirim".
	 */
	public function card_badge(): string {
		return trim( $this->meta( Meta::CARD_BADGE ) );
	}

	/**
	 * Last successful sync (unix timestamp) or 0.
	 */
	public function last_sync(): int {
		return (int) $this->meta( Meta::LAST_SYNC );
	}
}
