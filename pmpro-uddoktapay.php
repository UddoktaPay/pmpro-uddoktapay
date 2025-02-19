<?php
/**
 * Plugin Name: Paid Memberships Pro - UddoktaPay Gateway
 * Plugin URI: https://github.com/UddoktaPay/pmpro-uddoktapay
 * Description: UddoktaPay Gateway integration for Paid Memberships Pro
 * Version: 1.0.2
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author: UddoktaPay
 * Author URI: https://uddoktapay.com
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: pmpro-uddoktapay
 * Domain Path: /languages
 *
 * @package UddoktaPay\PMPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Plugin constants.
 */
define( 'PMPRO_UDDOKTAPAY_FILE', __FILE__ );
define( 'PMPRO_UDDOKTAPAY_DIR', dirname( PMPRO_UDDOKTAPAY_FILE ) );

/**
 * Autoloader.
 */
require PMPRO_UDDOKTAPAY_DIR . '/vendor/autoload.php';

/**
 * Initialize plugin.
 */
if ( class_exists( \UddoktaPay\PMPro\Core\Plugin::class ) ) {
	\UddoktaPay\PMPro\Core\Plugin::run();
}
