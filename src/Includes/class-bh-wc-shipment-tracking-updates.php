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
use BrianHenryIE\WC_Shipment_Tracking_Updates\Admin\Plugins_Page;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\API_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Settings_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Order_Statuses;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Shipping_Settings_Page;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce_Shipment_Tracking\Order_List_Table;
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
	 * @var API_Interface
	 */
	protected $api;

	/**
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * @var Settings_Interface
	 */
	protected $settings;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the frontend-facing side of the site.
	 *
	 * @param API_Interface      $api
	 * @param Settings_Interface $settings
	 * @param LoggerInterface    $logger
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
		$this->define_woocommerce_order_status_hooks();
		$this->define_woocommerce_shipment_tracking_hooks();
		$this->define_settings_page_hooks();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 */
	protected function set_locale(): void {

		$plugin_i18n = new I18n();

		add_action( 'plugins_loaded', array( $plugin_i18n, 'load_plugin_textdomain' ) );

	}


	protected function define_cli_commands(): void {

		if ( ! class_exists( WP_CLI::class ) ) {
			return;
		}

		$cli = new CLI( $this->api, $this->settings );
		// vendor/bin/wp shipment_tracking_updates check_order 123
		WP_CLI::add_command( 'shipment_tracking_updates', array( $cli, 'find_undispatched_orders' ) );

	}

	/**
	 * Register all of the hooks related to the admin area functionality of the plugin.
	 *
	 * @since    1.0.0
	 */
	protected function define_admin_hooks(): void {

		$plugin_admin = new Admin( $this->settings, $this->logger );

		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );

	}

	/**
	 *
	 * @since    1.0.0
	 */
	protected function define_plugins_page_hooks(): void {

		$plugins_page = new Plugins_Page( $this->settings, $this->logger );

		add_filter( 'plugin_action_links_bh-wc-shipment-tracking-updates/bh-wc-shipment-tracking-updates.php', array( $plugins_page, 'action_links' ) );
	}

	/**
	 *
	 * @since    1.0.0
	 */
	protected function define_action_scheduler_hooks(): void {

		$scheduler = new Scheduler( $this->api, $this->settings, $this->logger );

		add_action( 'init', array( $scheduler, 'register' ) );
		add_action( Scheduler::SINGLE_UPDATE_HOOK, array( $scheduler, 'execute_batch' ) );
		add_action( Scheduler::SCHEDULED_UPDATE_HOOK, array( $scheduler, 'execute' ) );
	}

	/**
	 *
	 * @since    1.0.0
	 */
	protected function define_woocommerce_order_status_hooks(): void {

		$order_status = new Order_Statuses( $this->settings, $this->logger );

		add_action( 'woocommerce_init', array( $order_status, 'register_status' ) );

		add_filter( 'wc_order_statuses', array( $order_status, 'add_order_status_to_woocommerce' ) );
		add_filter( 'woocommerce_order_is_paid_statuses', array( $order_status, 'add_to_paid_status_list' ) );
		add_filter( 'woocommerce_reports_order_statuses', array( $order_status, 'add_to_reports_status_list' ) );

	}

	/**
	 *
	 * @since    1.0.0
	 */
	protected function define_woocommerce_shipment_tracking_hooks(): void {

		$table = new Order_List_Table();

		add_filter( 'woocommerce_shipment_tracking_get_shipment_tracking_column', array( $table, 'append_tracking_detail_to_column' ), 10, 3 );

	}

	/**
	 *
	 * @since    1.0.0
	 */
	protected function define_settings_page_hooks(): void {

		$shipping_settings_page = new Shipping_Settings_Page();

		add_filter( 'woocommerce_get_sections_shipping', array( $shipping_settings_page, 'shipment_tracking_updates_section' ) );
		add_filter( 'woocommerce_get_settings_shipping', array( $shipping_settings_page, 'shipment_tracking_updates_settings' ), 10, 2 );
	}

}
