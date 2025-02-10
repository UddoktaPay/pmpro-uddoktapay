<?php
/**
 * Plugin Activator
 *
 * This class handles the activation process of the UddoktaPay PMPro integration,
 * including dependency checks and initialization tasks.
 *
 * @package UddoktaPay\PMPro
 * @since   1.0.0
 */

namespace UddoktaPay\PMPro\Core;

/**
 * Class Activator
 *
 * Handles plugin activation tasks and dependency checks.
 *
 * @package UddoktaPay\PMPro\Core
 * @since   1.0.0
 */
class Activator {

	/**
	 * Plugin activation handler.
	 *
	 * Sets up necessary options, checks dependencies, and performs
	 * required initialization tasks during plugin activation.
	 *
	 * @since  1.0.0
	 * @return void
	 * @throws \WP_Error If required dependencies are not met.
	 */
	public function activate(): void {
		set_transient( 'pmpro-uddoktapay-admin-notice', true, 5 );

		if ( ! $this->checkDependencies() ) {
			deactivate_plugins( plugin_basename( PMPRO_UDDOKTAPAY_FILE ) );
			wp_die(
				esc_html__(
					'This plugin requires Paid Memberships Pro to be installed and activated.',
					'pmpro-uddoktapay'
				),
				'Plugin Activation Error',
				array( 'back_link' => true )
			);
		}

		flush_rewrite_rules();

		do_action( 'pmpro_uddoktapay_activated' );
	}

	/**
	 * Check if required dependencies are met.
	 *
	 * Verifies that Paid Memberships Pro is either active or at least installed.
	 *
	 * @since  1.0.0
	 * @return bool True if dependencies are met, false otherwise.
	 */
	private function checkDependencies(): bool {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		$pmpro_active = is_plugin_active( 'paid-memberships-pro/paid-memberships-pro.php' );
		$pmpro_exists = file_exists( WP_PLUGIN_DIR . '/paid-memberships-pro/paid-memberships-pro.php' );

		return $pmpro_active || $pmpro_exists;
	}
}
