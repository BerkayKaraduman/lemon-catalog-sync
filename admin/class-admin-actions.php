<?php
/**
 * admin-post.php handlers (nonce + capability protected).
 *
 * @package LCS
 */

namespace LCS\Admin;

use LCS\Cron;
use LCS\Plugin;
use LCS\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Save settings, test connection, sync now.
 */
final class Admin_Actions {

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
		add_action( 'admin_post_lcs_save_settings', array( $this, 'save_settings' ) );
		add_action( 'admin_post_lcs_test_connection', array( $this, 'test_connection' ) );
		add_action( 'admin_post_lcs_sync_now', array( $this, 'sync_now' ) );
	}

	/**
	 * Per-user notice transient key.
	 */
	public static function notice_key(): string {
		return 'lcs_notice_' . get_current_user_id();
	}

	/**
	 * Saves settings.
	 */
	public function save_settings(): void {
		$this->verify( 'lcs_save_settings' );

		$settings = $this->plugin->settings();
		$stores   = $this->plugin->stores();
		$state    = $this->plugin->state();
		$old      = $settings->all();
		$choices  = Settings::choices();
		$messages = array();
		$type     = 'success';

		// Nonce verified in verify(); every field is sanitized individually below.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$input = isset( $_POST['lcs'] ) && is_array( $_POST['lcs'] ) ? wp_unslash( $_POST['lcs'] ) : array();

		// API key: an empty field keeps the stored key; the key is never echoed back.
		if ( ! $settings->has_constant_key() ) {
			$new_key = isset( $input['api_key'] ) ? sanitize_text_field( (string) $input['api_key'] ) : '';

			if ( ! empty( $input['remove_api_key'] ) ) {
				$settings->set_api_key( '' );
				$stores->clear_cache();
				$state->reset_connection();
				$messages[] = __( 'API key removed.', 'lemon-catalog-sync' );
			} elseif ( '' !== $new_key ) {
				$test = $this->plugin->api()->test_connection( $new_key );
				if ( is_wp_error( $test ) ) {
					$type       = 'error';
					$messages[] = __( 'The new API key was not saved:', 'lemon-catalog-sync' ) . ' ' . $test->get_error_message();
				} else {
					$settings->set_api_key( $new_key );
					$stores->clear_cache();
					$state->record_connection( true, $this->connected_message( $test['name'] ) );
					$messages[] = __( 'API key saved and verified.', 'lemon-catalog-sync' );
				}
			}
		}

		$values = array();

		foreach ( array( 'sync_frequency', 'checkout_mode', 'image_mode', 'display_mode' ) as $field ) {
			$value            = isset( $input[ $field ] ) ? sanitize_key( (string) $input[ $field ] ) : '';
			$values[ $field ] = isset( $choices[ $field ][ $value ] ) ? $value : $old[ $field ];
		}

		// Store: must be one of the stores the key can access.
		$store_id   = isset( $input['store_id'] ) ? preg_replace( '/\D/', '', (string) $input['store_id'] ) : '';
		$store_list = $settings->has_api_key() ? $stores->get_stores() : array();
		$store_list = is_wp_error( $store_list ) ? $stores->cached_stores() : $store_list;

		if ( '' === $store_id && 1 === count( $store_list ) ) {
			$store_id = (string) array_key_first( $store_list );
		}

		if ( '' !== $store_id && isset( $store_list[ $store_id ] ) ) {
			$values['store_id']       = $store_id;
			$values['store_name']     = $store_list[ $store_id ]['name'];
			$values['store_currency'] = '' !== $store_list[ $store_id ]['currency'] ? $store_list[ $store_id ]['currency'] : 'USD';
		} elseif ( '' === $store_id ) {
			$values['store_id']   = '';
			$values['store_name'] = '';
		} elseif ( $store_id !== $old['store_id'] ) {
			$type       = 'error';
			$messages[] = __( 'The selected store is not available for this API key.', 'lemon-catalog-sync' );
		}

		// URL bases.
		$product_base  = isset( $input['product_base'] ) ? sanitize_title( (string) $input['product_base'] ) : '';
		$category_base = isset( $input['category_base'] ) ? sanitize_title( (string) $input['category_base'] ) : '';
		$product_base  = '' !== $product_base ? $product_base : 'urun';
		$category_base = '' !== $category_base ? $category_base : 'urun-kategori';

		if ( $product_base === $category_base ) {
			$type       = 'error';
			$messages[] = __( 'Product URL base and category URL base must be different. URL bases were not changed.', 'lemon-catalog-sync' );
		} else {
			$values['product_base']  = $product_base;
			$values['category_base'] = $category_base;
		}

		$values['delete_data_on_uninstall'] = ! empty( $input['delete_data_on_uninstall'] );

		$settings->update( $values );

		// Rewrite rules are flushed once on the next init by Post_Type::maybe_flush_rewrite_rules().
		Cron::sync_schedule( (string) $settings->get( 'sync_frequency' ) );

		array_unshift( $messages, __( 'Settings saved.', 'lemon-catalog-sync' ) );
		$this->redirect( 'lcs-settings', $type, implode( ' ', $messages ) );
	}

	/**
	 * Tests the configured key and refreshes the store list.
	 */
	public function test_connection(): void {
		$this->verify( 'lcs_test_connection' );

		$state  = $this->plugin->state();
		$result = $this->plugin->api()->test_connection();

		if ( is_wp_error( $result ) ) {
			$state->record_connection( false, $result->get_error_message() );
			$this->redirect( 'lcs-settings', 'error', $result->get_error_message() );
		}

		$stores = $this->plugin->stores()->get_stores( true );
		if ( is_wp_error( $stores ) ) {
			$state->record_connection( false, $stores->get_error_message() );
			$this->redirect( 'lcs-settings', 'error', $stores->get_error_message() );
		}

		$settings = $this->plugin->settings();
		if ( '' === $settings->store_id() && 1 === count( $stores ) ) {
			$store = reset( $stores );
			$settings->update(
				array(
					'store_id'       => $store['id'],
					'store_name'     => $store['name'],
					'store_currency' => '' !== $store['currency'] ? $store['currency'] : 'USD',
				)
			);
		}

		$message = $this->connected_message( $result['name'] );
		$state->record_connection( true, $message );

		$this->redirect(
			'lcs-settings',
			'success',
			$message . ' ' . sprintf(
				/* translators: %d: number of stores. */
				_n( '%d store available.', '%d stores available.', count( $stores ), 'lemon-catalog-sync' ),
				count( $stores )
			)
		);
	}

	/**
	 * Manual sync (same service as cron).
	 */
	public function sync_now(): void {
		$this->verify( 'lcs_sync_now' );

		$result = $this->plugin->sync()->run( 'manual' );

		if ( is_wp_error( $result ) ) {
			$this->redirect( 'lcs-sync', 'error', $result->get_error_message() );
		}

		$message = __( 'Sync completed.', 'lemon-catalog-sync' ) . ' ' . $this->plugin->state()->summary( $result );
		$type    = 'success';

		if ( ! empty( $result['image_errors'] ) ) {
			$type     = 'warning';
			$message .= ' ' . sprintf(
				/* translators: %d: number of images. */
				__( '%d image(s) could not be imported; the remote Lemon image is used instead.', 'lemon-catalog-sync' ),
				(int) $result['image_errors']
			);
		}

		if ( ! empty( $result['failed'] ) ) {
			$type = 'warning';
		}

		$this->redirect( 'lcs-sync', $type, $message );
	}

	/**
	 * Capability + nonce check.
	 *
	 * @param string $action Nonce action.
	 */
	private function verify( string $action ): void {
		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'lemon-catalog-sync' ), 403 );
		}
		check_admin_referer( $action );
	}

	/**
	 * "Connected as …" message.
	 *
	 * @param string $name Account name.
	 */
	private function connected_message( string $name ): string {
		return '' !== $name
			/* translators: %s: Lemon Squeezy account name. */
			? sprintf( __( 'Connected to Lemon Squeezy as %s.', 'lemon-catalog-sync' ), $name )
			: __( 'Connected to Lemon Squeezy.', 'lemon-catalog-sync' );
	}

	/**
	 * Stores a notice and redirects back.
	 *
	 * @param string $page    Admin page slug.
	 * @param string $type    Notice type.
	 * @param string $message Message.
	 * @return never
	 */
	private function redirect( string $page, string $type, string $message ): never {
		set_transient(
			self::notice_key(),
			array(
				'type'    => $type,
				'message' => $message,
			),
			MINUTE_IN_SECONDS * 5
		);
		wp_safe_redirect( admin_url( 'admin.php?page=' . $page ) );
		exit;
	}
}
