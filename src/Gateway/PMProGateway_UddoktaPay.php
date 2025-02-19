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
	public static function init() {
		add_filter( 'pmpro_gateways', array( 'PMProGateway_UddoktaPay', 'gateways' ) );
		add_filter( 'pmpro_gateways_with_pending_status', array( 'PMProGateway_UddoktaPay', 'addPendingStatus' ) );
		add_filter( 'pmpro_payment_options', array( 'PMProGateway_UddoktaPay', 'paymentOptions' ) );
		add_filter( 'pmpro_payment_option_fields', array( 'PMProGateway_UddoktaPay', 'paymentOptionFields' ), 10, 2 );

		$gateway = pmpro_getGateway();
		if ( 'uddoktapay' === $gateway ) {
			add_filter( 'pmpro_include_payment_information_fields', '__return_false' );
			add_filter( 'pmpro_required_billing_fields', array( 'PMProGateway_UddoktaPay', 'requiredBillingFields' ) );
			add_filter( 'pmpro_checkout_default_submit_button', array( 'PMProGateway_UddoktaPay', 'checkoutDefaultSubmitButton' ) );
		}
	}

	/**
	 * Add UddoktaPay to available gateways.
	 *
	 * @since  1.0.0
	 * @param  array $gateways List of available gateways.
	 * @return array Modified list of gateways.
	 */
	public static function gateways( $gateways ) {
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
	public static function addPendingStatus( $gateways ) {
		$gateways[] = 'uddoktapay';
		return $gateways;
	}

	/**
	 * Get list of gateway-specific options.
	 *
	 * @since  1.0.0
	 * @return array List of gateway options.
	 */
	public static function getGatewayOptions() {
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
	public static function isReady( $ready ) {
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
	public static function paymentOptions( $options ) {
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
	public static function paymentOptionFields( $values, $gateway ) {
		include_once PMPRO_UDDOKTAPAY_DIR . '/src/Views/gateway-settings.php';
	}

	/**
	 * Remove unnecessary billing fields.
	 *
	 * @since  1.0.0
	 * @param  array $fields Current required billing fields.
	 * @return array Modified billing fields.
	 */
	public static function requiredBillingFields( $fields ) {
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
	public static function checkoutDefaultSubmitButton( $show ) {
		global $pmpro_requirebilling;
		$display_name = get_option( 'pmpro_uddoktapay_display_name' );
		$button_text  = empty( $display_name ) ? 'Pay with Bangladeshi Methods' : $display_name;
		if ( ! $pmpro_requirebilling ) {
			$button_text = __( 'Submit and Confirm', 'pmpro-uddoktapay' );
		}
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

		return self::$gateway->processPayment( $order );
	}
}
