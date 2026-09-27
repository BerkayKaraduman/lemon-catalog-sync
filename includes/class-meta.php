<?php
/**
 * Meta key registry.
 *
 * Every value the sync engine writes lives under the "_lcs_" prefix. Nothing
 * else (post_content, _elementor_*, SEO plugin keys, taxonomies) is ever
 * written by the sync — see Product_Repository::write_meta().
 *
 * The card fields (USER_KEYS) share the prefix but belong to the site owner:
 * they are edited in WordPress only and the sync can never write or delete them.
 *
 * @package LCS
 */

namespace LCS;

defined( 'ABSPATH' ) || exit;

/**
 * Meta key constants.
 */
final class Meta {

	public const PREFIX = '_lcs_';

	public const PRODUCT_ID           = '_lcs_product_id';
	public const STORE_ID             = '_lcs_store_id';
	public const DESCRIPTION          = '_lcs_lemon_description';
	public const PRICE                = '_lcs_price';
	public const PRICE_FORMATTED      = '_lcs_price_formatted';
	public const FROM_PRICE           = '_lcs_from_price';
	public const FROM_PRICE_FORMATTED = '_lcs_from_price_formatted';
	public const TO_PRICE             = '_lcs_to_price';
	public const TO_PRICE_FORMATTED   = '_lcs_to_price_formatted';
	public const PAY_WHAT_YOU_WANT    = '_lcs_pay_what_you_want';
	public const BUY_NOW_URL          = '_lcs_buy_now_url';
	public const THUMB_URL            = '_lcs_thumb_url';
	public const LARGE_THUMB_URL      = '_lcs_large_thumb_url';
	public const STATUS               = '_lcs_lemon_status';
	public const STATUS_FORMATTED     = '_lcs_lemon_status_formatted';
	public const CREATED_AT           = '_lcs_lemon_created_at';
	public const UPDATED_AT           = '_lcs_lemon_updated_at';
	public const TEST_MODE            = '_lcs_test_mode';
	public const VARIANTS             = '_lcs_variants';
	public const LAST_SYNC            = '_lcs_last_sync';
	public const MISSING              = '_lcs_missing_from_lemon';
	public const SYNC_HASH            = '_lcs_sync_hash';

	// Card fields — managed in WordPress ("Kart Görünümü" metabox), never by the sync.
	public const COMPARE_PRICE = '_lcs_compare_price';
	public const CARD_SUBTITLE = '_lcs_card_subtitle';
	public const CARD_BADGE    = '_lcs_card_badge';

	/**
	 * Keys owned by the WordPress user. Excluded from is_own().
	 */
	public const USER_KEYS = array( self::COMPARE_PRICE, self::CARD_SUBTITLE, self::CARD_BADGE );

	/**
	 * Keys an earlier build or a hand-made setup may have used, per canonical
	 * key, in priority order. Migrated once into the canonical key when that
	 * is empty; the legacy keys are never deleted.
	 */
	public const LEGACY_KEYS = array(
		self::COMPARE_PRICE => array( '_lcs_old_price', 'lcs_compare_price', 'compare_price' ),
		self::CARD_SUBTITLE => array( '_lcs_subtitle', 'card_subtitle' ),
		self::CARD_BADGE    => array( '_lcs_badge', 'card_badge' ),
	);

	public const CARD_MAX_LENGTH = 200;

	// Attachment meta (Media Library image mode).
	public const SOURCE_IMAGE_URL = '_lcs_source_image_url';

	/**
	 * Whether a key belongs to this plugin and may be written by the sync.
	 *
	 * @param string $key Meta key.
	 */
	public static function is_own( string $key ): bool {
		return str_starts_with( $key, self::PREFIX ) && ! self::is_user_key( $key );
	}

	/**
	 * Whether a key is a WordPress-managed card field.
	 *
	 * @param string $key Meta key.
	 */
	public static function is_user_key( string $key ): bool {
		return in_array( $key, self::USER_KEYS, true );
	}

	/**
	 * Sanitizes a card text field (subtitle / badge) with sanitize_text_field().
	 * "%" is shielded first because sanitize_text_field() strips "%xx"
	 * sequences, which would turn "%40 İndirim" into " İndirim".
	 *
	 * @param mixed $value Raw value.
	 */
	public static function sanitize_card_value( $value ): string {
		if ( ! is_scalar( $value ) || is_bool( $value ) ) {
			return '';
		}

		$shield = "\u{F8FF}";
		$value  = str_replace( $shield, '', (string) $value );
		$value  = sanitize_text_field( str_replace( '%', $shield, $value ) );
		$value  = trim( str_replace( $shield, '%', $value ) );

		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, self::CARD_MAX_LENGTH ) : substr( $value, 0, self::CARD_MAX_LENGTH );
	}

	/**
	 * Parses a price typed by a person into a decimal amount, or null.
	 * Accepts "50", "39.90", "39,90", "1.299,90", "1,299.90", "$50", "50 TL".
	 *
	 * @param mixed $value Raw value.
	 */
	public static function parse_price( $value ): ?float {
		if ( ! is_scalar( $value ) || is_bool( $value ) ) {
			return null;
		}

		if ( str_contains( (string) $value, '-' ) ) {
			return null; // Negative amounts are not a price.
		}

		$number = (string) preg_replace( '/[^0-9.,]/', '', (string) $value );
		if ( '' === $number || ! preg_match( '/\d/', $number ) ) {
			return null;
		}

		$last_dot   = strrpos( $number, '.' );
		$last_comma = strrpos( $number, ',' );

		if ( false !== $last_dot && false !== $last_comma ) {
			// Both present: the right-most one is the decimal separator.
			$decimal = $last_dot > $last_comma ? '.' : ',';
			$number  = str_replace( '.' === $decimal ? ',' : '.', '', $number );
			$number  = str_replace( $decimal, '.', $number );
		} elseif ( false !== $last_dot || false !== $last_comma ) {
			$separator = false !== $last_dot ? '.' : ',';
			$parts     = explode( $separator, $number );
			$tail      = (string) end( $parts );
			// One separator followed by 1–2 digits is a decimal point; otherwise thousands grouping.
			$number = ( 2 === count( $parts ) && strlen( $tail ) <= 2 )
				? str_replace( $separator, '.', $number )
				: str_replace( $separator, '', $number );
		}

		if ( ! is_numeric( $number ) ) {
			return null;
		}

		$amount = round( (float) $number, 2 );
		return $amount > 0 ? $amount : null;
	}

	/**
	 * Canonical stored form of the compare price: a plain number without
	 * currency — "50" for whole amounts, "39.90" otherwise — or '' when the
	 * input is empty/invalid.
	 *
	 * @param mixed $value Raw value.
	 */
	public static function sanitize_compare_price( $value ): string {
		$amount = self::parse_price( $value );
		if ( null === $amount ) {
			return '';
		}
		return floor( $amount ) === $amount ? (string) (int) $amount : number_format( $amount, 2, '.', '' );
	}
}
