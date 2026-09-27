<?php
/**
 * "Kart Görünümü" metabox: WordPress-managed card fields.
 *
 * @package LCS
 */

namespace LCS\Admin;

use LCS\Meta;
use LCS\Plugin;
use LCS\Post_Type;
use LCS\Product;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Edits _lcs_compare_price, _lcs_card_subtitle and _lcs_card_badge.
 *
 * Primary save path: the "Kart Bilgilerini Kaydet" button → admin-ajax.php
 * (action lcs_save_card_data), fully independent of the block editor / REST
 * post save. The regular post Update also saves the fields, but only the ones
 * the user changed since the box was rendered or last saved.
 *
 * These keys are never written by the Lemon sync (see Meta::is_own()).
 */
final class Card_Metabox {

	public const AJAX_ACTION = 'lcs_save_card_data';
	public const AJAX_NONCE  = 'lcs_card_data_nonce';

	private const FORM_NONCE_ACTION = 'lcs_card_fields_save';
	private const FORM_NONCE_FIELD  = 'lcs_card_fields_nonce';
	private const SCRIPT_HANDLE     = 'lcs-card-metabox';

	/**
	 * Request field → meta key.
	 */
	private const FIELDS = array(
		'compare_price' => Meta::COMPARE_PRICE,
		'card_subtitle' => Meta::CARD_SUBTITLE,
		'card_badge'    => Meta::CARD_BADGE,
	);

	/**
	 * Hooks.
	 */
	public function hooks(): void {
		add_action( 'add_meta_boxes_' . Post_Type::POST_TYPE, array( $this, 'register' ) );
		add_action( 'save_post_' . Post_Type::POST_TYPE, array( $this, 'save_with_post' ), 10, 2 );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'ajax_save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Registers the metabox.
	 */
	public function register(): void {
		add_meta_box(
			'lcs-card-fields',
			__( 'Kart Görünümü', 'lemon-catalog-sync' ),
			array( $this, 'render' ),
			Post_Type::POST_TYPE,
			'side',
			'high'
		);
	}

	/**
	 * Loads the metabox script on lcs_product edit screens only.
	 *
	 * @param string $hook_suffix Admin page.
	 */
	public function enqueue( $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || Post_Type::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script( self::SCRIPT_HANDLE, LCS_URL . 'admin/js/card-metabox.js', array(), LCS_VERSION, true );
		wp_localize_script(
			self::SCRIPT_HANDLE,
			'lcsCardData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'action'  => self::AJAX_ACTION,
				'i18n'    => array(
					'saving'      => __( 'Kaydediliyor...', 'lemon-catalog-sync' ),
					'saved'       => __( '✓ Kaydedildi', 'lemon-catalog-sync' ),
					'failed'      => __( '✕ Kaydedilemedi', 'lemon-catalog-sync' ),
					'savedLong'   => __( '✓ Kart bilgileri kaydedildi.', 'lemon-catalog-sync' ),
					'failedLong'  => __( 'Kart bilgileri kaydedilemedi.', 'lemon-catalog-sync' ),
					'badResponse' => __( 'Sunucu geçersiz bir yanıt döndürdü. Lütfen sayfayı yenileyip tekrar deneyin.', 'lemon-catalog-sync' ),
					'network'     => __( 'Bağlantı hatası. Lütfen tekrar deneyin.', 'lemon-catalog-sync' ),
					'unsaved'     => __( 'Kaydedilmemiş değişiklik var.', 'lemon-catalog-sync' ),
				),
			)
		);
	}

