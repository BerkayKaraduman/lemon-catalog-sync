<?php
/**
 * Plugin settings.
 *
 * @package LCS
 */

namespace LCS;

defined( 'ABSPATH' ) || exit;

/**
 * Reads/writes the lcs_settings option. The API key lives in Credentials.
 */
final class Settings {

	public const OPTION     = 'lcs_settings';
	public const KEY_OPTION = Credentials::OPTION;
	public const KEY_CONST  = Credentials::CONSTANT;

	/**
	 * Cached merged settings.
	 *
	 * @var array<string,mixed>|null
	 */
	private ?array $cache = null;

	/**
	 * Default settings.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return array(
			'store_id'                 => '',
			'store_name'               => '',
			'store_currency'           => 'USD',
			'sync_frequency'           => 'disabled',
			'checkout_mode'            => 'hosted',
			'product_base'             => 'urun',
			'category_base'            => 'urun-kategori',
			'image_mode'               => 'remote',
			'display_mode'             => 'theme_builder',
			'delete_data_on_uninstall' => false,
		);
	}

	/**
	 * Allowed values for select-type settings, with labels.
	 *
	 * @return array<string,array<string,string>>
	 */
	public static function choices(): array {
		return array(
			'sync_frequency' => array(
				'disabled'   => __( 'Disabled', 'lemon-catalog-sync' ),
				'15min'      => __( 'Every 15 Minutes', 'lemon-catalog-sync' ),
				'hourly'     => __( 'Hourly', 'lemon-catalog-sync' ),
				'twicedaily' => __( 'Twice Daily', 'lemon-catalog-sync' ),
				'daily'      => __( 'Daily', 'lemon-catalog-sync' ),
			),
			'checkout_mode'  => array(
				'hosted'  => __( 'Hosted Checkout', 'lemon-catalog-sync' ),
				'overlay' => __( 'Checkout Overlay (Lemon.js)', 'lemon-catalog-sync' ),
			),
			'image_mode'     => array(
				'remote' => __( 'Remote Lemon Image', 'lemon-catalog-sync' ),
				'media'  => __( 'WordPress Media Library', 'lemon-catalog-sync' ),
			),
			'display_mode'   => array(
				'theme_builder' => __( 'Elementor Theme Builder', 'lemon-catalog-sync' ),
				'auto_block'    => __( 'Automatic Product Block', 'lemon-catalog-sync' ),
			),
		);
	}

	/**
	 * All settings merged with defaults.
	 *
	 * @return array<string,mixed>
	 */
	public function all(): array {
		if ( null === $this->cache ) {
			$stored      = get_option( self::OPTION, array() );
			$this->cache = array_merge( self::defaults(), is_array( $stored ) ? $stored : array() );
		}
		return $this->cache;
	}

	/**
	 * Single setting.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	public function get( string $key ) {
		$all = $this->all();
		return $all[ $key ] ?? ( self::defaults()[ $key ] ?? null );
	}

	/**
	 * Merges and persists settings. Unknown keys are dropped.
	 *
	 * @param array<string,mixed> $values New values (already sanitized).
	 */
	public function update( array $values ): void {
		$merged = array_intersect_key( array_merge( $this->all(), $values ), self::defaults() );
		update_option( self::OPTION, $merged );
		$this->cache = $merged;
	}

	/**
	 * Convenience accessors.
	 */
	public function store_id(): string {
		return (string) $this->get( 'store_id' );
	}

	/**
	 * Checkout overlay enabled.
	 */
	public function is_overlay_checkout(): bool {
		return 'overlay' === $this->get( 'checkout_mode' );
	}

	/**
	 * Media Library image mode enabled.
	 */
	public function is_media_image_mode(): bool {
		return 'media' === $this->get( 'image_mode' );
	}

	/**
	 * Automatic product block enabled.
	 */
	public function is_auto_block_mode(): bool {
		return 'auto_block' === $this->get( 'display_mode' );
	}

	/*
	 * ---------------------------------------------------------------------
	 * API key — status only. The key itself is read exclusively through
	 * Credentials::get_api_key() (see class-credentials.php).
	 * ---------------------------------------------------------------------
	 */

	/**
	 * Whether the key comes from wp-config.php.
	 */
	public function has_constant_key(): bool {
		return Credentials::has_constant_key();
	}

	/**
	 * Whether any key is configured.
	 */
	public function has_api_key(): bool {
		return Credentials::has_api_key();
	}

	/**
	 * Whether a stored key can no longer be decrypted.
	 */
	public function stored_key_unreadable(): bool {
		return Credentials::stored_key_unreadable();
	}

	/**
	 * Masked hint safe to print in HTML (never the key).
	 */
	public function api_key_hint(): string {
		return Credentials::masked();
	}
}
