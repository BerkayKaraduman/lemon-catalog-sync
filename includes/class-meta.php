<?php
/**
 * Meta key registry.
 *
 * Every value the sync engine writes lives under the "_lcs_" prefix. Nothing
 * else (post_content, _elementor_*, SEO plugin keys, taxonomies) is ever
 * written by the sync — see Product_Repository::write_meta().
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

	// Attachment meta (Media Library image mode).
	public const SOURCE_IMAGE_URL = '_lcs_source_image_url';

	/**
	 * Whether a key belongs to this plugin and may be written by the sync.
	 *
	 * @param string $key Meta key.
	 */
	public static function is_own( string $key ): bool {
		return str_starts_with( $key, self::PREFIX );
	}
}
