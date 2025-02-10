<?php
/**
 * UddoktaPay Payment Gateway Integration for Paid Memberships Pro
 *
 * This class extends the PMProGateway class to provide UddoktaPay payment
 * processing functionality for Paid Memberships Pro.
 *
 * @package UddoktaPay\PMPro\Gateway
 * @since   1.0.0
 */

use UddoktaPay\PMPro\Gateway\UddoktaPayGateway;

/**
 * Class PMProGateway_UddoktaPay
 *
 * Handles integration between Paid Memberships Pro and UddoktaPay payment gateway.
 *
 * @package UddoktaPay\PMPro\Gateway
 * @since   1.0.0
 */
class PMProGateway_UddoktaPay extends PMProGateway {

	/**
	 * Gateway implementation instance.
	 *
	 * @since 1.0.0
	 * @var   UddoktaPayGateway
	 */
	private $gateway;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->gateway = new UddoktaPayGateway();
	}

	/**
	 * Initialize gateway hooks.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		add_filter( 'pmpro_gateways', array( $this, 'gateways' ) );
		add_filter( 'pmpro_gateways_with_pending_status', array( $this, 'addPendingStatus' ) );
		add_filter( 'pmpro_payment_options', array( $this, 'paymentOptions' ) );
		add_filter( 'pmpro_payment_option_fields', array( $this, 'paymentOptionFields' ), 10, 2 );

		$gateway = pmpro_getGateway();
		if ( 'uddoktapay' === $gateway ) {
			add_filter( 'pmpro_include_payment_information_fields', '__return_false' );
			add_filter( 'pmpro_required_billing_fields', array( $this, 'requiredBillingFields' ) );
			add_filter( 'pmpro_checkout_default_submit_button', array( $this, 'checkoutDefaultSubmitButton' ) );
		}
	}

	/**
	 * Add UddoktaPay to available gateways.
	 *
	 * @since  1.0.0
	 * @param  array $gateways List of available gateways.
	 * @return array Modified list of gateways.
	 */
	public function gateways( $gateways ) {
		if ( empty( $gateways['uddoktapay'] ) ) {
			$gateways['uddoktapay'] = __( 'UddoktaPay', 'pmpro-uddoktapay' );
		}
		return $gateways;
	}

	/**
	 * Add UddoktaPay to gateways supporting pending status.
	 *
	 * @since  1.0.0
	 * @param  array $gateways List of gateways with pending status.
	 * @return array Modified list of gateways.
	 */
	public function addPendingStatus( $gateways ) {
		$gateways[] = 'uddoktapay';
		return $gateways;
	}

	/**
	 * Get list of gateway-specific options.
	 *
	 * @since  1.0.0
	 * @return array List of gateway options.
	 */
	public function getGatewayOptions() {
		return array(
			'sslseal',
			'nuclear_HTTPS',
			'uddoktapay_display_name',
			'uddoktapay_api_key',
			'uddoktapay_api_url',
			'currency',
			'use_ssl',
			'tax_state',
			'tax_rate',
		);
	}

	/**
	 * Check if gateway is configured and ready for use.
	 *
	 * Verifies that all required gateway settings are properly configured.
	 *
	 * @since  1.0.0
	 * @param  bool $ready Initial ready state.
	 * @return bool True if gateway is configured, false otherwise.
	 */
	public function isReady( $ready ) {
		if ( '' === get_option( 'pmpro_uddoktapay_display_name' ) ||
			'' === get_option( 'pmpro_uddoktapay_api_key' ) ||
			'' === get_option( 'pmpro_uddoktapay_api_url' )
		) {
			return false;
		}

		return true;
	}

	/**
	 * Add gateway options to payment settings.
	 *
	 * @since  1.0.0
	 * @param  array $options Current payment options.
	 * @return array Modified payment options.
	 */
	public function paymentOptions( $options ) {
		return array_merge( self::getGatewayOptions(), $options );
	}

	/**
	 * Display gateway-specific option fields.
	 *
	 * @since  1.0.0
	 * @param  array  $values  Current field values.
	 * @param  string $gateway Current gateway.
	 * @return void
	 */
	public function paymentOptionFields( $values, $gateway ) {
		include_once PMPRO_UDDOKTAPAY_DIR . '/src/Views/gateway-settings.php';
	}

	/**
	 * Remove unnecessary billing fields.
	 *
	 * @since  1.0.0
	 * @param  array $fields Current required billing fields.
	 * @return array Modified billing fields.
	 */
	public function requiredBillingFields( $fields ) {
		unset( $fields['baddress1'] );
		unset( $fields['bcity'] );
		unset( $fields['bstate'] );
		unset( $fields['bzipcode'] );
		unset( $fields['bcountry'] );
		unset( $fields['CardType'] );
		unset( $fields['AccountNumber'] );
		unset( $fields['ExpirationMonth'] );
		unset( $fields['ExpirationYear'] );
		unset( $fields['CVV'] );
		return $fields;
	}

	/**
	 * Customize checkout submit button.
	 *
	 * @since  1.0.0
	 * @param  bool $show Whether to show default button.
	 * @return bool Modified show value.
	 */
	public function checkoutDefaultSubmitButton( $show ) {
		$display_name = get_option( 'pmpro_uddoktapay_display_name' );
		$button_text  = empty( $display_name ) ? 'Pay with Bangladeshi Methods' : $display_name;
		include_once PMPRO_UDDOKTAPAY_DIR . '/src/Views/submit-button.php';
		return false;
	}

	/**
	 * Process payment for an order.
	 *
	 * @since  1.0.0
	 * @param  MemberOrder $order Order object to process.
	 * @return bool Whether the payment was successful.
	 */
	public function process( &$order ) {
		if ( empty( $order->code ) ) {
			$order->code = $order->getRandomCode();
		}

		$order->payment_type = 'UddoktaPay';
		$order->status       = 'pending';
		$order->saveOrder();

		return $this->gateway->processPayment( $order );
	}
}
