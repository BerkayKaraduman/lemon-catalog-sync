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
 * Thin, read-only client. The API key comes only from Credentials and is only
 * ever placed in the Authorization header of a server-side wp_remote_get().
 * Every failure is returned as WP_Error with the key stripped from the message.
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
	 * GET a JSON:API endpoint with the configured key (Credentials::get_api_key()).
	 *
	 * @param string               $endpoint Path relative to /v1/, e.g. "products".
	 * @param array<string,scalar> $query    Query parameters.
	 * @return array<string,mixed>|WP_Error Decoded document.
	 */
	public function get( string $endpoint, array $query = array() ): array|WP_Error {
		return $this->request( $endpoint, $query, Credentials::get_api_key() );
	}

	/**
	 * Performs the request. The only place the Authorization header is built.
	 *
	 * @param string               $endpoint Endpoint.
	 * @param array<string,scalar> $query    Query parameters.
	 * @param string               $key      API key.
	 * @return array<string,mixed>|WP_Error
	 */
	private function request( string $endpoint, array $query, string $key ): array|WP_Error {
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
	 * Verifies the configured key by calling GET /v1/users/me.
	 *
	 * @return array{name:string}|WP_Error
	 */
	public function test_connection(): array|WP_Error {
		return $this->account( $this->get( 'users/me' ) );
	}

	/**
	 * Verifies a candidate key typed in the settings form before it is stored.
	 * The candidate is used for this single request only and never persisted here.
	 *
	 * @param string $candidate Candidate key.
	 * @return array{name:string}|WP_Error
	 */
	public function test_candidate_key( string $candidate ): array|WP_Error {
		return $this->account( $this->request( 'users/me', array(), trim( $candidate ) ) );
	}

	/**
	 * Extracts the account name from a users/me document.
	 *
	 * @param array<string,mixed>|WP_Error $doc Document.
	 * @return array{name:string}|WP_Error
	 */
	private function account( array|WP_Error $doc ): array|WP_Error {
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
	 * Removes the configured key and the key used for this request from a string.
	 *
	 * @param string $message Message.
	 * @param string $key     Key used for the request.
	 */
	private function redact( string $message, string $key ): string {
		return Credentials::redact( $message, $key );
	}
}
