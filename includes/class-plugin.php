<?php
/**
 * Plugin bootstrap / service container.
 *
 * @package LCS
 */

namespace LCS;

defined( 'ABSPATH' ) || exit;

/**
 * Wires services together. Future modules (e.g. order_created → WordPress user
 * → purchased products) can hook `lcs_loaded` and reuse api(), settings() etc.
 */
final class Plugin {

	/**
	 * Singleton.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Booted flag.
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * Services.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * API client.
	 *
	 * @var Api_Client
	 */
	private Api_Client $api;

	/**
	 * Store service.
	 *
	 * @var Store_Service
	 */
	private Store_Service $stores;

	/**
	 * Post type.
	 *
	 * @var Post_Type
	 */
	private Post_Type $post_type;

	/**
	 * Sync state.
	 *
	 * @var Sync_State
	 */
	private Sync_State $state;

	/**
	 * Sync lock.
	 *
	 * @var Sync_Lock
	 */
	private Sync_Lock $lock;

	/**
	 * Renderer.
	 *
	 * @var Renderer
	 */
	private Renderer $renderer;

	/**
	 * Sync service (lazy).
	 *
	 * @var Product_Sync|null
	 */
	private ?Product_Sync $sync = null;

	/**
	 * Singleton accessor.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Boots the plugin on plugins_loaded.
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		$this->settings  = new Settings();
		$this->api       = new Api_Client( $this->settings );
		$this->stores    = new Store_Service( $this->api );
		$this->post_type = new Post_Type( $this->settings );
		$this->state     = new Sync_State();
		$this->lock      = new Sync_Lock();
		$this->renderer  = new Renderer( $this->settings );

		$this->post_type->hooks();
		( new Cron( $this->settings, array( $this, 'sync' ), $this->state ) )->hooks();
		( new Shortcodes( $this->renderer ) )->hooks();
		( new Frontend( $this->settings, $this->renderer ) )->hooks();
		( new Elementor\Integration() )->hooks();

		if ( is_admin() ) {
			( new Admin\Admin( $this ) )->hooks();
			( new Admin\Product_Metabox() )->hooks();
		}

		add_filter( 'plugin_action_links_' . LCS_BASENAME, array( $this, 'action_links' ) );

		/**
		 * Fires when Lemon Catalog Sync is ready.
		 *
		 * @param Plugin $plugin Plugin instance.
		 */
		do_action( 'lcs_loaded', $this );
	}

	/**
	 * Adds "Settings" to the plugins list.
	 *
	 * @param array<int|string,string> $links Links.
	 * @return array<int|string,string>
	 */
	public function action_links( $links ): array {
		$links = is_array( $links ) ? $links : array();
		array_unshift(
			$links,
			'<a href="' . esc_url( admin_url( 'admin.php?page=lcs-settings' ) ) . '">' . esc_html__( 'Settings', 'lemon-catalog-sync' ) . '</a>'
		);
		return $links;
	}

	/*
	 * ---------------------------------------------------------------------
	 * Service accessors
	 * ---------------------------------------------------------------------
	 */

	/**
	 * Settings.
	 */
	public function settings(): Settings {
		return $this->settings;
	}

	/**
	 * API client.
	 */
	public function api(): Api_Client {
		return $this->api;
	}

	/**
	 * Store service.
	 */
	public function stores(): Store_Service {
		return $this->stores;
	}

	/**
	 * Post type.
	 */
	public function post_type(): Post_Type {
		return $this->post_type;
	}

	/**
	 * Sync state.
	 */
	public function state(): Sync_State {
		return $this->state;
	}

	/**
	 * Sync lock.
	 */
	public function lock(): Sync_Lock {
		return $this->lock;
	}

	/**
	 * Renderer.
	 */
	public function renderer(): Renderer {
		return $this->renderer;
	}

	/**
	 * Sync service — the same instance for manual and cron runs.
	 */
	public function sync(): Product_Sync {
		if ( null === $this->sync ) {
			$this->sync = new Product_Sync(
				$this->api,
				$this->settings,
				new Product_Repository(),
				new Product_Mapper(),
				new Image_Manager(),
				$this->state,
				$this->lock
			);
		}
		return $this->sync;
	}
}
