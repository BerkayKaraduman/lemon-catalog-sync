<?php
/**
 * PSR-4 style autoloader using WordPress file naming.
 *
 * LCS\Product_Sync                    → includes/class-product-sync.php
 * LCS\Admin\Admin                     → admin/class-admin.php
 * LCS\Elementor\Widgets\Buy_Button    → elementor/widgets/class-buy-button.php
 * LCS\Elementor\Dynamic_Tags\Price    → elementor/dynamic-tags/class-price.php
 *
 * @package LCS
 */

namespace LCS;

defined( 'ABSPATH' ) || exit;

/**
 * Class loader for the LCS namespace. No Composer required.
 */
final class Autoloader {

	/**
	 * Top level namespace segments that live outside includes/.
	 */
	private const ROOT_DIRECTORIES = array( 'admin', 'elementor' );

	/**
	 * Registers the loader with SPL.
	 */
	public static function register(): void {
		spl_autoload_register( array( self::class, 'load' ) );
	}

	/**
	 * Loads a class file if it belongs to the LCS namespace.
	 *
	 * @param string $class_name Fully qualified class name.
	 */
	public static function load( string $class_name ): void {
		if ( ! str_starts_with( $class_name, __NAMESPACE__ . '\\' ) ) {
			return;
		}

		$parts = explode( '\\', substr( $class_name, strlen( __NAMESPACE__ ) + 1 ) );
		$name  = array_pop( $parts );
		$dirs  = array_map( array( self::class, 'to_file_part' ), $parts );

		$base = LCS_PATH . 'includes/';
		if ( $dirs && in_array( $dirs[0], self::ROOT_DIRECTORIES, true ) ) {
			$base = LCS_PATH;
		}

		$path = $base . ( $dirs ? implode( '/', $dirs ) . '/' : '' ) . 'class-' . self::to_file_part( $name ) . '.php';

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}

	/**
	 * Converts a namespace segment to a file/directory name.
	 *
	 * @param string $part Segment.
	 */
	private static function to_file_part( string $part ): string {
		return strtolower( str_replace( '_', '-', $part ) );
	}
}
