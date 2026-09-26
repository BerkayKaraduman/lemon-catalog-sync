<?php
/**
 * Lemon Squeezy JSON:API client built on the WordPress HTTP API.
 *
 * @package LCS
 */

namespace LCS;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Thin, read-only client. Every failure is returned as WP_Error; the API key
 * is stripped from every message before it leaves this class.
 */
final class Api_Client {

	public const BASE_URL = 'https://api.lemonsqueezy.com/v1/';

	/**
	 * Default Retry-After (seconds) when a 429 carries no usable header.
	 */
	private const DEFAULT_RETRY_AFTER = 60;

	/**
	 * Settings.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * GET a JSON:API endpoint.
	 *
	 * @param string               $endpoint Path relative to /v1/, e.g. "products".
	 * @param array<string,scalar> $query    Query parameters.
	 * @param string|null          $api_key  Override key (used to test a key before saving it).
	 * @return array<string,mixed>|WP_Error Decoded document.
	 */
	public function get( string $endpoint, array $query = array(), ?string $api_key = null ): array|WP_Error {
		$key = $api_key ?? $this->settings->get_api_key();
		if ( '' === $key ) {
			return new WP_Error( 'lcs_no_api_key', __( 'No Lemon Squeezy API key is configured.', 'lemon-catalog-sync' ) );
		}

		$url = self::BASE_URL . ltrim( $endpoint, '/' );
		if ( $query ) {
			$url .= '?' . http_build_query( $query, '', '&', PHP_QUERY_RFC3986 );
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 30,
				'redirection' => 2,
				'user-agent'  => 'LemonCatalogSync/' . LCS_VERSION . '; WordPress/' . get_bloginfo( 'version' ),
				'headers'     => array(
					'Accept'        => 'application/vnd.api+json',
					'Content-Type'  => 'application/vnd.api+json',
					'Authorization' => 'Bearer ' . $key,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'lcs_http_error',
				sprintf(
					/* translators: %s: transport error message. */
					__( 'Could not reach the Lemon Squeezy API: %s', 'lemon-catalog-sync' ),
					$this->redact( $response->get_error_message(), $key )
				)
			);
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = (string) wp_remote_retrieve_body( $response );
		$data   = json_decode( $body, true );

		if ( $status < 200 || $status >= 300 ) {
			return $this->http_error( $status, is_array( $data ) ? $data : array(), $response, $key );
		}

		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'lcs_invalid_json',
				sprintf(
					/* translators: %s: JSON error message. */
					__( 'Lemon Squeezy returned an invalid JSON response (%s).', 'lemon-catalog-sync' ),
					json_last_error_msg()
				),
				array( 'status' => $status )
			);
		}

		return $data;
	}

	/**
	 * Verifies a key by calling GET /v1/users/me.
	 *
	 * @param string|null $api_key Key to test; defaults to the configured key.
	 * @return array{name:string}|WP_Error
	 */
	public function test_connection( ?string $api_key = null ): array|WP_Error {
		$doc = $this->get( 'users/me', array(), $api_key );
		if ( is_wp_error( $doc ) ) {
			return $doc;
		}

		$name = $doc['data']['attributes']['name'] ?? '';
		return array( 'name' => is_string( $name ) ? sanitize_text_field( $name ) : '' );
	}

	/**
	 * Builds a WP_Error for a non-2xx response.
	 *
	 * @param int                  $status   HTTP status.
	 * @param array<string,mixed>  $data     Decoded body (may be empty).
	 * @param array<string,mixed>  $response Raw WP HTTP response.
	 * @param string               $key      API key (for redaction).
	 */
	private function http_error( int $status, array $data, array $response, string $key ): WP_Error {
		$detail = '';
		if ( isset( $data['errors'][0] ) && is_array( $data['errors'][0] ) ) {
			$first  = $data['errors'][0];
			$detail = (string) ( $first['detail'] ?? $first['title'] ?? '' );
			$detail = $this->redact( sanitize_text_field( $detail ), $key );
			$detail = function_exists( 'mb_substr' ) ? mb_substr( $detail, 0, 300 ) : substr( $detail, 0, 300 );
		}

		$error_data = array( 'status' => $status );

		switch ( true ) {
			case 401 === $status:
				$code    = 'lcs_unauthorized';
				$message = __( 'Lemon Squeezy rejected the API key (401 Unauthorized). Check that the key is correct and has not been revoked.', 'lemon-catalog-sync' );
				break;
			case 403 === $status:
				$code    = 'lcs_forbidden';
				$message = __( 'The API key has no access to this resource (403 Forbidden).', 'lemon-catalog-sync' );
				break;
			case 404 === $status:
				$code    = 'lcs_not_found';
				$message = __( 'The requested Lemon Squeezy resource was not found (404).', 'lemon-catalog-sync' );
				break;
			case 429 === $status:
				$code                      = 'lcs_rate_limited';
				$error_data['retry_after'] = $this->retry_after( $response );
				$message                   = sprintf(
					/* translators: %d: seconds. */
					__( 'Lemon Squeezy rate limit reached (429). Retry in about %d seconds.', 'lemon-catalog-sync' ),
					$error_data['retry_after']
				);
				break;
			case $status >= 500:
				$code = 'lcs_server_error';
				/* translators: %d: HTTP status code. */
				$message = sprintf( __( 'Lemon Squeezy API is temporarily unavailable (HTTP %d).', 'lemon-catalog-sync' ), $status );
				break;
			default:
				$code = 'lcs_http_status';
				/* translators: %d: HTTP status code. */
				$message = sprintf( __( 'Unexpected response from Lemon Squeezy (HTTP %d).', 'lemon-catalog-sync' ), $status );
		}

		if ( '' !== $detail ) {
			$message .= ' ' . $detail;
		}

		return new WP_Error( $code, $message, $error_data );
	}

	/**
	 * Parses the Retry-After header (seconds or HTTP date).
	 *
	 * @param array<string,mixed> $response Raw response.
	 */
	private function retry_after( array $response ): int {
		$header = wp_remote_retrieve_header( $response, 'retry-after' );
		if ( is_array( $header ) ) {
			$header = (string) reset( $header );
		}
		$header = trim( (string) $header );

		if ( '' === $header ) {
			return self::DEFAULT_RETRY_AFTER;
		}

		if ( ctype_digit( $header ) ) {
			return max( 1, min( 3600, (int) $header ) );
		}

		$time = strtotime( $header );
		return false === $time ? self::DEFAULT_RETRY_AFTER : max( 1, min( 3600, $time - time() ) );
	}

	/**
	 * Removes the API key from any string.
	 *
	 * @param string $message Message.
	 * @param string $key     Key.
	 */
	private function redact( string $message, string $key ): string {
		return '' === $key ? $message : str_replace( $key, '[redacted]', $message );
	}
}
