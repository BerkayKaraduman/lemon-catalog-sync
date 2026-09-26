<?php
/**
 * Environment detection helpers (Elementor / Elementor Pro).
 *
 * @package LCS
 */

namespace LCS;

defined( 'ABSPATH' ) || exit;

/**
 * Static helpers that never fatal when Elementor is missing.
 */
final class Compat {

	/**
	 * Oldest Elementor version whose non-deprecated registration APIs we use
	 * (elementor/widgets/register, elementor/dynamic_tags/register).
	 */
	public const MIN_ELEMENTOR_VERSION = '3.5.0';

	/**
	 * Elementor loaded at all.
	 */
	public static function elementor_loaded(): bool {
		return (bool) did_action( 'elementor/loaded' ) && defined( 'ELEMENTOR_VERSION' );
	}

	/**
	 * Elementor loaded and new enough for our integration.
	 */
	public static function elementor_supported(): bool {
		return self::elementor_loaded() && version_compare( ELEMENTOR_VERSION, self::MIN_ELEMENTOR_VERSION, '>=' );
	}

	/**
	 * Elementor Pro active.
	 */
	public static function elementor_pro_active(): bool {
		return defined( 'ELEMENTOR_PRO_VERSION' ) || class_exists( '\ElementorPro\Plugin', false );
	}

	/**
	 * Whether the current request renders inside the Elementor editor or its preview iframe.
	 */
	public static function is_elementor_editor(): bool {
		if ( ! self::elementor_loaded() || ! class_exists( '\Elementor\Plugin', false ) ) {
			return false;
		}

		$elementor = \Elementor\Plugin::$instance;
		if ( ! $elementor ) {
			return false;
		}

		if ( isset( $elementor->editor ) && $elementor->editor->is_edit_mode() ) {
			return true;
		}

		return isset( $elementor->preview ) && $elementor->preview->is_preview_mode();
	}
}
