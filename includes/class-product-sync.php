<?php
/**
 * Product sync service. Shared by the manual "Sync Now" action and WP-Cron.
 *
 * @package LCS
 */

namespace LCS;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Pulls every product page from Lemon Squeezy and upserts lcs_product posts.
 *
 * Safety rules:
 * - Lemon product ID is the only identity; one post per ID.
 * - Only post_title, post_status and _lcs_* meta are ever written.
 * - post_name, post_content, post_excerpt, terms, Elementor and SEO data are never written.
 * - Missing-product detection runs only after a complete, error-free fetch of all pages.
 * - A failed run changes no product.
 */
final class Product_Sync {

	private const PAGE_SIZE = 100;
	private const MAX_PAGES = 500;

	/**
	 * Dependencies.
	 *
	 * @var Api_Client
	 */
	private Api_Client $api;

	/**
	 * Settings.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Repository.
	 *
	 * @var Product_Repository
	 */
	private Product_Repository $repository;

	/**
	 * Mapper.
	 *
	 * @var Product_Mapper
	 */
	private Product_Mapper $mapper;

	/**
	 * Images.
	 *
	 * @var Image_Manager
	 */
	private Image_Manager $images;

	/**
	 * State.
	 *
	 * @var Sync_State
	 */
	private Sync_State $state;

	/**
	 * Lock.
	 *
	 * @var Sync_Lock
	 */
	private Sync_Lock $lock;

	/**
	 * Constructor.
	 *
	 * @param Api_Client         $api        API client.
	 * @param Settings           $settings   Settings.
	 * @param Product_Repository $repository Repository.
	 * @param Product_Mapper     $mapper     Mapper.
	 * @param Image_Manager      $images     Image manager.
	 * @param Sync_State         $state      State.
	 * @param Sync_Lock          $lock       Lock.
	 */
	public function __construct( Api_Client $api, Settings $settings, Product_Repository $repository, Product_Mapper $mapper, Image_Manager $images, Sync_State $state, Sync_Lock $lock ) {
		$this->api        = $api;
		$this->settings   = $settings;
		$this->repository = $repository;
		$this->mapper     = $mapper;
		$this->images     = $images;
		$this->state      = $state;
		$this->lock       = $lock;
	}

	/**
	 * Runs a full sync.
	 *
	 * @param string $trigger manual|cron.
	 * @return array<string,mixed>|WP_Error Stats or error.
	 */
	public function run( string $trigger = 'manual' ): array|WP_Error {
		if ( ! $this->settings->has_api_key() ) {
			return new WP_Error( 'lcs_no_api_key', __( 'Add your Lemon Squeezy API key before syncing.', 'lemon-catalog-sync' ) );
		}

		$store_id = $this->settings->store_id();
		if ( '' === $store_id ) {
			return new WP_Error( 'lcs_no_store', __( 'Select a Lemon Squeezy store before syncing.', 'lemon-catalog-sync' ) );
		}

		if ( ! $this->lock->acquire( $trigger ) ) {
			return new WP_Error( 'lcs_locked', __( 'Another sync is already running. Try again in a few minutes.', 'lemon-catalog-sync' ) );
		}

		$this->state->record_attempt( $trigger );

		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 600 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- long running sync.
		}
		wp_raise_memory_limit( 'admin' );

