<?php

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\Includes;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Action_Scheduler\Scheduler;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Admin\Plugins_Page;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\API_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Settings_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Emails;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Order_Statuses;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Shipping_Settings_Page;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce_Shipment_Tracking\Order_List_Table;
use WP_CLI\Fetchers\Plugin;
use WP_Mock\Matcher\AnyInstance;

/**
 * Class BH_WC_Shipment_Tracking_Updates_Unit_Test
 *
 * @package BrianHenryIE\WC_Shipment_Tracking_Updates\Includes
 * @coversDefaultClass \BrianHenryIE\WC_Shipment_Tracking_Updates\Includes\BH_WC_Shipment_Tracking_Updates
 */
class BH_WC_Shipment_Tracking_Updates_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::_tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * @covers ::__construct
	 */
	public function test_construct(): void {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );
		new BH_WC_Shipment_Tracking_Updates( $api, $settings, $logger );
	}

	/**
	 * @covers ::set_locale
	 */
	public function test_set_locale_hooked(): void {

		\WP_Mock::expectActionAdded(
			'init',
			array( new AnyInstance( I18n::class ), 'load_plugin_textdomain' )
		);

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );
		new BH_WC_Shipment_Tracking_Updates( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_cli_commands
	 */
	public function test_define_cli_commands(): void {

		$this->markTestIncomplete();

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );
		new BH_WC_Shipment_Tracking_Updates( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_plugins_page_hooks
	 */
	public function test_define_plugins_page_hooks(): void {

		$plugin_basename = 'bh-wc-shipment-tracking-updates/bh-wc-shipment-tracking-updates.php';

		\WP_Mock::expectFilterAdded(
			"plugin_action_links_{$plugin_basename}",
			array( new AnyInstance( Plugins_Page::class ), 'action_links' ),
			10,
			4
		);

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_basename' => $plugin_basename,
			)
		);
		$api      = $this->makeEmpty( API_Interface::class );
		new BH_WC_Shipment_Tracking_Updates( $api, $settings, $logger );
	}


	/**
	 * @covers ::define_action_scheduler_hooks
	 */
	public function test_define_action_scheduler_hooks(): void {

		\WP_Mock::expectActionAdded(
			'init',
			array( new AnyInstance( Scheduler::class ), 'register' )
		);

		\WP_Mock::expectActionAdded(
			Scheduler::SCHEDULED_UPDATE_HOOK,
			array( new AnyInstance( Scheduler::class ), 'execute' )
		);

		\WP_Mock::expectActionAdded(
			Scheduler::SINGLE_UPDATE_HOOK,
			array( new AnyInstance( Scheduler::class ), 'execute_batch' )
		);

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );
		new BH_WC_Shipment_Tracking_Updates( $api, $settings, $logger );
	}


	/**
	 * @covers ::define_woocommerce_order_status_hooks
	 */
	public function test_define_woocommerce_order_status_hooks(): void {

		\WP_Mock::expectActionAdded(
			'woocommerce_init',
			array( new AnyInstance( Order_Statuses::class ), 'register_status' )
		);

		\WP_Mock::expectFilterAdded(
			'wc_order_statuses',
			array( new AnyInstance( Order_Statuses::class ), 'add_order_status_to_woocommerce' )
		);

		\WP_Mock::expectFilterAdded(
			'woocommerce_order_is_paid_statuses',
			array( new AnyInstance( Order_Statuses::class ), 'add_to_paid_status_list' )
		);

		\WP_Mock::expectFilterAdded(
			'woocommerce_reports_order_statuses',
			array( new AnyInstance( Order_Statuses::class ), 'add_to_reports_status_list' )
		);

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );
		new BH_WC_Shipment_Tracking_Updates( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_woocommerce_shipment_tracking_hooks
	 */
	public function test_define_woocommerce_shipment_tracking_hooks(): void {

		\WP_Mock::expectFilterAdded(
			'woocommerce_shipment_tracking_get_shipment_tracking_column',
			array( new AnyInstance( Order_List_Table::class ), 'append_tracking_detail_to_column' ),
			10,
			3
		);

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );
		new BH_WC_Shipment_Tracking_Updates( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_woocommerce_email_hooks
	 */
	public function test_define_woocommerce_email_hooks(): void {

		\WP_Mock::expectFilterAdded(
			'woocommerce_email_classes',
			array( new AnyInstance( Emails::class ), 'register_emails_with_woocommerce' ),
			10,
			1
		);

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );
		new BH_WC_Shipment_Tracking_Updates( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_settings_page_hooks
	 */
	public function test_define_settings_page_hooks(): void {

		\WP_Mock::expectFilterAdded(
			'woocommerce_get_sections_shipping',
			array( new AnyInstance( Shipping_Settings_Page::class ), 'shipment_tracking_updates_section' )
		);

		\WP_Mock::expectFilterAdded(
			'woocommerce_get_settings_shipping',
			array( new AnyInstance( Shipping_Settings_Page::class ), 'shipment_tracking_updates_settings' ),
			10,
			2
		);

		\WP_Mock::expectActionAdded(
			'woocommerce_admin_field_bh_wc_shipment_tracking_updates_text_html',
			array( new AnyInstance( Shipping_Settings_Page::class ), 'print_text_output' )
		);

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );
		new BH_WC_Shipment_Tracking_Updates( $api, $settings, $logger );
	}

}
