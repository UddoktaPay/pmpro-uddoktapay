<?php
/**
 * UddoktaPay API Client
 *
 * Handles communication with the UddoktaPay API for payment processing.
 *
 * @package UddoktaPay\PMPro\Api
 * @since   1.0.0
 */

namespace UddoktaPay\PMPro\Api;

/**
 * Class ApiClient
 *
 * Provides methods for interacting with the UddoktaPay payment gateway API.
 *
 * @package UddoktaPay\PMPro\Api
 * @since   1.0.0
 */
class ApiClient {

	/**
	 * API Key for authentication.
	 *
	 * @since  1.0.0
	 * @var    string
	 */
	private string $api_key;

	/**
	 * Base URL for API requests.
	 *
	 * @since  1.0.0
	 * @var    string
	 */
	private string $api_url;

	/**
	 * API endpoint paths.
	 *
	 * @since  1.0.0
	 * @var    array
	 */
	private array $endpoints = array(
		'CHECKOUT' => 'checkout-v2',
		'VERIFY'   => 'verify-payment',
	);

	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 * @param  string $api_key API Key for authentication.
	 * @param  string $api_url Base URL for API requests.
	 */
	public function __construct( string $api_key, string $api_url ) {
		$this->api_key = $api_key;
		$this->api_url = $this->normalize_url( $api_url );
	}

	/**
	 * Create a new payment request.
	 *
	 * @since  1.0.0
	 * @param  array $data Payment request data.
	 * @return array Response from the API.
	 * @throws ApiException If the API request fails.
	 */
	public function createPayment( array $data ): array {
		return $this->send_request(
			$this->endpoints['CHECKOUT'],
			$data,
			'POST'
		);
	}

	/**
	 * Verify a payment's status.
	 *
	 * @since  1.0.0
	 * @param  string $invoice_id Invoice ID to verify.
	 * @return array Response from the API.
	 * @throws ApiException If the API request fails.
	 */
	public function verifyPayment( string $invoice_id ): array {
		return $this->send_request(
			$this->endpoints['VERIFY'],
			array( 'invoice_id' => $invoice_id ),
			'POST'
		);
	}

	/**
	 * Send request to the API.
	 *
	 * @since  1.0.0
	 * @param  string $endpoint API endpoint path.
	 * @param  array  $data    Request data.
	 * @param  string $method  HTTP method to use.
	 * @return array Response from the API.
	 * @throws ApiException If the request fails.
	 */
	private function send_request( string $endpoint, array $data = array(), string $method = 'POST' ): array {
		$url = $this->build_url( $endpoint );

		$args = array(
			'method'  => $method,
			'timeout' => 45,
			'headers' => array(
				'RT-UDDOKTAPAY-API-KEY' => $this->api_key,
				'Content-Type'          => 'application/json',
				'Accept'                => 'application/json',
			),
		);

		if ( in_array( $method, array( 'POST', 'PUT' ), true ) ) {
			$args['body'] = wp_json_encode( $data );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			throw new ApiException( esc_html( $response->get_error_message() ) );
		}

		$body   = wp_remote_retrieve_body( $response );
		$result = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			throw new ApiException( esc_html__( 'Invalid JSON response from API', 'pmpro-uddoktapay' ) );
		}

		if ( ! empty( $result['error'] ) ) {
			throw new ApiException( esc_html( $result['error'] ) );
		}

		return $result;
	}

	/**
	 * Build complete API URL.
	 *
	 * @since  1.0.0
	 * @param  string $endpoint API endpoint path.
	 * @return string Complete API URL.
	 */
	private function build_url( string $endpoint ): string {
		return rtrim( $this->api_url, '/' ) . '/' . $endpoint;
	}

	/**
	 * Normalize API URL by ensuring correct format.
	 *
	 * @since  1.0.0
	 * @param  string $url API URL to normalize.
	 * @return string Normalized URL.
	 */
	private function normalize_url( string $url ): string {
		$url     = rtrim( $url, '/' );
		$api_pos = strpos( $url, '/api' );

		if ( false !== $api_pos ) {
			$url = substr( $url, 0, $api_pos + 4 );
		}

		return $url;
	}
}
