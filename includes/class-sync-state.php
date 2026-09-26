<?php
/**
 * Persistent sync status (attempts, successes, errors, history).
 *
 * @package LCS
 */

namespace LCS;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Stored in a non-autoloaded option. Contains no secrets.
 */
final class Sync_State {

	public const OPTION = 'lcs_sync_state';
	private const HISTORY_SIZE = 20;

	/**
	 * Defaults.
	 *
	 * @return array<string,mixed>
	 */
	private static function defaults(): array {
		return array(
			'last_attempt'         => 0,
			'last_attempt_trigger' => '',
			'last_success'         => 0,
			'last_result'          => array(),
			'last_error'           => '',
			'last_error_time'      => 0,
			'rate_limited_until'   => 0,
			'environment'          => '',
			'connection'           => array(),
			'history'              => array(),
		);
	}

	/**
	 * Full state.
	 *
	 * @return array<string,mixed>
	 */
	public function get(): array {
		$stored = get_option( self::OPTION, array() );
		return array_merge( self::defaults(), is_array( $stored ) ? $stored : array() );
	}

	/**
	 * Records the start of an attempt.
	 *
	 * @param string $trigger manual|cron.
	 */
	public function record_attempt( string $trigger ): void {
		$this->save(
			array(
				'last_attempt'         => time(),
				'last_attempt_trigger' => $trigger,
			)
		);
	}

	/**
	 * Records a successful run.
	 *
	 * @param array<string,mixed> $result  Stats.
	 * @param string              $trigger manual|cron.
	 */
	public function record_success( array $result, string $trigger ): void {
		$state = $this->get();

		$this->save(
			array(
				'last_success'       => time(),
				'last_result'        => $result,
				'last_error'         => '',
				'rate_limited_until' => 0,
				'environment'        => (string) ( $result['environment'] ?? $state['environment'] ),
				'history'            => $this->push_history(
					$state,
					array(
						'time'    => time(),
						'trigger' => $trigger,
						'ok'      => true,
						'message' => $this->summary( $result ),
					)
				),
			)
		);
	}

	/**
	 * Records a failed run. Products are never touched on failure.
	 *
	 * @param WP_Error $error   Error.
	 * @param string   $trigger manual|cron.
	 */
	public function record_failure( WP_Error $error, string $trigger ): void {
		$state   = $this->get();
		$data    = $error->get_error_data();
		$changes = array(
			'last_error'      => $error->get_error_message(),
			'last_error_time' => time(),
			'history'         => $this->push_history(
				$state,
				array(
					'time'    => time(),
					'trigger' => $trigger,
					'ok'      => false,
					'message' => $error->get_error_message(),
				)
			),
		);

		if ( is_array( $data ) && ! empty( $data['retry_after'] ) ) {
			$changes['rate_limited_until'] = time() + (int) $data['retry_after'];
		}

		$this->save( $changes );
	}

	/**
	 * Records the result of "Test Connection".
	 *
	 * @param bool   $ok      Success.
	 * @param string $message Message.
	 */
	public function record_connection( bool $ok, string $message ): void {
		$this->save(
			array(
				'connection' => array(
					'ok'      => $ok,
					'message' => $message,
					'time'    => time(),
				),
			)
		);
	}

	/**
	 * Clears the connection test result (key changed).
	 */
	public function reset_connection(): void {
		$this->save( array( 'connection' => array() ) );
	}

	/**
	 * Seconds until the rate limit expires (0 when not limited).
	 */
	public function rate_limit_remaining(): int {
		return max( 0, (int) $this->get()['rate_limited_until'] - time() );
	}

	/**
	 * One line summary of a result.
	 *
	 * @param array<string,mixed> $r Result.
	 */
	public function summary( array $r ): string {
		return sprintf(
			/* translators: 1: total, 2: created, 3: updated, 4: drafted, 5: unchanged, 6: failed. */
			__( '%1$d products: %2$d created, %3$d updated, %4$d drafted, %5$d unchanged, %6$d failed.', 'lemon-catalog-sync' ),
			(int) ( $r['total'] ?? 0 ),
			(int) ( $r['created'] ?? 0 ),
			(int) ( $r['updated'] ?? 0 ),
			(int) ( $r['drafted'] ?? 0 ),
			(int) ( $r['unchanged'] ?? 0 ),
			(int) ( $r['failed'] ?? 0 )
		);
	}

	/**
	 * Adds a history entry.
	 *
	 * @param array<string,mixed> $state State.
	 * @param array<string,mixed> $entry Entry.
	 * @return array<int,array<string,mixed>>
	 */
	private function push_history( array $state, array $entry ): array {
		$history = is_array( $state['history'] ) ? $state['history'] : array();
		array_unshift( $history, $entry );
		return array_slice( $history, 0, self::HISTORY_SIZE );
	}

	/**
	 * Persists changes.
	 *
	 * @param array<string,mixed> $changes Changes.
	 */
	private function save( array $changes ): void {
		update_option( self::OPTION, array_merge( $this->get(), $changes ), false );
	}
}
