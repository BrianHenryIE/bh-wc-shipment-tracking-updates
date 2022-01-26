<?php

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\USPS;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\USPS\USPS_Settings
 */
class USPS_Settings_Unit_Test extends \Codeception\Test\Unit {

	/**
	 * @covers ::get_usps_username
	 */
	public function test_get_usps_username(): void {

		$sut = new USPS_Settings();

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

		$sut = new USPS_Settings();

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

		$sut = new USPS_Settings();

		$result = $sut->get_number_of_days_to_mark_overseas_orders_complete();

		$this->assertEquals( 30, $result );
	}


	/**
	 * @covers ::is_configured
	 */
	public function test_is_configured(): void {
		$this->markTestIncomplete();
	}

}
