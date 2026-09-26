<?php
/**
 * Admin menu, pages and assets.
 *
 * @package LCS
 */

namespace LCS\Admin;

use LCS\Compat;
use LCS\Plugin;
use LCS\Post_Type;
use LCS\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Lemon Catalog Sync → Dashboard / Sync / Settings / Elementor Setup.
 */
final class Admin {

	public const CAPABILITY = 'manage_options';
	public const PAGES      = array( 'lcs-dashboard', 'lcs-sync', 'lcs-settings', 'lcs-elementor' );

	/**
	 * Plugin.
	 *
	 * @var Plugin
	 */
	private Plugin $plugin;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Plugin.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Hooks.
	 */
	public function hooks(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		( new Admin_Actions( $this->plugin ) )->hooks();
	}

	/**
	 * Registers menu pages.
	 */
	public function menu(): void {
		add_menu_page(
			__( 'Lemon Catalog Sync', 'lemon-catalog-sync' ),
			__( 'Lemon Catalog Sync', 'lemon-catalog-sync' ),
			self::CAPABILITY,
			'lcs-dashboard',
			array( $this, 'page_dashboard' ),
			'dashicons-update',
			58
		);

		$pages = array(
			'lcs-dashboard' => array( __( 'Dashboard', 'lemon-catalog-sync' ), 'page_dashboard' ),
			'lcs-sync'      => array( __( 'Sync', 'lemon-catalog-sync' ), 'page_sync' ),
			'lcs-settings'  => array( __( 'Settings', 'lemon-catalog-sync' ), 'page_settings' ),
			'lcs-elementor' => array( __( 'Elementor Setup', 'lemon-catalog-sync' ), 'page_elementor' ),
		);

		foreach ( $pages as $slug => $page ) {
			add_submenu_page( 'lcs-dashboard', $page[0], $page[0], self::CAPABILITY, $slug, array( $this, $page[1] ) );
		}
	}

	/**
	 * Admin CSS/JS on our pages and the product editor only.
	 *
	 * @param string $hook_suffix Hook suffix.
	 */
	public function assets( $hook_suffix ): void {
		$screen     = get_current_screen();
		$is_product = $screen && Post_Type::POST_TYPE === $screen->post_type;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page detection.
		$page    = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$is_ours = in_array( $page, self::PAGES, true );

		if ( ! $is_ours && ! $is_product ) {
			return;
		}

		wp_enqueue_style( 'lcs-admin', LCS_URL . 'admin/css/admin.css', array(), LCS_VERSION );

		if ( $is_ours ) {
			wp_enqueue_script( 'lcs-admin', LCS_URL . 'admin/js/admin.js', array(), LCS_VERSION, true );
			wp_localize_script(
				'lcs-admin',
				'lcsAdmin',
				array(
					'syncing' => __( 'Syncing… this can take a minute.', 'lemon-catalog-sync' ),
					'testing' => __( 'Testing…', 'lemon-catalog-sync' ),
				)
			);
		}
	}

	/*
	 * ---------------------------------------------------------------------
	 * Pages
	 * ---------------------------------------------------------------------
	 */

	/**
	 * Dashboard.
	 */
	public function page_dashboard(): void {
		$this->guard();
		$settings = $this->plugin->settings();
		$state    = $this->plugin->state()->get();
		$counts   = wp_count_posts( Post_Type::POST_TYPE );
		$this->view( 'dashboard', compact( 'settings', 'state', 'counts' ) );
	}

	/**
	 * Sync page.
	 */
	public function page_sync(): void {
		$this->guard();
		$settings = $this->plugin->settings();
		$state    = $this->plugin->state()->get();
		$lock     = $this->plugin->lock()->status();
		$wait     = $this->plugin->state()->rate_limit_remaining();
		$this->view( 'sync', compact( 'settings', 'state', 'lock', 'wait' ) );
	}

	/**
	 * Settings page.
	 */
	public function page_settings(): void {
		$this->guard();
		$settings = $this->plugin->settings();
		$stores   = $this->plugin->stores()->cached_stores();
		$state    = $this->plugin->state()->get();
		$choices  = Settings::choices();
		$this->view( 'settings', compact( 'settings', 'stores', 'state', 'choices' ) );
	}

	/**
	 * Elementor onboarding page.
	 */
	public function page_elementor(): void {
		$this->guard();
		$elementor     = Compat::elementor_loaded();
		$elementor_ok  = Compat::elementor_supported();
		$elementor_pro = Compat::elementor_pro_active();
		$editing       = post_type_supports( Post_Type::POST_TYPE, 'elementor' );
		$display_mode  = (string) $this->plugin->settings()->get( 'display_mode' );
		$theme_builder = admin_url( 'edit.php?post_type=elementor_library&tabs_group=theme' );
		$this->view( 'elementor-setup', compact( 'elementor', 'elementor_ok', 'elementor_pro', 'editing', 'display_mode', 'theme_builder' ) );
	}

	/**
	 * Formats a timestamp for display.
	 *
	 * @param int $timestamp Unix time.
	 */
	public static function time( int $timestamp ): string {
		if ( $timestamp <= 0 ) {
			return __( 'Never', 'lemon-catalog-sync' );
		}
		return sprintf(
			/* translators: 1: date/time, 2: human time difference. */
			__( '%1$s (%2$s ago)', 'lemon-catalog-sync' ),
			wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ),
			human_time_diff( $timestamp )
		);
	}

	/**
	 * Prints and clears the pending notice for this user.
	 */
	public static function notices(): void {
		$key    = Admin_Actions::notice_key();
		$notice = get_transient( $key );
		if ( ! is_array( $notice ) ) {
			return;
		}
		delete_transient( $key );

		$type = in_array( $notice['type'] ?? '', array( 'success', 'error', 'warning', 'info' ), true ) ? $notice['type'] : 'info';
		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $type ),
			esc_html( (string) ( $notice['message'] ?? '' ) )
		);
	}

	/**
	 * Capability check for page callbacks.
	 */
	private function guard(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'lemon-catalog-sync' ), 403 );
		}
	}

	/**
	 * Renders a view with extracted variables.
	 *
	 * @param string              $name View name.
	 * @param array<string,mixed> $vars Variables.
	 */
	private function view( string $name, array $vars ): void {
		$file = LCS_PATH . 'admin/views/' . $name . '.php';
		if ( ! is_readable( $file ) ) {
			return;
		}
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- controlled, local variables only.
		extract( $vars, EXTR_SKIP );
		include $file;
	}
}
