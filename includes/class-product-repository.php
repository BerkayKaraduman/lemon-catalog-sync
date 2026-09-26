<?php
/**
 * Persistence for synced products.
 *
 * This is the only class that writes product data during sync. It only ever
 * touches: post_title, post_status (plus the date/slug bookkeeping WordPress
 * needs when a draft is published) and meta keys starting with "_lcs_".
 *
 * @package LCS
 */

namespace LCS;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Product repository.
 */
final class Product_Repository {

	/**
	 * Every existing lcs_product that has a Lemon product ID, keyed by that ID.
	 * Includes trashed posts so a trashed product is never re-created as a duplicate.
	 * One query; no full meta cache priming (Elementor data can be large).
	 *
	 * @return array{map:array<string,array{post_id:int,status:string,store_id:string,hash:string,updated_at:string,missing:bool}>,duplicates:array<string,int[]>}
	 */
	public function index_by_lemon_id(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- one indexed lookup per sync; caching would be stale by definition.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID AS post_id, p.post_status, pid.meta_value AS lemon_id,
					store.meta_value AS store_id, hash.meta_value AS sync_hash,
					upd.meta_value AS updated_at, missing.meta_value AS missing
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pid ON pid.post_id = p.ID AND pid.meta_key = %s
				LEFT JOIN {$wpdb->postmeta} store ON store.post_id = p.ID AND store.meta_key = %s
				LEFT JOIN {$wpdb->postmeta} hash ON hash.post_id = p.ID AND hash.meta_key = %s
				LEFT JOIN {$wpdb->postmeta} upd ON upd.post_id = p.ID AND upd.meta_key = %s
				LEFT JOIN {$wpdb->postmeta} missing ON missing.post_id = p.ID AND missing.meta_key = %s
				WHERE p.post_type = %s AND p.post_status <> 'auto-draft'
				ORDER BY p.ID ASC",
				Meta::PRODUCT_ID,
				Meta::STORE_ID,
				Meta::SYNC_HASH,
				Meta::UPDATED_AT,
				Meta::MISSING,
				Post_Type::POST_TYPE
			),
			ARRAY_A
		);

		$map        = array();
		$duplicates = array();

		foreach ( (array) $rows as $row ) {
			$lemon_id = trim( (string) $row['lemon_id'] );
			if ( '' === $lemon_id ) {
				continue;
			}

			if ( isset( $map[ $lemon_id ] ) ) {
				// Legacy duplicate: the oldest post stays canonical and is the only one synced.
				$duplicates[ $lemon_id ][] = (int) $row['post_id'];
				continue;
			}

			$map[ $lemon_id ] = array(
				'post_id'    => (int) $row['post_id'],
				'status'     => (string) $row['post_status'],
				'store_id'   => (string) $row['store_id'],
				'hash'       => (string) $row['sync_hash'],
				'updated_at' => (string) $row['updated_at'],
				'missing'    => '1' === (string) $row['missing'],
			);
		}

		return array(
			'map'        => $map,
			'duplicates' => $duplicates,
		);
	}

	/**
	 * Creates a new product post. post_content and post_excerpt stay empty:
	 * they belong to the WordPress/Elementor author.
	 *
	 * @param array<string,mixed> $mapped Mapped product.
	 * @param string              $hash   Sync hash.
	 * @return int|WP_Error Post ID.
	 */
	public function create( array $mapped, string $hash ): int|WP_Error {
		$status = 'published' === $mapped['status'] ? 'publish' : 'draft';

		// Always reserve a slug that is unique for a *published* post, so a draft
		// published later never collides with another product.
		$slug = wp_unique_post_slug( (string) $mapped['slug'], 0, 'publish', Post_Type::POST_TYPE, 0 );

		$meta                    = $this->own_meta( $mapped['meta'] );
		$meta[ Meta::SYNC_HASH ] = $hash;
		$meta[ Meta::LAST_SYNC ] = time();

		$post_id = wp_insert_post(
			wp_slash(
				array(
					'post_type'      => Post_Type::POST_TYPE,
					'post_title'     => (string) $mapped['title'],
					'post_name'      => $slug,
					'post_status'    => $status,
					'post_content'   => '',
					'post_excerpt'   => '',
					'post_author'    => $this->default_author(),
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
					// Written atomically with the post so an interrupted sync can never leave
					// a post without its Lemon ID (which would cause a duplicate later).
					'meta_input'     => $meta,
				)
			),
			true
		);

		return is_wp_error( $post_id ) ? $post_id : (int) $post_id;
	}

	/**
	 * Updates only post_title and/or post_status with a direct, column-scoped
	 * query. wp_update_post() is deliberately avoided: it re-saves post_content
	 * through content filters (e.g. kses when cron runs without a user), which
	 * could alter hand-made or Elementor-generated content.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string,string> $fields  'post_title' and/or 'post_status'.
	 * @return bool Whether anything changed.
	 */
	public function update_post_fields( int $post_id, array $fields ): bool {
		global $wpdb;

		$before = get_post( $post_id );
		if ( ! $before ) {
			return false;
		}

		$data = array();

		if ( isset( $fields['post_title'] ) && $fields['post_title'] !== $before->post_title ) {
			$data['post_title'] = $fields['post_title'];
		}

		if ( isset( $fields['post_status'] ) && $fields['post_status'] !== $before->post_status ) {
			$data['post_status'] = $fields['post_status'];

			if ( 'publish' === $fields['post_status'] ) {
				if ( '0000-00-00 00:00:00' === $before->post_date_gmt ) {
					$data['post_date']     = current_time( 'mysql' );
					$data['post_date_gmt'] = current_time( 'mysql', true );
				}
				if ( '' === $before->post_name ) {
					$title             = $data['post_title'] ?? $before->post_title;
					$data['post_name'] = wp_unique_post_slug( sanitize_title( $title ), $post_id, 'publish', Post_Type::POST_TYPE, 0 );
				}
			}
		}

		if ( ! $data ) {
			return false;
		}

		$data['post_modified']     = current_time( 'mysql' );
		$data['post_modified_gmt'] = current_time( 'mysql', true );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- intentional column-scoped update, cache is cleaned below.
		$updated = $wpdb->update( $wpdb->posts, $data, array( 'ID' => $post_id ) );
		if ( false === $updated ) {
			return false;
		}

		clean_post_cache( $post_id );

		if ( isset( $data['post_status'] ) ) {
			$after = get_post( $post_id );
			if ( $after ) {
				// Lets sitemaps, caches and SEO plugins react to publish/unpublish.
				wp_transition_post_status( $after->post_status, $before->post_status, $after );
			}
		}

		return true;
	}

	/**
	 * Writes meta. Anything outside the "_lcs_" namespace is silently refused,
	 * which guarantees _elementor_*, _wpseo_*, rank_math_* etc. are never touched.
	 *
	 * @param int                 $post_id Post ID.
	 * @param array<string,mixed> $meta    Meta.
	 */
	public function write_meta( int $post_id, array $meta ): void {
		foreach ( $this->own_meta( $meta ) as $key => $value ) {
			update_post_meta( $post_id, $key, wp_slash( $value ) );
		}
	}

	/**
	 * Removes one of our own meta keys.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 */
	public function delete_meta( int $post_id, string $key ): void {
		if ( Meta::is_own( $key ) ) {
			delete_post_meta( $post_id, $key );
		}
	}

	/**
	 * Filters a meta array down to _lcs_* keys.
	 *
	 * @param array<string,mixed> $meta Meta.
	 * @return array<string,mixed>
	 */
	private function own_meta( array $meta ): array {
		return array_filter( $meta, array( Meta::class, 'is_own' ), ARRAY_FILTER_USE_KEY );
	}

	/**
	 * Author for new posts: the current user, or the first administrator during cron.
	 */
	private function default_author(): int {
		$user_id = get_current_user_id();
		if ( $user_id > 0 ) {
			return $user_id;
		}

		$admins = get_users(
			array(
				'role'    => 'administrator',
				'number'  => 1,
				'orderby' => 'ID',
				'order'   => 'ASC',
				'fields'  => 'ID',
			)
		);

		return $admins ? (int) $admins[0] : 0;
	}
}
