<?php
/**
 * Plugin settings and API key storage.
 *
 * @package LCS
 */

namespace LCS;

defined( 'ABSPATH' ) || exit;

/**
 * Reads/writes the lcs_settings option and the (non-autoloaded) API key option.
 */
final class Settings {

	public const OPTION     = 'lcs_settings';
	public const KEY_OPTION = 'lcs_api_key';
	public const KEY_CONST  = 'LCS_LEMON_API_KEY';

	private const CIPHER_PREFIX = 'lcs1:';

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
	 * API key
	 * ---------------------------------------------------------------------
	 */

	/**
	 * Whether the key comes from wp-config.php.
	 */
	public function has_constant_key(): bool {
		return defined( self::KEY_CONST ) && is_string( constant( self::KEY_CONST ) ) && '' !== trim( (string) constant( self::KEY_CONST ) );
	}

	/**
	 * The effective API key. Never print this value.
	 */
	public function get_api_key(): string {
		if ( $this->has_constant_key() ) {
			return trim( (string) constant( self::KEY_CONST ) );
		}

		$stored = get_option( self::KEY_OPTION, '' );
		return is_string( $stored ) ? $this->decrypt( $stored ) : '';
	}

	/**
	 * Whether any key is configured.
	 */
	public function has_api_key(): bool {
		return '' !== $this->get_api_key();
	}

	/**
	 * Whether a key is stored in the database but can no longer be decrypted
	 * (e.g. the site's salts changed).
	 */
	public function stored_key_unreadable(): bool {
		$stored = get_option( self::KEY_OPTION, '' );
		return is_string( $stored ) && '' !== $stored && '' === $this->decrypt( $stored );
	}

	/**
	 * Stores the API key (encrypted when libsodium is available) without autoload.
	 *
	 * @param string $key Plain API key, or '' to remove it.
	 */
	public function set_api_key( string $key ): void {
		$key = trim( $key );

		if ( '' === $key ) {
			delete_option( self::KEY_OPTION );
			return;
		}

		// A new key must not inherit the old autoload value, so recreate the option.
		delete_option( self::KEY_OPTION );
		add_option( self::KEY_OPTION, $this->encrypt( $key ), '', false );
	}

	/**
	 * Masked hint safe to print in HTML (never the full key).
	 */
	public function api_key_hint(): string {
		$key = $this->get_api_key();
		if ( '' === $key ) {
			return '';
		}
		return strlen( $key ) > 12 ? str_repeat( '•', 8 ) . substr( $key, -4 ) : str_repeat( '•', 8 );
	}

	/**
	 * Encrypts a value with a key derived from the site's salts.
	 *
	 * @param string $plain Plain text.
	 */
	private function encrypt( string $plain ): string {
		if ( ! function_exists( 'sodium_crypto_secretbox' ) ) {
			return $plain;
		}

		try {
			$nonce  = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$cipher = sodium_crypto_secretbox( $plain, $nonce, $this->crypto_key() );
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- binary-safe storage of ciphertext.
			return self::CIPHER_PREFIX . base64_encode( $nonce . $cipher );
		} catch ( \Throwable $e ) {
			return $plain;
		}
	}

	/**
	 * Decrypts a stored value. Returns '' when it cannot be decrypted.
	 *
	 * @param string $stored Stored value.
	 */
	private function decrypt( string $stored ): string {
		if ( ! str_starts_with( $stored, self::CIPHER_PREFIX ) ) {
			return trim( $stored );
		}

		if ( ! function_exists( 'sodium_crypto_secretbox_open' ) ) {
			return '';
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- see encrypt().
		$raw = base64_decode( substr( $stored, strlen( self::CIPHER_PREFIX ) ), true );
		if ( false === $raw || strlen( $raw ) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) {
			return '';
		}

		try {
			$plain = sodium_crypto_secretbox_open(
				substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ),
				substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ),
				$this->crypto_key()
			);
		} catch ( \Throwable $e ) {
			return '';
		}

		return false === $plain ? '' : $plain;
	}

	/**
	 * 32 byte key derived from wp_salt().
	 */
	private function crypto_key(): string {
		return sodium_crypto_generichash( wp_salt( 'secure_auth' ) . '|lemon-catalog-sync', '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );
	}
}
