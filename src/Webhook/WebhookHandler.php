<?php
/**
 * UddoktaPay PMPro Webhook Handler
 *
 * @package UddoktaPay\PMPro
 * @since 1.0.0
 */

namespace UddoktaPay\PMPro\Webhook;

use UddoktaPay\PMPro\Gateway\GatewayInterface;

/**
 * Class WebhookHandler
 *
 * Handles webhook requests from UddoktaPay payment gateway
 *
 * @package UddoktaPay\PMPro\Webhook
 */
class WebhookHandler {

	/**
	 * Gateway instance
	 *
	 * @var GatewayInterface
	 */
	private GatewayInterface $gateway;

	/**
	 * Constructor
	 *
	 * @param GatewayInterface $gateway Gateway instance.
	 */
	public function __construct( GatewayInterface $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * Handle webhook requests
	 *
	 * @return void
	 * @throws \Exception If webhook type is invalid.
	 */
	public function handleWebhook(): void {
		try {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification not required for payment gateway webhooks.
			$type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';
			if ( empty( $type ) ) {
				throw new \Exception( 'Invalid webhook type' );
			}

			// For admin-side operations, verify user capabilities.
			if ( ! in_array( $type, array( 'ipn', 'success', 'cancel' ), true ) ) {
				throw new \Exception( 'Unauthorized access' );
			}

			switch ( $type ) {
				case 'ipn':
					$this->handleIPN();
					break;
				case 'success':
					$this->handleSuccess();
					break;
				case 'cancel':
					$this->handleCancel();
					break;
				default:
					throw new \Exception( 'Unknown webhook type' );
			}
		} catch ( \Exception $e ) {
			$this->logError( 'Webhook Error: ' . $e->getMessage() );
			wp_die( esc_html( $e->getMessage() ), 'Webhook Error', array( 'response' => 400 ) );
		}
	}

	/**
	 * Handle IPN (Instant Payment Notification) requests
	 *
	 * @return void
	 * @throws \Exception If payload is invalid.
	 */
	private function handleIPN(): void {
		$payload = file_get_contents( 'php://input' );
		if ( empty( $payload ) ) {
			throw new \Exception( 'Empty payload' );
		}

		$this->logDebug( 'IPN Payload: ' . $payload );

		$data = json_decode( $payload, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			throw new \Exception( 'Invalid JSON payload' );
		}

		if ( empty( $data['metadata']['order_id'] ) ) {
			throw new \Exception( 'Order ID not found in payload' );
		}

		$order_id = intval( $data['metadata']['order_id'] );
		$order    = new \MemberOrder( $order_id );

		if ( empty( $order ) || empty( $order->id ) ) {
			throw new \Exception( 'Order not found: ' . esc_html( $order_id ) );
		}

		$response = $this->gateway->verifyPayment( $data['invoice_id'] );

		// Validate the payment.
		if ( ! $this->validatePayment( $order, $response ) ) {
			$this->logError( 'Payment validation failed in IPN.' );
			$this->failPayment( $order, 'validation_failed' );
			throw new \Exception( 'Payment validation failed' );
		}

		$this->completePayment( $order, $response );
		wp_send_json_success( array( 'message' => 'Payment completed' ) );
	}

	/**
	 * Handle successful payment redirects
	 *
	 * @return void
	 * @throws \Exception If payment validation fails.
	 */
	private function handleSuccess(): void {
		$invoice_id = filter_input( INPUT_GET, 'invoice_id' );
		if ( ! $invoice_id ) {
			throw new \Exception( 'Invalid Invoice ID' );
		}

		$order_id = filter_input( INPUT_GET, 'order_id', FILTER_SANITIZE_NUMBER_INT );
		if ( ! $order_id ) {
			throw new \Exception( 'Invalid order ID' );
		}

		$order = new \MemberOrder( $order_id );
		if ( empty( $order ) || empty( $order->id ) ) {
			throw new \Exception( 'Order not found' );
		}

		$response = $this->gateway->verifyPayment( $invoice_id );

		if ( ! $this->validatePayment( $order, $response ) ) {
			$this->logError( 'Payment validation failed.' );
			$this->failPayment( $order, 'validation_failed' );
			wp_safe_redirect( pmpro_url( 'account' ) );
			exit;
		}

		$this->completePayment( $order, $response );
		wp_safe_redirect( pmpro_url( 'confirmation', '?level=' . $order->membership_id ) );
		exit;
	}

	/**
	 * Handle cancelled payment redirects
	 *
	 * @return void
	 */
	private function handleCancel(): void {
		$order_id = filter_input( INPUT_GET, 'order_id', FILTER_SANITIZE_NUMBER_INT );
		if ( $order_id ) {
			$order = new \MemberOrder( $order_id );
			if ( ! empty( $order ) && ! empty( $order->id ) ) {
				$this->failPayment( $order, 'cancelled' );
			}
		}

		wp_safe_redirect( pmpro_url( 'levels' ) );
		exit;
	}

	/**
	 * Complete the payment process
	 *
	 * @param \MemberOrder $order    Order instance.
	 * @param array        $response Payment gateway response.
	 * @return void
	 */
	private function completePayment( \MemberOrder $order, array $response ): void {
		// Build detailed payment note.
		/* translators: %s: Formatted date and time of payment completion */
		$note = sprintf( __( 'Payment completed via UddoktaPay on %s.', 'pmpro-uddoktapay' ), date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ) . "\n";
		/* translators: %s: Transaction ID from payment gateway */
		$note .= sprintf( __( 'Transaction ID: %s', 'pmpro-uddoktapay' ), $response['transaction_id'] ) . "\n";
		/* translators: %s: Payment method used */
		$note .= sprintf( __( 'Payment Method: %s', 'pmpro-uddoktapay' ), $response['payment_method'] ) . "\n";
		/* translators: %s: Sender's phone number */
		$note .= sprintf( __( 'Sender Number: %s', 'pmpro-uddoktapay' ), $response['sender_number'] );

		$order->getMembershipLevel();
		$order->status                 = 'success';
		$order->payment_transaction_id = $response['transaction_id'];
		$order->notes                  = $note;
		$order->saveOrder();

		pmpro_changeMembershipLevel(
			array(
				'user_id'       => $order->user_id,
				'membership_id' => $order->membership_id,
				'order_id'      => $order->id,
			)
		);

		do_action( 'pmpro_uddoktapay_after_payment_completed', $order );
	}

	/**
	 * Mark payment as failed
	 *
	 * @param \MemberOrder $order  Order instance.
	 * @param string       $reason Reason for failure.
	 * @return void
	 */
	private function failPayment( \MemberOrder $order, string $reason ): void {
		// Build detailed failure note.
		/* translators: %s: Formatted date and time of payment failure */
		$note = sprintf( __( 'Payment failed via UddoktaPay on %s.', 'pmpro-uddoktapay' ), date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ) . "\n";
		/* translators: %s: Reason for payment failure */
		$note .= sprintf( __( 'Reason: %s', 'pmpro-uddoktapay' ), $reason );

		$order->status = 'failed';
		$order->notes  = $note;
		$order->saveOrder();

		do_action( 'pmpro_uddoktapay_after_payment_failed', $order, $reason );
	}

	/**
	 * Validate payment response
	 *
	 * @param \MemberOrder $order    Order instance.
	 * @param array        $response Payment gateway response.
	 * @return bool
	 */
	private function validatePayment( \MemberOrder $order, array $response ): bool {
		// Check payment status.
		if ( 'COMPLETED' !== $response['status'] ) {
			$this->logError( sprintf( 'Invalid payment status: %s.', $response['status'] ) );
			return false;
		}

		// Calculate order total.
		$order_total = $this->calculateOrderTotal( $order );

		// Get payment amount from response.
		$paid_amount = (float) ( $response['amount'] ?? 0 );

		// Compare amounts (with 2 decimal precision).
		if ( abs( $order_total - $paid_amount ) > 0.01 ) {
			$this->logError(
				sprintf(
					'Amount mismatch - Order: %f, Paid: %f.',
					$order_total,
					$paid_amount
				)
			);
			return false;
		}

		// Verify invoice/order matches.
		if ( $response['metadata']['order_code'] !== $order->code ) {
			$this->logError(
				sprintf(
					'Invoice mismatch - Order: %s, Verification: %s.',
					$order->code,
					$response['metadata']['order_code'] ?? 'none'
				)
			);
			return false;
		}

		return true;
	}

	/**
	 * Calculate total order amount including tax
	 *
	 * @param \MemberOrder $order Order instance.
	 * @return float
	 */
	private function calculateOrderTotal( \MemberOrder $order ): float {
		$subtotal = (float) $order->subtotal;
		$tax      = (float) $order->getTaxForPrice( $subtotal );
		return round( $subtotal + $tax, 2 );
	}

	/**
	 * Log error messages
	 *
	 * @param string $message Error message.
	 * @return void
	 */
	private function logError( string $message ): void {
		error_log( '[UddoktaPay Error] ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}

	/**
	 * Log debug messages when WP_DEBUG is enabled
	 *
	 * @param string $message Debug message.
	 * @return void
	 */
	private function logDebug( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[UddoktaPay Debug] ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}
