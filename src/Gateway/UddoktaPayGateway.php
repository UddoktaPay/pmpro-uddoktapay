<?php
/**
 * UddoktaPay Gateway Implementation
 *
 * Handles payment processing through the UddoktaPay payment gateway.
 *
 * @package UddoktaPay\PMPro\Gateway
 * @since   1.0.0
 */

namespace UddoktaPay\PMPro\Gateway;

use UddoktaPay\PMPro\Api\ApiClient;

/**
 * Class UddoktaPayGateway
 *
 * Implements the payment gateway interface for UddoktaPay integration.
 *
 * @package UddoktaPay\PMPro\Gateway
 * @since   1.0.0
 */
class UddoktaPayGateway implements GatewayInterface {

	/**
	 * API Client instance.
	 *
	 * @since 1.0.0
	 * @var   ApiClient
	 */
	private ApiClient $api_client;

	/**
	 * Constructor.
	 *
	 * Initializes the API client with credentials from WordPress options.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->api_client = new ApiClient(
			get_option( 'pmpro_uddoktapay_api_key' ),
			get_option( 'pmpro_uddoktapay_api_url' )
		);
	}

	/**
	 * Process a payment transaction.
	 *
	 * @since  1.0.0
	 * @param  \MemberOrder $order Order to process.
	 * @return bool Whether the payment was processed successfully.
	 */
	public function processPayment( &$order ): bool {
		try {
			$payment_data = $this->preparePaymentData( $order );
			$response     = $this->api_client->createPayment( $payment_data );
			if ( ! empty( $response['payment_url'] ) ) {
				// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Payment gateway trusted redirect URL
				wp_redirect( $response['payment_url'] );
				exit;
			}

			if ( ! empty( $response['message'] ) ) {
				/* translators: %s: Error message from payment gateway */
				$order->error = sprintf( esc_html__( 'Gateway Error: %s', 'pmpro-uddoktapay' ), esc_html( $response['message'] ) );
			} else {
				$order->error = esc_html__( 'Failed to get payment URL from UddoktaPay', 'pmpro-uddoktapay' );
			}
			return false;
		} catch ( \Exception $e ) {
			$order->error = $e->getMessage();
			return false;
		}
	}

	/**
	 * Verify a payment transaction.
	 *
	 * @since  1.0.0
	 * @param  string $invoice_id Invoice ID to verify.
	 * @return array Payment verification response.
	 */
	public function verifyPayment( string $invoice_id ): array {
		return $this->api_client->verifyPayment( $invoice_id );
	}

	/**
	 * Prepare payment data for gateway submission.
	 *
	 * @since  1.0.0
	 * @param  \MemberOrder $order Order to prepare payment data for.
	 * @return array Prepared payment data.
	 */
	public function preparePaymentData( &$order ): array {
		$base_webhook_args = array(
			'action' => 'uddoktapay-pmpro-webhook',
		);

		$success_args = array_merge(
			$base_webhook_args,
			array(
				'type'     => 'success',
				'order_id' => $order->id,
			)
		);

		$cancel_args = array_merge(
			$base_webhook_args,
			array(
				'type'     => 'cancel',
				'order_id' => $order->id,
			)
		);

		$ipn_args = array_merge(
			$base_webhook_args,
			array(
				'type' => 'ipn',
			)
		);

		return array(
			'full_name'    => $order->billing->name,
			'email'        => pmpro_getParam( 'bemail', 'REQUEST' ),
			'amount'       => $this->calculateAmount( $order ),
			'metadata'     => array(
				'order_id'      => $order->id,
				'user_id'       => $order->user_id,
				'membership_id' => $order->membership_id,
				'order_code'    => $order->code,
			),
			'return_type'  => 'GET',
			'redirect_url' => add_query_arg( $success_args, admin_url( 'admin-ajax.php' ) ),
			'cancel_url'   => add_query_arg( $cancel_args, admin_url( 'admin-ajax.php' ) ),
			'webhook_url'  => add_query_arg( $ipn_args, admin_url( 'admin-ajax.php' ) ),
			'success_url'  => add_query_arg( $success_args, admin_url( 'admin-ajax.php' ) ),
		);
	}

	/**
	 * Calculate total order amount including tax.
	 *
	 * @since  1.0.0
	 * @param  \MemberOrder $order Order to calculate amount for.
	 * @return float Calculated order amount.
	 */
	private function calculateAmount( $order ): float {
		$initial_payment     = $order->subtotal;
		$initial_payment_tax = $order->getTaxForPrice( $initial_payment );
		return pmpro_round_price( (float) $initial_payment + (float) $initial_payment_tax );
	}
}