	/**
	 * Renders the metabox. Values come straight from get_post_meta().
	 *
	 * @param WP_Post $post Post.
	 */
	public function render( $post ): void {
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$post_id = (int) $post->ID;
		$values  = self::stored_values( $post_id );
		$product = Product::from_id( $post_id );
		$lemon   = $product ? Plugin::instance()->renderer()->price_text( $product ) : '';

		$fields = array(
			'compare_price' => array(
				'label'       => __( 'Eski / Liste Fiyatı', 'lemon-catalog-sync' ),
				'placeholder' => __( 'örn. 50 veya 39.90', 'lemon-catalog-sync' ),
				'help'        => __( 'Yalnızca sayı girin; para birimi Lemon fiyatıyla aynı biçimde otomatik eklenir. Boşsa sitede hiçbir şey gösterilmez.', 'lemon-catalog-sync' ),
				'inputmode'   => 'decimal',
			),
			'card_subtitle' => array(
				'label'       => __( 'Kart Alt Açıklaması', 'lemon-catalog-sync' ),
				'placeholder' => __( 'örn. 120+ Lightroom Preset', 'lemon-catalog-sync' ),
				'help'        => '',
				'inputmode'   => 'text',
			),
			'card_badge'    => array(
				'label'       => __( 'Kart Rozeti', 'lemon-catalog-sync' ),
				'placeholder' => __( 'örn. Çok Satan, Yeni, %40 İndirim', 'lemon-catalog-sync' ),
				'help'        => '',
				'inputmode'   => 'text',
			),
		);

		// Fallback save with the regular Update (changed fields only).
		wp_nonce_field( self::FORM_NONCE_ACTION, self::FORM_NONCE_FIELD );

		printf(
			'<div class="lcs-card-fields" data-product-id="%1$d">',
			(int) $post_id
		);

		// AJAX nonce (action lcs_save_card_data), read by admin/js/card-metabox.js.
		printf(
			'<input type="hidden" class="lcs-card-fields__nonce" id="%1$s" value="%2$s" />',
			esc_attr( self::AJAX_NONCE ),
			esc_attr( wp_create_nonce( self::AJAX_ACTION ) )
		);

		if ( '' !== $lemon ) {
			echo '<p class="lcs-card-fields__lemon"><span class="dashicons dashicons-lock" aria-hidden="true"></span> ';
			echo esc_html__( 'Lemon fiyatı:', 'lemon-catalog-sync' ) . ' <strong>' . esc_html( $lemon ) . '</strong></p>';
		}

		foreach ( $fields as $name => $field ) {
			$id    = 'lcs-field-' . str_replace( '_', '-', $name );
			$value = $values[ $name ];

			echo '<p class="lcs-card-fields__row">';
			echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . '</label>';
			printf(
				'<input type="text" class="widefat lcs-card-fields__input" id="%1$s" name="lcs_%2$s" data-field="%2$s" value="%3$s" placeholder="%4$s" maxlength="%5$d" inputmode="%6$s" autocomplete="off" />',
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( $value ),
				esc_attr( $field['placeholder'] ),
				(int) Meta::CARD_MAX_LENGTH,
				esc_attr( $field['inputmode'] )
			);
			// Value as rendered / last saved: the Update fallback only writes fields that differ from it.
			printf(
				'<input type="hidden" name="lcs_%1$s_original" data-original="%1$s" value="%2$s" />',
				esc_attr( $name ),
				esc_attr( $value )
			);
			if ( '' !== $field['help'] ) {
				echo '<span class="description">' . esc_html( $field['help'] ) . '</span>';
			}
			echo '</p>';
		}

		echo '<p class="lcs-card-fields__actions">';
		echo '<button type="button" class="button button-primary lcs-card-fields__save">' . esc_html__( 'Kart Bilgilerini Kaydet', 'lemon-catalog-sync' ) . '</button>';
		echo '<span class="lcs-card-fields__state"></span>';
		echo '</p>';
		echo '<div class="lcs-card-fields__message" role="status" aria-live="polite"></div>';

		echo '<p class="lcs-card-fields__autosave"><label><input type="checkbox" class="lcs-card-fields__autosave-toggle" /> ';
		echo esc_html__( 'Alandan çıkınca otomatik kaydet', 'lemon-catalog-sync' ) . '</label></p>';

		if ( current_user_can( 'manage_options' ) ) {
			$info = self::info( $post_id );
			echo '<dl class="lcs-card-fields__info">';
			echo '<dt>' . esc_html__( 'Product ID', 'lemon-catalog-sync' ) . '</dt><dd>' . esc_html( (string) $post_id ) . '</dd>';
			echo '<dt>' . esc_html__( 'Saved Compare Price', 'lemon-catalog-sync' ) . '</dt><dd data-info="compare_price">' . esc_html( $info['compare_price'] ) . '</dd>';
			echo '<dt>' . esc_html__( 'Saved Subtitle', 'lemon-catalog-sync' ) . '</dt><dd data-info="card_subtitle">' . esc_html( $info['card_subtitle'] ) . '</dd>';
			echo '<dt>' . esc_html__( 'Saved Badge', 'lemon-catalog-sync' ) . '</dt><dd data-info="card_badge">' . esc_html( $info['card_badge'] ) . '</dd>';
			echo '</dl>';
		}

		echo '<p class="description">' . esc_html__( 'Bu alanlar yalnızca WordPress\'te yönetilir; Lemon Squeezy senkronizasyonu bunları asla değiştirmez veya silmez. Kaydetmek için sayfayı güncellemeniz gerekmez.', 'lemon-catalog-sync' ) . '</p>';
		echo '</div>';
	}

