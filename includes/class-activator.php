<?php
/**
 * Activation / deactivation routines.
 *
 * @package LCS
 */

namespace LCS;

defined( 'ABSPATH' ) || exit;

/**
 * Activation runs before the plugin's plugins_loaded bootstrap, so everything
 * here is self-contained.
 */
final class Activator {

	/**
	 * Activation: defaults → CPT/taxonomy → rewrite flush → cron.
	 */
	public static function activate(): void {
		$settings = new Settings();

		if ( false === get_option( Settings::OPTION, false ) ) {
			$defaults = Settings::defaults();
			// Without Elementor Pro there is no Theme Builder, so product pages need the automatic block.
			$defaults['display_mode'] = Compat::elementor_pro_active() ? 'theme_builder' : 'auto_block';
			add_option( Settings::OPTION, $defaults );
		}

		$post_type = new Post_Type( $settings );
		$post_type->register();
		flush_rewrite_rules( false );
		update_option( Post_Type::REWRITE_SIGNATURE_OPTION, $post_type->rewrite_signature(), true );

		add_filter( 'cron_schedules', array( Cron::class, 'add_schedules' ) ); // phpcs:ignore WordPress.WP.CronInterval
		Cron::sync_schedule( (string) $settings->get( 'sync_frequency' ) );
	}

	/**
	 * Deactivation: clears cron and the sync lock. Never deletes products,
	 * categories, Elementor content, SEO data or media.
	 */
	public static function deactivate(): void {
		Cron::clear();
		Sync_Lock::clear();

		unregister_post_type( Post_Type::POST_TYPE );
		unregister_taxonomy( Post_Type::TAXONOMY );
		flush_rewrite_rules( false );

		// Force a fresh flush on the next activation.
		delete_option( Post_Type::REWRITE_SIGNATURE_OPTION );
	}
}
