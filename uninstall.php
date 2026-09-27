<?php
/**
 * Uninstall routine.
 *
 * Default behaviour is DATA PRESERVE: products (lcs_product posts), categories,
 * Elementor content, SEO data and media are never deleted. Plugin settings are
 * removed only if "Delete plugin settings on uninstall" was enabled.
 *
 * @package LCS
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Cleans one site.
 */
function lcs_uninstall_site() {
	wp_clear_scheduled_hook( 'lcs_cron_sync' );
	delete_transient( 'lcs_sync_lock' );

	// The API key is a credential, not content: always removed on uninstall.
	delete_option( 'lcs_api_key' );

	$settings = get_option( 'lcs_settings' );
	if ( ! is_array( $settings ) || empty( $settings['delete_data_on_uninstall'] ) ) {
		return;
	}

	foreach ( array( 'lcs_settings', 'lcs_api_key', 'lcs_sync_state', 'lcs_rewrite_signature', 'lcs_elementor_cpt_merged', 'lcs_compare_price_migrated', 'lcs_card_meta_migration', 'lcs_api_key_autoload_checked' ) as $option ) {
		delete_option( $option );
	}
	delete_transient( 'lcs_stores_cache' );
}

if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids' ) ) as $lcs_site_id ) {
		switch_to_blog( (int) $lcs_site_id );
		lcs_uninstall_site();
		restore_current_blog();
	}
} else {
	lcs_uninstall_site();
}
