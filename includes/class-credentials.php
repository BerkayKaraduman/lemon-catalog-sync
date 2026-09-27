<?php
/**
 * Lemon Squeezy API key — the single place the key is read, stored or masked.
 *
 * @package LCS
 */

namespace LCS;

defined( 'ABSPATH' ) || exit;

/**
 * Credential service.
 *
 * Source order:
 * 1. LCS_LEMON_API_KEY constant in wp-config.php (recommended). While it is
 *    defined, nothing is ever written to the database.
 * 2. Backward-compatible fallback: the "lcs_api_key" option — never
 *    autoloaded, encrypted with libsodium (key derived from the site salts)
 *    when available. Used only when the constant is not defined.
 *
 * The key is only ever placed in the Authorization header of server-side
 * wp_remote_get() calls (Api_Client). It is never printed, localized to
 * JavaScript, exposed via REST/AJAX, logged or included in error messages;
 * use redact() on any message that could contain it.
 */
final class Credentials {

	public const CONSTANT = 'LCS_LEMON_API_KEY';
	public const OPTION   = 'lcs_api_key';

	private const CIPHER_PREFIX = 'lcs1:';

	/**
	 * The effective API key, or ''. Never print this value.
	 */
	public static function get_api_key(): string {
		if ( self::has_constant_key() ) {
			return trim( (string) constant( self::CONSTANT ) );
		}

		return self::stored_key();
	}

	/**
	 * Whether a key is available from any source.
	 */
	public static function has_api_key(): bool {
		return '' !== self::get_api_key();
	}

	/**
	 * Whether LCS_LEMON_API_KEY is defined with a non-empty string value.
	 */
	public static function has_constant_key(): bool {
		return defined( self::CONSTANT ) && is_string( constant( self::CONSTANT ) ) && '' !== trim( (string) constant( self::CONSTANT ) );
	}

	/**
	 * Whether the database still holds a key (e.g. left over after moving to
	 * wp-config.php).
	 */
	public static function has_stored_key(): bool {
		$stored = get_option( self::OPTION, '' );
		return is_string( $stored ) && '' !== $stored;
	}

	/**
	 * Whether a stored key exists but can no longer be decrypted (salts changed).
	 */
	public static function stored_key_unreadable(): bool {
		return self::has_stored_key() && '' === self::stored_key();
	}

	/**
	 * Stores the database fallback key (encrypted, autoload off). Refused while
	 * LCS_LEMON_API_KEY is defined, so the key never reaches wp_options then.
	 *
	 * @param string $key Plain API key.
	 * @return bool Whether it was stored.
	 */
	public static function store_key( string $key ): bool {
		$key = trim( $key );
		if ( '' === $key || self::has_constant_key() ) {
			return false;
		}

		// Recreate the option so it can never inherit autoload = yes.
		delete_option( self::OPTION );
		return add_option( self::OPTION, self::encrypt( $key ), '', false );
	}

	/**
	 * Removes the database key.
	 */
	public static function delete_stored_key(): void {
		delete_option( self::OPTION );
	}

	/**
	 * Ensures an existing fallback option is not autoloaded (installs created
	 * by other code paths or older WordPress versions).
	 */
	public static function enforce_no_autoload(): void {
		if ( self::has_stored_key() && function_exists( 'wp_set_option_autoload' ) ) {
			wp_set_option_autoload( self::OPTION, false );
		}
	}

	/**
	 * Masked representation for the admin UI: bullets plus the last 4
	 * characters of a database key. A wp-config.php key is never revealed,
	 * not even partially.
	 */
	public static function masked(): string {
		if ( self::has_constant_key() ) {
			return str_repeat( '•', 12 );
		}

		$key = self::stored_key();
		if ( '' === $key ) {
			return '';
		}

		return strlen( $key ) > 16 ? str_repeat( '•', 12 ) . substr( $key, -4 ) : str_repeat( '•', 12 );
	}

	/**
	 * Replaces the configured key (and optionally another candidate key) in a
	 * message with "[redacted]".
	 *
	 * @param string $message Message.
	 * @param string $extra   Another key to hide, e.g. a candidate being tested.
	 */
	public static function redact( string $message, string $extra = '' ): string {
		foreach ( array_unique( array( self::get_api_key(), trim( $extra ) ) ) as $secret ) {
			if ( strlen( $secret ) >= 8 ) {
				$message = str_replace( $secret, '[redacted]', $message );
			}
		}
		return $message;
	}

	/*
	 * ---------------------------------------------------------------------
	 * Storage encryption
	 * ---------------------------------------------------------------------
	 */

	/**
	 * Decrypted database key, or ''.
	 */
	private static function stored_key(): string {
		$stored = get_option( self::OPTION, '' );
		return is_string( $stored ) && '' !== $stored ? self::decrypt( $stored ) : '';
	}

	/**
	 * Encrypts a value with a key derived from the site's salts.
	 *
	 * @param string $plain Plain text.
	 */
	private static function encrypt( string $plain ): string {
		if ( ! function_exists( 'sodium_crypto_secretbox' ) ) {
			return $plain;
		}

		try {
			$nonce  = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$cipher = sodium_crypto_secretbox( $plain, $nonce, self::crypto_key() );
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- binary-safe storage of ciphertext.
			return self::CIPHER_PREFIX . base64_encode( $nonce . $cipher );
		} catch ( \Throwable $e ) {
			return $plain;
		}
	}

	/**
	 * Decrypts a stored value; '' when it cannot be decrypted.
	 *
	 * @param string $stored Stored value.
	 */
	private static function decrypt( string $stored ): string {
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
				self::crypto_key()
			);
		} catch ( \Throwable $e ) {
			return '';
		}

		return false === $plain ? '' : $plain;
	}

	/**
	 * 32 byte key derived from wp_salt(). Must stay identical to the 1.x
	 * derivation so previously stored keys keep decrypting.
	 */
	private static function crypto_key(): string {
		return sodium_crypto_generichash( wp_salt( 'secure_auth' ) . '|lemon-catalog-sync', '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );
	}
}
