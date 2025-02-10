<?php
/**
 * Main Plugin Class
 *
 * Handles initialization and basic setup of the UddoktaPay PMPro integration plugin.
 *
 * @package UddoktaPay\PMPro\Core
 * @since   1.0.0
 */

namespace UddoktaPay\PMPro\Core;

use PMProGateway_UddoktaPay;
use UddoktaPay\PMPro\Admin\NoticeHandler;
use UddoktaPay\PMPro\Gateway\UddoktaPayGateway;
use UddoktaPay\PMPro\Webhook\WebhookHandler;

/**
 * Class Plugin
 *
 * Manages plugin lifecycle and coordinates component interactions.
 * Implements singleton pattern to ensure single instance.
 *
 * @package UddoktaPay\PMPro\Core
 * @since   1.0.0
 */
final class Plugin {

	/**
	 * Plugin instance.
	 *
	 * @since 1.0.0
	 * @var   Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Notice handler instance.
	 *
	 * @since 1.0.0
	 * @var   NoticeHandler
	 */
	private NoticeHandler $notice_handler;

	/**
	 * Payment gateway instance.
	 *
	 * @since 1.0.0
	 * @var   UddoktaPayGateway
	 */
	private UddoktaPayGateway $gateway;

	/**
	 * Webhook handler instance.
	 *
	 * @since 1.0.0
	 * @var   WebhookHandler
	 */
	private WebhookHandler $webhook_handler;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->initHooks();
	}

	/**
	 * Get plugin instance.
	 *
	 * Ensures only one instance is loaded or can be loaded.
	 *
	 * @since  1.0.0
	 * @return Plugin Plugin instance.
	 */
	public static function run(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize plugin hooks.
	 *
	 * @since 1.0.0
	 */
	private function initHooks() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );

		// Activation hook.
		register_activation_hook( PMPRO_UDDOKTAPAY_FILE, array( $this, 'activate' ) );

		// Deactivation hook.
		register_deactivation_hook( PMPRO_UDDOKTAPAY_FILE, array( $this, 'deactivate' ) );
	}

	/**
	 * Initialize plugin components.
	 *
	 * @since 1.0.0
	 */
	public function init(): void {
		$this->initializeDependencies();

		if ( ! class_exists( 'PMProGateway' ) ) {
			add_action( 'admin_notices', array( $this->notice_handler, 'missingNotice' ) );
			return;
		}

		$this->loadGatewayClass();
		$this->registerHooks();
	}

	/**
	 * Initialize plugin dependencies.
	 *
	 * @since 1.0.0
	 */
	private function initializeDependencies(): void {
		$this->gateway         = new UddoktaPayGateway();
		$this->notice_handler  = new NoticeHandler();
		$this->webhook_handler = new WebhookHandler( $this->gateway );
	}

	/**
	 * Load PMPro gateway class.
	 *
	 * @since 1.0.0
	 */
	private function loadGatewayClass(): void {
		add_action( 'init', array( new PMProGateway_UddoktaPay(), 'init' ) );
		add_filter( 'pmpro_is_ready', array( new PMProGateway_UddoktaPay(), 'isReady' ), 999, 1 );
	}

	/**
	 * Register plugin hooks.
	 *
	 * @since 1.0.0
	 */
	private function registerHooks(): void {
		// Admin notices.
		add_action( 'admin_notices', array( $this->notice_handler, 'displayNotices' ) );

		// Plugin links.
		add_filter(
			'plugin_action_links_' . plugin_basename( PMPRO_UDDOKTAPAY_FILE ),
			array( $this->notice_handler, 'addActionLinks' )
		);
		add_filter(
			'plugin_row_meta',
			array( $this->notice_handler, 'addPluginRowMeta' ),
			10,
			2
		);

		// Webhook handlers.
		add_action( 'wp_ajax_nopriv_uddoktapay-pmpro-webhook', array( $this->webhook_handler, 'handleWebhook' ) );
		add_action( 'wp_ajax_uddoktapay-pmpro-webhook', array( $this->webhook_handler, 'handleWebhook' ) );
	}

	/**
	 * Activate plugin.
	 *
	 * @since 1.0.0
	 */
	public function activate(): void {
		$activator = new Activator();
		$activator->activate();
	}

	/**
	 * Deactivate plugin.
	 *
	 * @since 1.0.0
	 */
	public function deactivate(): void {
		$deactivator = new Deactivator();
		$deactivator->deactivate();
	}

	/**
	 * Prevent cloning of the instance.
	 *
	 * @since 1.0.0
	 */
	private function __clone() {
	}

	/**
	 * Prevent unserializing of the instance.
	 *
	 * @since  1.0.0
	 * @throws \Exception If attempt is made to unserialize instance.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}
