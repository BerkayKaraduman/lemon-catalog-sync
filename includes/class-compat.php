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

	/**
	 * Runs $callback inside the same query context Elementor gives dynamic
	 * tags. Elementor Pro's Theme Builder hooks these actions to switch to the
	 * template's representative preview post (what "Post Title" shows), so LCS
	 * widgets resolve that same product. Without Pro the actions are no-ops.
	 *
	 * @param callable $callback Callback.
	 * @return mixed Callback result.
	 */
	public static function in_dynamic_context( callable $callback ) {
		do_action( 'elementor/dynamic_tags/before_render' );
		try {
			return $callback();
		} finally {
			do_action( 'elementor/dynamic_tags/after_render' );
		}
	}

	/**
	 * Preview post chosen in the settings of the document being edited
	 * (Theme Builder / Loop Item templates), or 0.
	 */
	public static function editor_preview_post_id(): int {
		if ( ! self::is_elementor_editor() ) {
			return 0;
		}

		try {
			$documents = \Elementor\Plugin::$instance->documents ?? null;
			$document  = $documents && method_exists( $documents, 'get_current' ) ? $documents->get_current() : null;
			if ( ! $document || ! method_exists( $document, 'get_settings' ) ) {
				return 0;
			}
			$preview_id = $document->get_settings( 'preview_id' );
			return is_scalar( $preview_id ) ? absint( $preview_id ) : 0;
		} catch ( \Throwable $e ) {
			return 0;
		}
	}
}
