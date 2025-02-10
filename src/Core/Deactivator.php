<?php
/**
 * Plugin Deactivator
 *
 * This class handles the deactivation process of the UddoktaPay PMPro integration,
 * ensuring proper cleanup of plugin-specific settings and rules.
 *
 * @package UddoktaPay\PMPro
 * @since   1.0.0
 */

namespace UddoktaPay\PMPro\Core;

/**
 * Class Deactivator
 *
 * Handles plugin deactivation tasks and cleanup operations.
 *
 * @package UddoktaPay\PMPro\Core
 * @since   1.0.0
 */
class Deactivator {

	/**
	 * Plugin deactivation handler.
	 *
	 * Performs necessary cleanup tasks during plugin deactivation,
	 * including flushing rewrite rules and triggering deactivation hooks.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function deactivate(): void {
		flush_rewrite_rules();

		do_action( 'pmpro_uddoktapay_deactivated' );
	}
}