		try {
			/**
			 * Fires before a sync starts.
			 *
			 * @param string $trigger  manual|cron.
			 * @param string $store_id Store ID.
			 */
			do_action( 'lcs_before_sync', $trigger, $store_id );

			$fetched = $this->fetch_all( $store_id );
			if ( is_wp_error( $fetched ) ) {
				$this->state->record_failure( $fetched, $trigger );
				return $fetched;
			}

			$result = $this->apply( $fetched, $store_id );
			$this->state->record_success( $result, $trigger );

			/**
			 * Fires after a successful sync.
			 *
			 * @param array  $result  Stats.
			 * @param string $trigger manual|cron.
			 */
			do_action( 'lcs_after_sync', $result, $trigger );

			return $result;
		} catch ( \Throwable $e ) {
			$error = new WP_Error(
				'lcs_sync_exception',
				sprintf(
					/* translators: %s: error message. */
					__( 'Sync aborted by an unexpected error: %s', 'lemon-catalog-sync' ),
					Credentials::redact( $e->getMessage() )
				)
			);
			$this->state->record_failure( $error, $trigger );
			return $error;
		} finally {
			$this->lock->release();
		}
	}

	/**
	 * Fetches every products page (with included variants) for a store.
	 *
	 * @param string $store_id Store ID.
	 * @return array{products:array<string,array<string,mixed>>,variants:array<string,array<string,array<string,mixed>>>,complete:bool,reported_total:int}|WP_Error
	 */
	private function fetch_all( string $store_id ): array|WP_Error {
		$products = array();
		$variants = array();
		$totals   = array();
		$page     = 1;

		do {
			$doc = $this->api->get(
				'products',
				array(
					'filter[store_id]' => $store_id,
					'include'          => 'variants',
					'page[size]'       => self::PAGE_SIZE,
					'page[number]'     => $page,
				)
			);

			// Any error (network, 401, 429, 5xx, bad JSON) aborts the whole run.
			if ( is_wp_error( $doc ) ) {
				return $doc;
			}

			if ( ! isset( $doc['data'] ) || ! is_array( $doc['data'] ) ) {
				return new WP_Error( 'lcs_invalid_response', __( 'Lemon Squeezy returned a products response without a data array.', 'lemon-catalog-sync' ) );
			}

			foreach ( $doc['data'] as $item ) {
				if ( ! is_array( $item ) || ( $item['type'] ?? '' ) !== 'products' || empty( $item['id'] ) ) {
					continue;
				}
				$item_store = (string) ( $item['attributes']['store_id'] ?? $store_id );
				if ( $item_store !== $store_id ) {
					continue; // Defensive: never import another store's product.
				}
				$products[ (string) $item['id'] ] = $item;
			}

			foreach ( (array) ( $doc['included'] ?? array() ) as $included ) {
				if ( ! is_array( $included ) ) {
					continue;
				}
				$variant = $this->mapper->map_variant( $included );
				if ( $variant ) {
					$variants[ $variant['product_id'] ][ $variant['id'] ] = $variant;
				}
			}

			$page_meta = is_array( $doc['meta']['page'] ?? null ) ? $doc['meta']['page'] : array();
			$current   = (int) ( $page_meta['currentPage'] ?? $page );
			$last      = isset( $page_meta['lastPage'] ) ? (int) $page_meta['lastPage'] : null;

			if ( isset( $page_meta['total'] ) ) {
				$totals[] = (int) $page_meta['total'];
			}

			$has_next = null !== $last ? $current < $last : ! empty( $doc['links']['next'] );
			++$page;

			if ( $has_next && $page > self::MAX_PAGES ) {
				return new WP_Error( 'lcs_pagination_limit', __( 'Pagination did not finish within the safety limit. No products were changed.', 'lemon-catalog-sync' ) );
			}
		} while ( $has_next );

		$reported_total = $totals ? min( $totals ) : count( $products );

		return array(
			'products'       => $products,
			'variants'       => $variants,
			// If items shifted between pages we may have skipped one; never treat that as "missing".
			'complete'       => count( $products ) >= $reported_total,
			'reported_total' => $reported_total,
		);
	}

	/**
	 * Applies fetched data to WordPress.
	 *
	 * @param array<string,mixed> $fetched  Output of fetch_all().
	 * @param string              $store_id Store ID.
	 * @return array<string,mixed> Stats.
	 */
	private function apply( array $fetched, string $store_id ): array {
		$index    = $this->repository->index_by_lemon_id();
		$existing = $index['map'];

		$stats = array(
			'total'             => count( $fetched['products'] ),
			'created'           => 0,
			'updated'           => 0,
			'drafted'           => 0,
			'unchanged'         => 0,
			'failed'            => 0,
			'missing'           => 0,
			'image_errors'      => 0,
			'duplicates'        => count( $index['duplicates'] ),
			'missing_detection' => '',
			'errors'            => array(),
			'environment'       => '',
		);

		$any_test = false;

		foreach ( $fetched['products'] as $lemon_id => $resource ) {
			try {
				$mapped = $this->mapper->map_product( $resource, array_values( $fetched['variants'][ $lemon_id ] ?? array() ) );
				if ( ! $mapped ) {
					++$stats['failed'];
					continue;
				}

				$any_test = $any_test || $mapped['test_mode'];
				$outcome  = isset( $existing[ $lemon_id ] )
					? $this->update_existing( $existing[ $lemon_id ], $mapped )
					: $this->create_new( $mapped );

				foreach ( $outcome as $key => $count ) {
					$stats[ $key ] += $count;
				}
			} catch ( \Throwable $e ) {
				++$stats['failed'];
				$stats['errors'][] = sprintf( '#%s: %s', $lemon_id, $e->getMessage() );
			}
		}

		if ( $stats['total'] > 0 ) {
			$stats['environment'] = $any_test ? 'test' : 'live';
		}

		$stats['missing_detection'] = $this->detect_missing( $existing, $fetched, $store_id, $stats );
		$stats['errors']            = array_slice( $stats['errors'], 0, 10 );

		return $stats;
	}

	/**
	 * Creates a product that WordPress does not know yet.
	 *
	 * @param array<string,mixed> $mapped Mapped product.
	 * @return array<string,int> Stat increments.
	 */
	private function create_new( array $mapped ): array {
		$post_id = $this->repository->create( $mapped, $this->mapper->hash( $mapped ) );
		if ( is_wp_error( $post_id ) ) {
			throw new \RuntimeException( esc_html( $post_id->get_error_message() ) );
		}

		$stats = array( 'created' => 1 );

		if ( $this->settings->is_media_image_mode() ) {
			$stats['image_errors'] = $this->sync_image( $post_id, $mapped );
		}

		/**
		 * Fires after a product post was created from Lemon Squeezy.
		 *
		 * @param int   $post_id Post ID.
		 * @param array $mapped  Mapped Lemon data.
		 */
		do_action( 'lcs_product_created', $post_id, $mapped );

		return $stats;
	}

	/**
	 * Updates an already-synced product in place.
	 *
	 * @param array<string,mixed> $row    Index row.
	 * @param array<string,mixed> $mapped Mapped product.
	 * @return array<string,int> Stat increments.
	 */
	private function update_existing( array $row, array $mapped ): array {
		$post_id = (int) $row['post_id'];
		$hash    = $this->mapper->hash( $mapped );
		$same    = $row['hash'] === $hash && $row['updated_at'] === $mapped['updated_at'];

		$target_status = $this->target_status( (string) $row['status'], (string) $mapped['status'] );

		// Title + status are always reconciled (cheap no-op when equal).
		$post_changed = $this->repository->update_post_fields(
			$post_id,
			array(
				'post_title'  => (string) $mapped['title'],
				'post_status' => $target_status,
			)
		);

		if ( ! $same ) {
			$meta                    = $mapped['meta'];
			$meta[ Meta::SYNC_HASH ] = $hash;
			$this->repository->write_meta( $post_id, $meta );
		}

		if ( $row['missing'] ) {
			$this->repository->delete_meta( $post_id, Meta::MISSING );
		}

		$this->repository->write_meta( $post_id, array( Meta::LAST_SYNC => time() ) );

		$stats = array();

		if ( $this->settings->is_media_image_mode() && ( ! $same || ! has_post_thumbnail( $post_id ) ) ) {
			$stats['image_errors'] = $this->sync_image( $post_id, $mapped );
		}

		$drafted = 'draft' === $target_status && 'publish' === $row['status'];

		if ( $drafted ) {
			$stats['drafted'] = 1;
		} elseif ( $same && ! $post_changed && ! $row['missing'] ) {
			$stats['unchanged'] = 1;
		} else {
			$stats['updated'] = 1;
		}

		if ( ! $same || $post_changed ) {
			/**
			 * Fires after a product post was updated from Lemon Squeezy.
			 *
			 * @param int   $post_id Post ID.
			 * @param array $mapped  Mapped Lemon data.
			 */
			do_action( 'lcs_product_updated', $post_id, $mapped );
		}

		return $stats;
	}

	/**
	 * WordPress status for a Lemon status. Only publish <-> draft is managed;
	 * trash, private, pending and scheduled posts are the site owner's decision.
	 *
	 * @param string $current WordPress status.
	 * @param string $lemon   Lemon status.
	 */
	private function target_status( string $current, string $lemon ): string {
		if ( ! in_array( $current, array( 'publish', 'draft' ), true ) ) {
			return $current;
		}
		return 'published' === $lemon ? 'publish' : 'draft';
	}

	/**
	 * Media Library image handling. Image problems never fail the product.
	 *
	 * @param int                 $post_id Post ID.
	 * @param array<string,mixed> $mapped  Mapped product.
	 * @return int 1 when an image error occurred.
	 */
	private function sync_image( int $post_id, array $mapped ): int {
		$result = $this->images->sync_featured_image( $post_id, (string) $mapped['image_url'], (string) $mapped['id'], (string) $mapped['title'] );
		return is_wp_error( $result ) ? 1 : 0;
	}

	/**
	 * Drafts (never deletes) products that disappeared from the selected store.
	 *
	 * @param array<string,array<string,mixed>> $existing Index before this run.
	 * @param array<string,mixed>               $fetched  Fetch result.
	 * @param string                            $store_id Store ID.
	 * @param array<string,mixed>               $stats    Stats (by reference).
	 * @return string Outcome description.
	 */
	private function detect_missing( array $existing, array $fetched, string $store_id, array &$stats ): string {
		if ( empty( $fetched['complete'] ) ) {
			return 'skipped: incomplete pagination';
		}

		$candidates = array();
		foreach ( $existing as $lemon_id => $row ) {
			if ( isset( $fetched['products'][ $lemon_id ] ) ) {
				continue;
			}
			// Only products that came from this store; switching stores never unpublishes the old catalog.
			if ( '' !== $row['store_id'] && $row['store_id'] !== $store_id ) {
				continue;
			}
			$candidates[ $lemon_id ] = $row;
		}

		if ( ! $candidates ) {
			return 'ran';
		}

		// An empty catalog while we know products exist is far more likely an API/account problem.
		if ( 0 === count( $fetched['products'] ) ) {
			return 'skipped: empty catalog response';
		}

		/**
		 * Allows disabling missing-product detection.
		 *
		 * @param bool  $enabled    Enabled.
		 * @param array $candidates Products that would be flagged.
		 */
		if ( ! apply_filters( 'lcs_enable_missing_detection', true, $candidates ) ) {
			return 'skipped: disabled by filter';
		}

		foreach ( $candidates as $lemon_id => $row ) {
			$post_id = (int) $row['post_id'];

			if ( ! $row['missing'] ) {
				$this->repository->write_meta( $post_id, array( Meta::MISSING => '1' ) );
			}

			if ( 'publish' === $row['status'] && $this->repository->update_post_fields( $post_id, array( 'post_status' => 'draft' ) ) ) {
				++$stats['drafted'];
			}

			++$stats['missing'];

			if ( ! $row['missing'] ) {
				/**
				 * Fires once when a product is no longer returned by Lemon Squeezy.
				 *
				 * @param int    $post_id  Post ID.
				 * @param string $lemon_id Lemon product ID.
				 */
				do_action( 'lcs_product_marked_missing', $post_id, (string) $lemon_id );
			}
		}

		return 'ran';
	}
}
