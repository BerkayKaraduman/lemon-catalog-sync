<?php
/**
 * WP-Cron scheduling for automatic sync.
 *
 * @package LCS
 */

namespace LCS;

defined( 'ABSPATH' ) || exit;

/**
 * Keeps exactly one scheduled event matching the chosen frequency.
 */
final class Cron {

	public const HOOK            = 'lcs_cron_sync';
	public const FIFTEEN_MINUTES = 'lcs_every_15_minutes';

	/**
	 * Settings.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Sync service (lazy, only resolved when the event fires).
	 *
	 * @var callable(): Product_Sync
	 */
	private $sync_factory;

	/**
	 * State.
	 *
	 * @var Sync_State
	 */
	private Sync_State $state;

	/**
	 * Constructor.
	 *
	 * @param Settings   $settings     Settings.
	 * @param callable   $sync_factory Returns the Product_Sync service.
	 * @param Sync_State $state        State.
	 */
	public function __construct( Settings $settings, callable $sync_factory, Sync_State $state ) {
		$this->settings     = $settings;
		$this->sync_factory = $sync_factory;
		$this->state        = $state;
	}

	/**
	 * Hooks.
	 */
	public function hooks(): void {
		add_filter( 'cron_schedules', array( self::class, 'add_schedules' ) ); // phpcs:ignore WordPress.WP.CronInterval -- 15 minutes is an explicit user choice.
		add_action( self::HOOK, array( $this, 'run' ) );
		add_action( 'init', array( $this, 'ensure_schedule' ), 20 );
	}

	/**
	 * Adds the 15 minute interval.
	 *
	 * @param array<string,array<string,mixed>> $schedules Schedules.
	 * @return array<string,array<string,mixed>>
	 */
	public static function add_schedules( $schedules ): array {
		$schedules = is_array( $schedules ) ? $schedules : array();
		if ( ! isset( $schedules[ self::FIFTEEN_MINUTES ] ) ) {
			$schedules[ self::FIFTEEN_MINUTES ] = array(
				'interval' => 15 * MINUTE_IN_SECONDS,
				'display'  => __( 'Every 15 Minutes (Lemon Catalog Sync)', 'lemon-catalog-sync' ),
			);
		}
		return $schedules;
	}

	/**
	 * Maps the setting to a WP-Cron recurrence (null = disabled).
	 *
	 * @param string $frequency Setting value.
	 */
	public static function recurrence( string $frequency ): ?string {
		return match ( $frequency ) {
			'15min'      => self::FIFTEEN_MINUTES,
			'hourly'     => 'hourly',
			'twicedaily' => 'twicedaily',
			'daily'      => 'daily',
			default      => null,
		};
	}

	/**
	 * Makes the scheduled event match the setting. Idempotent: never adds a
	 * second event (wp_next_scheduled / wp_get_scheduled_event guard).
	 */
	public function ensure_schedule(): void {
		self::sync_schedule( (string) $this->settings->get( 'sync_frequency' ) );
	}

	/**
	 * Static variant used by the activator.
	 *
	 * @param string $frequency Setting value.
	 */
	public static function sync_schedule( string $frequency ): void {
		$desired = self::recurrence( $frequency );
		$event   = wp_get_scheduled_event( self::HOOK );

		if ( null === $desired ) {
			if ( $event ) {
				wp_clear_scheduled_hook( self::HOOK );
			}
			return;
		}

		if ( $event && $event->schedule === $desired ) {
			return;
		}

		if ( $event ) {
			wp_clear_scheduled_hook( self::HOOK );
		}

		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, $desired, self::HOOK );
		}
	}

	/**
	 * Cron callback.
	 */
	public function run(): void {
		if ( null === self::recurrence( (string) $this->settings->get( 'sync_frequency' ) ) ) {
			return;
		}

		// Respect a Retry-After from a previous 429 instead of hammering the API.
		if ( $this->state->rate_limit_remaining() > 0 ) {
			return;
		}

		( $this->sync_factory )()->run( 'cron' );
	}

	/**
	 * Removes all scheduled events.
	 */
	public static function clear(): void {
		wp_clear_scheduled_hook( self::HOOK );
	}
}
