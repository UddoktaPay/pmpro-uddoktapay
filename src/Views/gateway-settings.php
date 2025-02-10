<?php
/**
 * UddoktaPay Gateway Settings Template
 *
 * This template file displays the configuration form for the UddoktaPay payment gateway
 * in the PMPro payment settings section.
 *
 * @package UddoktaPay\PMPro\Views
 * @since   1.0.0
 *
 * @var array  $values        Array of gateway settings values.
 * @var string $display_name  Gateway display name.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$display_name = empty( $values['uddoktapay_display_name'] )
? 'Pay with Bangladeshi Methods'
: $values['uddoktapay_display_name'];

$is_active  = 'uddoktapay' === $gateway;
$style_attr = $is_active ? '' : ' style="display: none;"';

?>
<tr class="pmpro_settings_divider gateway gateway_uddoktapay"<?php echo esc_attr( $style_attr ); ?>>
	<td colspan="2">
		<hr />
		<h2><?php esc_html_e( 'UddoktaPay Settings', 'pmpro-uddoktapay' ); ?></h2>
	</td>
</tr>

<tr class="gateway gateway_uddoktapay"<?php echo esc_attr( $style_attr ); ?>>
	<th scope="row" valign="top">
		<label for="uddoktapay_display_name"><?php esc_html_e( 'Display Name', 'pmpro-uddoktapay' ); ?>:</label>
	</th>
	<td>
		<input
			type="text"
			id="uddoktapay_display_name"
			name="uddoktapay_display_name"
			size="60"
			value="<?php echo esc_attr( $display_name ); ?>"
		/>
		<p class="description">
			<?php esc_html_e( 'The name that will be displayed on the checkout button', 'pmpro-uddoktapay' ); ?>
		</p>
	</td>
</tr>

<tr class="gateway gateway_uddoktapay"<?php echo esc_attr( $style_attr ); ?>>
	<th scope="row" valign="top">
		<label for="uddoktapay_api_key"><?php esc_html_e( 'API Key', 'pmpro-uddoktapay' ); ?>:</label>
	</th>
	<td>
		<input 
			type="text" 
			id="uddoktapay_api_key" 
			name="uddoktapay_api_key" 
			size="60" 
			value="<?php echo esc_attr( $values['uddoktapay_api_key'] ); ?>" 
		/>
	</td>
</tr>

<tr class="gateway gateway_uddoktapay"<?php echo esc_attr( $style_attr ); ?>>
	<th scope="row" valign="top">
		<label for="uddoktapay_api_url"><?php esc_html_e( 'API URL', 'pmpro-uddoktapay' ); ?>:</label>
	</th>
	<td>
		<input 
			type="text" 
			id="uddoktapay_api_url" 
			name="uddoktapay_api_url" 
			size="60" 
			value="<?php echo esc_attr( $values['uddoktapay_api_url'] ); ?>" 
		/>
	</td>
</tr>