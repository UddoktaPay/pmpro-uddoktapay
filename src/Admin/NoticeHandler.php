<?php
/**
 * Admin Notice Handler
 *
 * Handles various admin notices and plugin links for the UddoktaPay PMPro integration.
 *
 * @package UddoktaPay\PMPro\Admin
 * @since   1.0.0
 */

namespace UddoktaPay\PMPro\Admin;

/**
 * Class NoticeHandler
 *
 * Manages admin notices, plugin action links, and meta information
 * for the UddoktaPay PMPro integration.
 *
 * @package UddoktaPay\PMPro\Admin
 * @since   1.0.0
 */
class NoticeHandler {

	/**
	 * Display notice for missing PMPro plugin dependency.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function missingNotice(): void {
		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			wp_kses(
				sprintf(
					/* translators: %1$s: Plugin name, %2$s: Required plugin name */
					esc_html__(
						'%1$s requires %2$s to be installed and activated.',
						'pmpro-uddoktapay'
					),
					'<strong>UddoktaPay Gateway</strong>',
					'<strong>Paid Memberships Pro</strong>'
				),
				array(
					'strong' => array(),
				)
			)
		);
	}

	/**
	 * Display admin notices if any are set.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function displayNotices(): void {
		if ( get_transient( 'pmpro-uddoktapay-admin-notice' ) ) {
			$this->displayActivationNotice();
			delete_transient( 'pmpro-uddoktapay-admin-notice' );
		}
	}

	/**
	 * Display plugin activation success notice.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private function displayActivationNotice(): void {
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			wp_kses(
				sprintf(
					/* translators: %s: Settings page URL */
					esc_html__(
						'Thank you for activating the Paid Memberships Pro: UddoktaPay Add On. <a href="%s">Visit the payment settings page</a> to configure the UddoktaPay Payment Gateway.',
						'pmpro-uddoktapay'
					),
					esc_url( admin_url( 'admin.php?page=pmpro-paymentsettings' ) )
				),
				array(
					'a' => array(
						'href' => array(),
					),
				)
			)
		);
	}

	/**
	 * Add plugin action links.
	 *
	 * @since  1.0.0
	 * @param  array $links Existing plugin action links.
	 * @return array Modified plugin action links.
	 */
	public function addActionLinks( array $links ): array {
		if ( current_user_can( 'manage_options' ) ) {
			$links[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'admin.php?page=pmpro-paymentsettings' ) ),
				esc_html__( 'Configure UddoktaPay', 'pmpro-uddoktapay' )
			);
		}
		return $links;
	}

	/**
	 * Add plugin row meta links.
	 *
	 * @since  1.0.0
	 * @param  array  $links Existing plugin row meta links.
	 * @param  string $file  Plugin file path.
	 * @return array Modified plugin row meta links.
	 */
	public function addPluginRowMeta( array $links, string $file ): array {
		if ( strpos( $file, 'pmpro-uddoktapay.php' ) !== false ) {
			$new_links = array(
				'docs'    => sprintf(
					'<a href="%s" target="_blank">%s</a>',
					esc_url( 'https://github.com/UddoktaPay/pmpro-uddoktapay' ),
					esc_html__( 'Documentation', 'pmpro-uddoktapay' )
				),
				'support' => sprintf(
					'<a href="%s" target="_blank">%s</a>',
					esc_url( 'https://my.uddoktapay.com/submitticket.php' ),
					esc_html__( 'Support', 'pmpro-uddoktapay' )
				),
			);
			$links     = array_merge( $links, $new_links );
		}
		return $links;
	}
}
