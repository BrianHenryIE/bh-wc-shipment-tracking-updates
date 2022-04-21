<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * frontend-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      2.0.0
 *
 * @package    BrianHenryIE\WC_Shipment_Tracking_Updates
 * @subpackage BrianHenryIE\WC_Shipment_Tracking_Updates/includes
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\Includes;

use BrianHenryIE\WC_Shipment_Tracking_Updates\Action_Scheduler\Scheduler;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Admin\Admin;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Admin\Plugin_Installer;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Admin\Plugins_Page;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\API_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Settings_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Admin_Order_List_Page;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Emails;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Order_Statuses;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Shipping_Settings_Page;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce_Shipment_Tracking\Admin_Order_View;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce_Shipment_Tracking\Order_List_Table;
use Exception;
use Psr\Log\LoggerInterface;
use WP_CLI;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * frontend-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    BrianHenryIE\WC_Shipment_Tracking_Updates
 * @subpackage BrianHenryIE\WC_Shipment_Tracking_Updates/includes
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */
class BH_WC_Shipment_Tracking_Updates {

	/**
	 * PSR logger for the plugin.
	 *
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * The plugin's settings, to pass to each class instance.
	 *
	 * @var Settings_Interface
	 */
	protected $settings;

	/**
	 * The main plugin functions.
	 *
	 * @var API_Interface
	 */
	protected $api;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the frontend-facing side of the site.
	 *
	 * @param API_Interface      $api The main plugin functions.
	 * @param Settings_Interface $settings The plugin settings.
	 * @param LoggerInterface    $logger PSR logger for the plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( API_Interface $api, Settings_Interface $settings, LoggerInterface $logger ) {

		$this->logger   = $logger;
		$this->settings = $settings;
		$this->api      = $api;

		$this->set_locale();

		$this->define_cli_commands();

		$this->define_action_scheduler_hooks();
		$this->define_admin_hooks();
		$this->define_plugins_page_hooks();
		$this->define_plugin_installer_page_hooks();
		$this->define_woocommerce_order_status_hooks();
		$this->define_woocommerce_shipment_tracking_hooks();
		$this->define_woocommerce_email_hooks();
		$this->define_settings_page_hooks();
		$this->define_admin_order_list_page_hooks();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    2.0.0
	 */
	protected function set_locale(): void {

		$plugin_i18n = new I18n();

		add_action( 'init', array( $plugin_i18n, 'load_plugin_textdomain' ) );
	}

	/**
	 * Register WP CLI commands.
	 *
	 * `wp shipment_tracking_updates check_order 123`
	 */
	protected function define_cli_commands(): void {

		if ( ! class_exists( WP_CLI::class ) ) {
			return;
		}

		$cli = new CLI( $this->api, $this->settings );

		try {
			WP_CLI::add_command( 'shipment_tracking_updates find_undispatched_orders', array( $cli, 'find_undispatched_orders' ) );
			WP_CLI::add_command( 'shipment_tracking_updates check_packed_orders', array( $cli, 'check_packed_orders' ) );
		} catch ( Exception $e ) {
			$this->logger->error( 'Failed to register WP CLI commands: ' . $e->getMessage(), array( 'exception' => $e ) );
		}
	}

	/**
	 * Register all the hooks related to the admin area functionality of the plugin.
	 *
	 * @since    2.0.0
	 */
	protected function define_admin_hooks(): void {

		$plugin_admin = new Admin( $this->settings, $this->logger );

		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );
	}

	/**
	 * Add Settings link on the plugins.php list page.
	 *
	 * @since    2.0.0
	 */
	protected function define_plugins_page_hooks(): void {

		$plugins_page = new Plugins_Page( $this->settings, $this->logger );

		$plugin_basename = $this->settings->get_plugin_basename();

		add_filter( "plugin_action_links_{$plugin_basename}", array( $plugins_page, 'action_links' ), 10, 4 );
	}

