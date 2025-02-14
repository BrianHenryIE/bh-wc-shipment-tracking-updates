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

namespace BrianHenryIE\WC_Shipment_Tracking_Updates;

use BrianHenryIE\WC_Shipment_Tracking_Updates\Action_Scheduler\Scheduler;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Admin\Admin;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Admin\Plugin_Installer;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Admin\Plugins_Page;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Frontend\Frontend;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Logger\USPS_Logs;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Logger\WooCommerce_Logs;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Settings_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Logger\DHL_Logs;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Logger\Log_Level;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Admin_Order_List_Page;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Admin_Order_UI;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Emails;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\My_Account;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Order_Statuses;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Shipping_Settings_Page;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce_Shipment_Tracking\Admin_Order_View;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce_Shipment_Tracking\Order_List_Table;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Includes\CLI;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Includes\I18n;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\API\BH_WP_PSR_Logger;
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
		$this->define_admin_order_ui_hooks();

		$this->define_frontend_hooks();
		$this->define_my_account_hooks();

		$this->define_logger_hooks();
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
			WP_CLI::add_command( 'shipment_tracking_updates check_order_ids', array( $cli, 'check_tracking_for_order_numbers' ) );
			WP_CLI::add_command( 'shipment_tracking_updates mark_order_complete', array( $cli, 'mark_order_complete' ) );
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
	 * Add a bulk action to mark orders as packed.
	 *
	 * @since 2.2.0
	 */
	protected function define_admin_order_list_page_hooks(): void {

		$admin_order_list_page = new Admin_Order_List_Page( $this->api );

		add_action( 'admin_notices', array( $admin_order_list_page, 'print_packed_stats' ) );

		add_filter( 'bulk_actions-edit-shop_order', array( $admin_order_list_page, 'register_bulk_action_print_shipping_labels_pdf' ), 100 );
		add_action( 'admin_action_mark_packed', array( $admin_order_list_page, 'update_order_statuses' ) );
		add_action( 'admin_notices', array( $admin_order_list_page, 'print_bulk_mark_packed_status_notice' ) );
	}

	protected function define_admin_order_ui_hooks(): void {
		$admin_order_ui = new Admin_Order_UI( $this->api );

		add_filter( 'woocommerce_order_actions', array( $admin_order_ui, 'add_admin_ui_order_action' ) );
		add_action( 'woocommerce_order_action_bh_wc_shipment_tracking_updates_mark_completed', array( $admin_order_ui, 'handle_mark_order_complete_action' ) );
	}
	/**
	 * Hooks for frontend behaviour:
	 * * Enqueuing CSS and JS
	 */
	protected function define_frontend_hooks(): void {

		$plugin_frontend = new Frontend( $this->settings );

		add_action( 'wp_enqueue_scripts', array( $plugin_frontend, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $plugin_frontend, 'enqueue_scripts' ) );

	}

	/**
	 * Hooks for actions in the customer my-account area:
	 * * Add a button to allow customers to mark their order complete
	 */
	protected function define_my_account_hooks(): void {

		$my_account = new My_Account( $this->logger );

		add_filter( 'woocommerce_my_account_my_orders_actions', array( $my_account, 'add_button' ), 10, 2 );
		add_action( 'init', array( $my_account, 'handle_mark_complete_action' ) );

	}

	/**
	 * Hooks to manipulate the logging behaviour.
	 *
	 * * Change some info logs to debug.
	 * * Add JSON in log messages to the context array.
	 *
	 * @see BH_WP_PSR_Logger::log()
	 */
	protected function define_logger_hooks(): void {

		$log_hook = $this->settings->get_plugin_slug() . '_bh_wp_logger_log';

		$log_level = new Log_Level();
		add_filter( $log_hook, array( $log_level, 'info_to_debug' ), 10, 3 );

		$dhl_logs = new DHL_Logs();
		add_filter( $log_hook, array( $dhl_logs, 'add_message_json_to_context' ), 10, 3 );

		$usps_logs = new USPS_Logs();
		add_filter( $log_hook, array( $usps_logs, 'mute_errors' ), 10, 3 );

		$column_hook = $this->settings->get_plugin_slug() . '_bh_wp_logger_column';

		$woocommerce_logs = new WooCommerce_Logs();
		add_filter( $column_hook, array( $woocommerce_logs, 'replace_wc_order_id_with_link' ), 10, 5 );

	}
}
