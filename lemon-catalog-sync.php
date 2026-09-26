<?php
/**
 * Plugin Name:       Lemon Catalog Sync for Elementor
 * Plugin URI:        https://github.com/BerkayKaraduman/lemon-catalog-sync
 * Description:       Syncs your Lemon Squeezy product catalog into a dedicated "Dijital Ürünler" post type with per-product Elementor design, Theme Builder support, widgets, dynamic tags and shortcodes. No WooCommerce required.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Yahya Berkay Karaduman
 * Author URI:        https://www.instagram.com/yahyaberkay/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       lemon-catalog-sync
 *
 * @package LCS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LCS_VERSION', '1.0.0' );
define( 'LCS_FILE', __FILE__ );
define( 'LCS_PATH', plugin_dir_path( __FILE__ ) );
define( 'LCS_URL', plugin_dir_url( __FILE__ ) );
define( 'LCS_BASENAME', plugin_basename( __FILE__ ) );
define( 'LCS_MIN_PHP', '8.1' );

// This file must stay parseable on old PHP versions so the notice below can be shown.
if ( version_compare( PHP_VERSION, LCS_MIN_PHP, '<' ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>';
			echo esc_html(
				sprintf(
					/* translators: 1: required PHP version, 2: current PHP version. */
					__( 'Lemon Catalog Sync for Elementor requires PHP %1$s or newer. Your server runs PHP %2$s. The plugin is inactive until PHP is upgraded.', 'lemon-catalog-sync' ),
					LCS_MIN_PHP,
					PHP_VERSION
				)
			);
			echo '</p></div>';
		}
	);
	return;
}

require_once LCS_PATH . 'includes/class-autoloader.php';
\LCS\Autoloader::register();

register_activation_hook( __FILE__, array( '\LCS\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\LCS\Activator', 'deactivate' ) );

add_action( 'plugins_loaded', array( \LCS\Plugin::instance(), 'boot' ) );
