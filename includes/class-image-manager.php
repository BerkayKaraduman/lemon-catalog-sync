<?php
/**
 * Media Library image mode: imports the Lemon image once and sets it as the
 * featured image, without duplicates and without overriding a manually chosen image.
 *
 * @package LCS
 */

namespace LCS;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Image manager.
 */
final class Image_Manager {

	private const ALLOWED_MIME = array(
		'image/jpeg' => 'jpg',
		'image/png'  => 'png',
		'image/gif'  => 'gif',
		'image/webp' => 'webp',
		'image/avif' => 'avif',
	);

	/**
	 * Ensures the product's featured image matches the Lemon image.
	 *
	 * @param int    $post_id   Product post ID.
	 * @param string $url       Lemon image URL.
	 * @param string $lemon_id  Lemon product ID.
	 * @param string $title     Product title (used for alt/filename).
	 * @return bool|WP_Error True when the featured image was changed.
	 */
	public function sync_featured_image( int $post_id, string $url, string $lemon_id, string $title ): bool|WP_Error {
		if ( '' === $url ) {
			return false;
		}

		$current = (int) get_post_thumbnail_id( $post_id );

		// A featured image chosen by a person is never replaced.
		if ( $current && ! $this->is_managed( $current ) ) {
			return false;
		}

		$source        = $this->normalize( $url );
		$attachment_id = $this->find_attachment( $source );

		if ( $current && $current === $attachment_id ) {
			return false;
		}

		if ( ! $attachment_id ) {
			$attachment_id = $this->import( $url, $source, $post_id, $lemon_id, $title );
			if ( is_wp_error( $attachment_id ) ) {
				return $attachment_id;
			}
		}

		set_post_thumbnail( $post_id, $attachment_id );
		return true;
	}

	/**
	 * Whether an attachment was imported by this plugin.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function is_managed( int $attachment_id ): bool {
		return '' !== (string) get_post_meta( $attachment_id, Meta::SOURCE_IMAGE_URL, true );
	}

	/**
	 * Existing attachment for a source URL.
	 *
	 * @param string $source Normalized URL.
	 */
	private function find_attachment( string $source ): int {
		$ids = get_posts(
			array(
				'post_type'              => 'attachment',
				'post_status'            => 'inherit',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- only runs in Media Library mode when an image changes.
				'meta_query'             => array(
					array(
						'key'   => Meta::SOURCE_IMAGE_URL,
						'value' => $source,
					),
				),
			)
		);
		return $ids ? (int) $ids[0] : 0;
	}

	/**
	 * Downloads and registers the image.
	 *
	 * @param string $url      Download URL.
	 * @param string $source   Normalized URL used for de-duplication.
	 * @param int    $post_id  Parent post.
	 * @param string $lemon_id Lemon product ID.
	 * @param string $title    Title.
	 * @return int|WP_Error Attachment ID.
	 */
	private function import( string $url, string $source, int $post_id, string $lemon_id, string $title ): int|WP_Error {
		if ( ! wp_http_validate_url( $url ) ) {
			return new WP_Error( 'lcs_image_url', __( 'Invalid Lemon image URL.', 'lemon-catalog-sync' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$tmp = download_url( $url, 30 );
		if ( is_wp_error( $tmp ) ) {
			return $tmp;
		}

		$mime = function_exists( 'wp_get_image_mime' ) ? wp_get_image_mime( $tmp ) : false;
		if ( ! $mime || ! isset( self::ALLOWED_MIME[ $mime ] ) ) {
			wp_delete_file( $tmp );
			return new WP_Error( 'lcs_image_type', __( 'The Lemon image is not a supported image type.', 'lemon-catalog-sync' ) );
		}

		$base = sanitize_title( $title );
		$file = array(
			'name'     => ( '' !== $base ? $base : 'lemon-product-' . $lemon_id ) . '.' . self::ALLOWED_MIME[ $mime ],
			'tmp_name' => $tmp,
		);

		$attachment_id = media_handle_sideload( $file, $post_id, $title );
		if ( is_wp_error( $attachment_id ) ) {
			wp_delete_file( $tmp );
			return $attachment_id;
		}

		update_post_meta( $attachment_id, Meta::SOURCE_IMAGE_URL, $source );
		update_post_meta( $attachment_id, Meta::PRODUCT_ID, $lemon_id );
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $title ) );

		return (int) $attachment_id;
	}

	/**
	 * Identity of an image URL: scheme + host + path (volatile query strings dropped).
	 *
	 * @param string $url URL.
	 */
	private function normalize( string $url ): string {
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
			return esc_url_raw( $url );
		}
		return esc_url_raw( ( $parts['scheme'] ?? 'https' ) . '://' . $parts['host'] . ( $parts['path'] ?? '' ) );
	}
}
