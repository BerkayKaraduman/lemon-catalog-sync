<?php
/**
 * One-time data upgrades.
 *
 * @package LCS
 */

namespace LCS;

defined( 'ABSPATH' ) || exit;

/**
 * Copies card values stored under legacy keys into the canonical
 * _lcs_compare_price / _lcs_card_subtitle / _lcs_card_badge keys.
 * Never overwrites an existing canonical value and never deletes legacy keys.
 */
final class Upgrade {

	/**
	 * Option holding the completed card meta migration revision.
	 */
	public const CARD_META_OPTION = 'lcs_card_meta_migration';

	/**
	 * Option flag: the API key option's autoload was checked.
	 */
	public const KEY_AUTOLOAD_OPTION = 'lcs_api_key_autoload_checked';

	/**
	 * Current migration revision. 1.1.1 only covered the compare price
	 * (option "lcs_compare_price_migrated"); revision 2 covers all card keys.
	 */
	private const CARD_META_REVISION = 2;

	/**
	 * Hooks.
	 */
	public function hooks(): void {
		add_action( 'admin_init', array( $this, 'maybe_migrate_card_meta' ) );
		add_action( 'admin_init', array( $this, 'maybe_secure_api_key_option' ) );
	}

	/**
	 * Once: makes sure a database API key (fallback) is not autoloaded.
	 */
	public function maybe_secure_api_key_option(): void {
		if ( get_option( self::KEY_AUTOLOAD_OPTION ) ) {
			return;
		}

		Credentials::enforce_no_autoload();
		update_option( self::KEY_AUTOLOAD_OPTION, 1, false );
	}

	/**
	 * Runs the card meta migration once per revision.
	 */
	public function maybe_migrate_card_meta(): void {
		if ( (int) get_option( self::CARD_META_OPTION, 0 ) >= self::CARD_META_REVISION ) {
			return;
		}

		$this->migrate_card_meta();
		update_option( self::CARD_META_OPTION, self::CARD_META_REVISION, false );
	}

	/**
	 * Migrates every legacy card key.
	 *
	 * @return int Number of values copied.
	 */
	public function migrate_card_meta(): int {
		$migrated = 0;
		foreach ( Meta::LEGACY_KEYS as $canonical => $legacy ) {
			$migrated += $this->migrate_key( $canonical, $legacy );
		}
		return $migrated;
	}

	/**
	 * Copies the first usable legacy value into $canonical where it is empty.
	 *
	 * @param string   $canonical Canonical meta key.
	 * @param string[] $legacy    Legacy keys in priority order.
	 * @return int Number of products migrated.
	 */
	private function migrate_key( string $canonical, array $legacy ): int {
		global $wpdb;

		$placeholders = implode( ', ', array_fill( 0, count( $legacy ), '%s' ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders -- one-time lookup; placeholders built from a constant list.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pm.post_id, pm.meta_key, pm.meta_value
				FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE p.post_type = %s AND pm.meta_key IN ( {$placeholders} )
				ORDER BY pm.meta_id ASC",
				array_merge( array( Post_Type::POST_TYPE ), $legacy )
			),
			ARRAY_A
		);
		// phpcs:enable

		$candidates = array();
		foreach ( (array) $rows as $row ) {
			$post_id = (int) $row['post_id'];
			$value   = self::sanitize( $canonical, maybe_unserialize( $row['meta_value'] ) );
			if ( '' === $value ) {
				continue;
			}
			$priority = (int) array_search( $row['meta_key'], $legacy, true );
			if ( ! isset( $candidates[ $post_id ] ) || $priority < $candidates[ $post_id ]['priority'] ) {
				$candidates[ $post_id ] = array(
					'priority' => $priority,
					'value'    => $value,
				);
			}
		}

		$migrated = 0;
		foreach ( $candidates as $post_id => $candidate ) {
			if ( '' !== trim( (string) get_post_meta( $post_id, $canonical, true ) ) ) {
				continue; // Never overwrite an existing canonical value.
			}
			update_post_meta( $post_id, $canonical, wp_slash( $candidate['value'] ) );
			++$migrated;
		}

		return $migrated;
	}

	/**
	 * Sanitizer for a canonical key.
	 *
	 * @param string $key   Canonical key.
	 * @param mixed  $value Raw value.
	 */
	private static function sanitize( string $key, $value ): string {
		return Meta::COMPARE_PRICE === $key ? Meta::sanitize_compare_price( $value ) : Meta::sanitize_card_value( $value );
	}
}