	/**
	 * Handler for admin-ajax.php?action=lcs_save_card_data.
	 *
	 * Fields: product_id, nonce, compare_price, card_subtitle, card_badge.
	 * A field sent empty is deleted; a field not sent at all is left untouched.
	 * Nothing is written unless every sent field is valid.
	 */
	public function ajax_save(): void {
		$this->discard_stray_output();

		if ( ! is_user_logged_in() ) {
			$this->fail( __( 'Oturum süresi dolmuş. Lütfen tekrar giriş yapın.', 'lemon-catalog-sync' ), 401 );
		}

		if ( ! check_ajax_referer( self::AJAX_ACTION, 'nonce', false ) ) {
			$this->fail( __( 'Güvenlik doğrulaması başarısız oldu. Sayfayı yenileyip tekrar deneyin.', 'lemon-catalog-sync' ), 403 );
		}

		$product_id = isset( $_POST['product_id'] ) && is_scalar( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;

		if ( $product_id <= 0 || Post_Type::POST_TYPE !== get_post_type( $product_id ) ) {
			$this->fail( __( 'Geçersiz ürün.', 'lemon-catalog-sync' ), 400 );
		}

		if ( ! current_user_can( 'edit_post', $product_id ) ) {
			$this->fail( __( 'Bu ürünü düzenleme yetkiniz yok.', 'lemon-catalog-sync' ), 403 );
		}

		$input = array();
		foreach ( array_keys( self::FIELDS ) as $name ) {
			if ( isset( $_POST[ $name ] ) && is_scalar( $_POST[ $name ] ) ) {
				$input[ $name ] = (string) wp_unslash( $_POST[ $name ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in prepare().
			}
		}

		if ( ! $input ) {
			$this->fail( __( 'Kaydedilecek alan gönderilmedi.', 'lemon-catalog-sync' ), 400 );
		}

		$changes = $this->prepare( $input );
		if ( is_string( $changes ) ) {
			$this->fail( $changes, 422 );
		}

		$this->apply( $product_id, $changes );

		wp_send_json_success(
			array(
				'message' => __( 'Kart bilgileri kaydedildi.', 'lemon-catalog-sync' ),
				'values'  => self::stored_values( $product_id ),
				'info'    => self::info( $product_id ),
			)
		);
	}

	/**
	 * Fallback: regular post Update. Only fields the user changed since the box
	 * was rendered (or last AJAX-saved) are written, so a stale form never
	 * overwrites newer values. The sync's wp_insert_post() never gets here
	 * (no nonce).
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public function save_with_post( $post_id, $post = null ): void {
		$post_id = (int) $post_id;

		if ( ! isset( $_POST[ self::FORM_NONCE_FIELD ] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::FORM_NONCE_FIELD ] ) ), self::FORM_NONCE_ACTION ) ) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( Post_Type::POST_TYPE !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$input = array();
		foreach ( array_keys( self::FIELDS ) as $name ) {
			$field    = 'lcs_' . $name;
			$original = $field . '_original';
			if ( ! isset( $_POST[ $field ], $_POST[ $original ] ) || ! is_scalar( $_POST[ $field ] ) || ! is_scalar( $_POST[ $original ] ) ) {
				continue;
			}
			$value = (string) wp_unslash( $_POST[ $field ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in prepare().
			if ( $value !== (string) wp_unslash( $_POST[ $original ] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- comparison only.
				$input[ $name ] = $value;
			}
		}

		if ( ! $input ) {
			return;
		}

		$changes = $this->prepare( $input );
		if ( is_array( $changes ) ) {
			$this->apply( $post_id, $changes );
		}
	}

	/**
	 * Validates and sanitizes submitted fields.
	 *
	 * @param array<string,string> $input Field → raw value.
	 * @return array<string,string>|string Meta key → sanitized value ('' = delete), or an error message.
	 */
	private function prepare( array $input ) {
		$changes = array();

		foreach ( $input as $name => $raw ) {
			$key = self::FIELDS[ $name ];

			if ( '' === trim( $raw ) ) {
				$changes[ $key ] = '';
				continue;
			}

			if ( Meta::COMPARE_PRICE === $key ) {
				$value = Meta::sanitize_compare_price( $raw );
				if ( '' === $value ) {
					return __( 'Eski / Liste Fiyatı geçerli bir sayı olmalı (örn. 50 veya 39.90).', 'lemon-catalog-sync' );
				}
			} else {
				$value = Meta::sanitize_card_value( $raw );
			}

			$changes[ $key ] = $value;
		}

		return $changes;
	}

	/**
	 * Writes prepared values with update_post_meta() / delete_post_meta().
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string,string> $changes Meta key → value ('' = delete).
	 */
	private function apply( int $post_id, array $changes ): void {
		foreach ( $changes as $key => $value ) {
			if ( '' === $value ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, wp_slash( $value ) );
			}
		}
	}

	/**
	 * Stored values, read with get_post_meta().
	 *
	 * @param int $post_id Post ID.
	 * @return array<string,string>
	 */
	private static function stored_values( int $post_id ): array {
		$values = array();
		foreach ( self::FIELDS as $name => $key ) {
			$value           = get_post_meta( $post_id, $key, true );
			$values[ $name ] = is_scalar( $value ) ? (string) $value : '';
		}
		return $values;
	}

	/**
	 * Read-only info for administrators (formatted as on the site).
	 *
	 * @param int $post_id Post ID.
	 * @return array<string,string>
	 */
	private static function info( int $post_id ): array {
		$product = Product::from_id( $post_id );
		$compare = $product ? Plugin::instance()->renderer()->compare_price_text( $product ) : '';

		return array(
			'compare_price' => '' !== $compare ? $compare : '—',
			'card_subtitle' => $product && '' !== $product->card_subtitle() ? $product->card_subtitle() : '—',
			'card_badge'    => $product && '' !== $product->card_badge() ? $product->card_badge() : '—',
		);
	}

	/**
	 * Sends a JSON error and stops.
	 *
	 * @param string $message Reason shown in the box.
	 * @param int    $status  HTTP status.
	 */
	private function fail( string $message, int $status ): void {
		wp_send_json_error(
			array(
				'message' => __( 'Kart bilgileri kaydedilemedi.', 'lemon-catalog-sync' ) . ' ' . $message,
			),
			$status
		);
	}

	/**
	 * Drops anything already printed into the output buffer before this
	 * handler ran (e.g. another plugin's PHP notice), so the response stays
	 * valid JSON.
	 */
	private function discard_stray_output(): void {
		if ( ob_get_level() > 0 && ob_get_length() ) {
			ob_clean();
		}
	}
}
