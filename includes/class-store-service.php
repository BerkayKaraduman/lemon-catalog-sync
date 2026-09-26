<?php
/**
 * Lemon Squeezy Stores API.
 *
 * @package LCS
 */

namespace LCS;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Lists the stores the API key can access (cached).
 */
final class Store_Service {

	public const CACHE_KEY = 'lcs_stores_cache';
	private const CACHE_TTL = 12 * HOUR_IN_SECONDS;
	private const MAX_PAGES = 20;

	/**
	 * API client.
	 *
	 * @var Api_Client
	 */
	private Api_Client $api;

	/**
	 * Constructor.
	 *
	 * @param Api_Client $api API client.
	 */
	public function __construct( Api_Client $api ) {
		$this->api = $api;
	}

	/**
	 * Stores keyed by ID.
	 *
	 * @param bool $force Bypass the cache.
	 * @return array<string,array{id:string,name:string,slug:string,domain:string,url:string,currency:string}>|WP_Error
	 */
	public function get_stores( bool $force = false ): array|WP_Error {
		if ( ! $force ) {
			$cached = get_transient( self::CACHE_KEY );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$stores = array();
		$page   = 1;

		do {
			$doc = $this->api->get(
				'stores',
				array(
					'page[size]'   => 100,
					'page[number]' => $page,
				)
			);
			if ( is_wp_error( $doc ) ) {
				return $doc;
			}

			foreach ( (array) ( $doc['data'] ?? array() ) as $item ) {
				if ( ! is_array( $item ) || empty( $item['id'] ) ) {
					continue;
				}
				$attrs = is_array( $item['attributes'] ?? null ) ? $item['attributes'] : array();
				$id    = (string) $item['id'];

				$stores[ $id ] = array(
					'id'       => $id,
					'name'     => sanitize_text_field( (string) ( $attrs['name'] ?? '' ) ),
					'slug'     => sanitize_title( (string) ( $attrs['slug'] ?? '' ) ),
					'domain'   => sanitize_text_field( (string) ( $attrs['domain'] ?? '' ) ),
					'url'      => esc_url_raw( (string) ( $attrs['url'] ?? '' ) ),
					'currency' => strtoupper( sanitize_key( (string) ( $attrs['currency'] ?? 'USD' ) ) ),
				);
			}

			$last = (int) ( $doc['meta']['page']['lastPage'] ?? 1 );
			++$page;
		} while ( $page <= $last && $page <= self::MAX_PAGES );

		set_transient( self::CACHE_KEY, $stores, self::CACHE_TTL );

		return $stores;
	}

	/**
	 * Cached stores only (never triggers a request).
	 *
	 * @return array<string,array<string,string>>
	 */
	public function cached_stores(): array {
		$cached = get_transient( self::CACHE_KEY );
		return is_array( $cached ) ? $cached : array();
	}

	/**
	 * Drops the cache (e.g. after the API key changes).
	 */
	public function clear_cache(): void {
		delete_transient( self::CACHE_KEY );
	}
}
