<?php

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Shipment_Tracking_Updates\API\Settings
 */
class Settings_Unit_Test extends \Codeception\Test\Unit {

	protected function setup() : void {
		parent::setUp();
		\WP_Mock::setUp();
	}

	public function tearDown(): void {
		\WP_Mock::tearDown();
		parent::tearDown();
	}

	/**
	 * @covers ::get_plugin_name
	 */
	public function test_get_plugin_name():void {

		$sut = new Settings();

		$result = $sut->get_plugin_name();

		$this->assertEquals( 'Shipment Tracking Updates', $result );
	}

	/**
	 * @covers ::get_plugin_slug
	 */
	public function test_get_plugin_slug(): void {

		$sut = new Settings();

		$result = $sut->get_plugin_slug();

		$this->assertEquals( 'bh-wc-shipment-tracking-updates', $result );

	}

	/**
	 * @covers ::get_plugin_basename
	 */
	public function test_get_plugin_basename(): void {

		assert( ! defined( 'BH_WC_SHIPMENT_TRACKING_UPDATES_BASENAME' ) );

		$sut = new Settings();

		$result = $sut->get_plugin_basename();

		$this->assertEquals( 'bh-wc-shipment-tracking-updates/bh-wc-shipment-tracking-updates.php', $result );
	}


	/**
	 * @covers ::get_usps_username
	 */
	public function test_get_usps_username(): void {

		$sut = new Settings();

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'  => array(
					'bh_wc_shipment_tracking_updates_usps_user_id',
					null,
				),
				'times' => 1,
			)
		);

		$sut->get_usps_username();
	}

	/**
	 * @covers ::get_usps_source_id
	 */
	public function test_get_usps_source_id(): void {

		$sut = new Settings();

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'  => array(
					'bh_wc_shipment_tracking_updates_usps_source_id',
					null,
				),
				'times' => 1,
			)
		);

		$sut->get_usps_source_id();
	}

	/**
	 * @covers ::get_number_of_days_to_mark_overseas_orders_complete
	 */
	public function test_get_number_of_days_to_mark_overseas_orders_complete(): void {

		$sut = new Settings();

		$result = $sut->get_number_of_days_to_mark_overseas_orders_complete();

		$this->assertEquals( 30, $result );
	}

	/**
	 * @covers ::get_order_statuses_to_watch
	 */
	public function test_get_order_statuses_to_watch(): void {

		$sut = new Settings();

		$defaults = array( 'shippingpurchased', 'printed', 'packing', 'packed', 'in-transit', 'returning' );

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array(
					'bh_wc_shipment_tracking_updates_order_statuses_to_watch',
					$defaults,
				),
				'return' => $defaults,
				'times'  => 1,
			)
		);

		$result = $sut->get_order_statuses_to_watch();

		$this->assertContains( 'shippingpurchased', $result );
		$this->assertContains( 'printed', $result );
		$this->assertContains( 'packing', $result );
		$this->assertContains( 'packed', $result );
		$this->assertContains( 'in-transit', $result );
		$this->assertContains( 'returning', $result );
	}

	/**
	 * Check are all required settings configured.
	 *
	 * @used-by Scheduler::register()
	 * @used-by CLI::find_undispatched_orders()
	 *
	 * @return bool
	 */
	public function test_is_configured(): void {

	}


	/**
	 * Test get_log_level fetches the correct option, with a default value of "info".
	 *
	 * @covers ::get_log_level
	 */
	public function test_log_level(): void {

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array(
					'bh_wc_shipment_tracking_updates_log_level',
					'info',
				),
				'return' => 'info',
				'times'  => 1,
			)
		);

		$sut = new Settings();

		$sut->get_log_level();
	}
}
