<?php
/**
 * Expiring sync lock shared by manual and cron syncs.
 *
 * @package LCS
 */

namespace LCS;

defined( 'ABSPATH' ) || exit;

/**
 * Transient based lock. It always expires, so a fatal error mid-sync can never
 * leave a permanent lock behind.
 */
final class Sync_Lock {

	public const KEY = 'lcs_sync_lock';
	public const TTL = 15 * MINUTE_IN_SECONDS;

	/**
	 * Token of the lock held by this request.
	 *
	 * @var string
	 */
	private string $token = '';

	/**
	 * Tries to take the lock.
	 *
	 * @param string $owner Human readable owner ("manual" / "cron").
	 */
	public function acquire( string $owner ): bool {
		$current = get_transient( self::KEY );
		if ( is_array( $current ) && ( (int) ( $current['time'] ?? 0 ) + self::TTL ) > time() ) {
			return false;
		}

		$token = wp_generate_password( 32, false );
		set_transient(
			self::KEY,
			array(
				'token' => $token,
				'owner' => sanitize_key( $owner ),
				'time'  => time(),
			),
			self::TTL
		);

		// Re-read to detect a concurrent writer that won the race.
		$check = get_transient( self::KEY );
		if ( ! is_array( $check ) || ( $check['token'] ?? '' ) !== $token ) {
			return false;
		}

		$this->token = $token;
		return true;
	}

	/**
	 * Releases the lock if this request owns it.
	 */
	public function release(): void {
		if ( '' === $this->token ) {
			return;
		}

		$current = get_transient( self::KEY );
		if ( is_array( $current ) && ( $current['token'] ?? '' ) === $this->token ) {
			delete_transient( self::KEY );
		}
		$this->token = '';
	}

	/**
	 * Current lock info, or null when unlocked.
	 *
	 * @return array{owner:string,time:int}|null
	 */
	public function status(): ?array {
		$current = get_transient( self::KEY );
		if ( ! is_array( $current ) || ( (int) ( $current['time'] ?? 0 ) + self::TTL ) <= time() ) {
			return null;
		}
		return array(
			'owner' => (string) ( $current['owner'] ?? '' ),
			'time'  => (int) $current['time'],
		);
	}

	/**
	 * Unconditionally removes the lock (deactivation / uninstall).
	 */
	public static function clear(): void {
		delete_transient( self::KEY );
	}
}
