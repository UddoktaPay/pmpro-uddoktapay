<?php
/**
 * UddoktaPay Submit Button Template
 *
 * This template file renders the payment submission button for the UddoktaPay
 * payment gateway in the PMPro checkout form.
 *
 * @package UddoktaPay\PMPro\Views
 * @since   1.0.0
 *
 * @var string $button_text The text to display on the submit button.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<span id="pmpro_submit_span">
	<input type="hidden" name="submit-checkout" value="1" />
	<input 
		type="submit" 
		class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit-checkout' ) ); ?>" 
		value="<?php echo esc_attr( $button_text ); ?>" 
	/>
</span>