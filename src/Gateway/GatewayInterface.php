<?php
/**
 * Payment Gateway Interface
 *
 * Defines the contract for payment gateway implementations in the UddoktaPay PMPro integration.
 *
 * @package UddoktaPay\PMPro\Gateway
 * @since   1.0.0
 */

namespace UddoktaPay\PMPro\Gateway;

/**
 * Interface GatewayInterface
 *
 * Specifies required methods for payment gateway implementations.
 *
 * @package UddoktaPay\PMPro\Gateway
 * @since   1.0.0
 */
interface GatewayInterface {

	/**
	 * Process a payment transaction.
	 *
	 * @since  1.0.0
	 * @param  \MemberOrder $order Order object to be processed.
	 * @return bool True if payment was processed successfully, false otherwise.
	 */
	public function processPayment( &$order ): bool;

	/**
	 * Verify a payment transaction with the gateway.
	 *
	 * @since  1.0.0
	 * @param  string $invoice_id The invoice ID to verify.
	 * @return array Payment verification response data.
	 */
	public function verifyPayment( string $invoice_id ): array;

	/**
	 * Prepare payment data for gateway submission.
	 *
	 * @since  1.0.0
	 * @param  \MemberOrder $order Order object to prepare payment data for.
	 * @return array Prepared payment data.
	 */
	public function preparePaymentData( &$order ): array;
}