	/**
	 * Adds a link to the settings page on the plugin update completed page.
	 *
	 * @ssince 2.2.0
	 */
	protected function define_plugin_installer_page_hooks(): void {

		$plugin_installer = new Plugin_Installer( $this->settings, $this->logger );

		add_filter( 'install_plugin_complete_actions', array( $plugin_installer, 'add_settings_link' ), 10, 3 );
	}

	/**
	 * Define hooks for background updates.
	 *
	 * @since    2.0.0
	 */
	protected function define_action_scheduler_hooks(): void {

		$scheduler = new Scheduler( $this->api, $this->settings, $this->logger );

		add_action( 'init', array( $scheduler, 'register' ) );
		add_action( Scheduler::SINGLE_UPDATE_HOOK, array( $scheduler, 'execute_batch' ) );
		add_action( Scheduler::SCHEDULED_UPDATE_HOOK, array( $scheduler, 'execute' ) );
		add_action( Scheduler::SCHEDULED_CHECK_PACKED_ORDERS_HOOK, array( $scheduler, 'check_packed_orders' ) );
	}

	/**
	 * Register order statuses (post types): packed, in-transit, returning.
	 *
	 * @since    1.0.0
	 */
	protected function define_woocommerce_order_status_hooks(): void {

		$order_status = new Order_Statuses( $this->logger );

		add_action( 'woocommerce_init', array( $order_status, 'register_status' ) );

		add_filter( 'wc_order_statuses', array( $order_status, 'add_order_status_to_woocommerce' ) );
		add_filter( 'woocommerce_order_is_paid_statuses', array( $order_status, 'add_to_paid_status_list' ) );
		add_filter( 'woocommerce_reports_order_statuses', array( $order_status, 'add_to_reports_status_list' ) );
	}

	/**
	 * Add tracking information to the existing Shipment Tracking column on the orders list page.
	 *
	 * @since    2.0.0
	 */
	protected function define_woocommerce_shipment_tracking_hooks(): void {

		$table = new Order_List_Table();

		add_filter( 'woocommerce_shipment_tracking_get_shipment_tracking_column', array( $table, 'append_tracking_detail_to_column' ), 10, 3 );

		$admin_order_view = new Admin_Order_View( $this->api, $this->logger );

		add_action( 'admin_footer', array( $admin_order_view, 'tracking_information_for_order' ), 1 );
	}

	/**
	 * Register new emails (order-dispatched...) with WooCommerce.
	 *
	 * @since    2.1.0
	 */
	protected function define_woocommerce_email_hooks(): void {

		$emails = new Emails();

		add_filter( 'woocommerce_email_classes', array( $emails, 'register_emails_with_woocommerce' ) );
	}

	/**
	 * Create the settings page.
	 * * API credentials
	 * * Link to emails settings page
	 * * Log level setting
	 *
	 * @since    1.0.0
	 */
	protected function define_settings_page_hooks(): void {

		$shipping_settings_page = new Shipping_Settings_Page();

		add_filter( 'woocommerce_get_sections_shipping', array( $shipping_settings_page, 'shipment_tracking_updates_section' ) );
		add_filter( 'woocommerce_get_settings_shipping', array( $shipping_settings_page, 'shipment_tracking_updates_settings' ), 10, 2 );

		add_action(
			'woocommerce_admin_field_bh_wc_shipment_tracking_updates_text_html',
			array(
				$shipping_settings_page,
				'print_text_output',
			)
		);
	}

	/**
	 * Add an admin notice on the orders list page for packed orders, displaying stats on how long they have been waiting pickup.
	 *
	 * @since 2.2.0
	 */
	protected function define_admin_order_list_page_hooks(): void {

		$admin_order_list_page = new Admin_Order_List_Page( $this->api );

		add_action( 'admin_notices', array( $admin_order_list_page, 'print_packed_stats' ) );
	}
}
