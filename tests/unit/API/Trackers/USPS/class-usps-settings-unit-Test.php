<?php

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\USPS;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\USPS\USPS_Settings
 */
class USPS_Settings_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::_tearDown();
		\WP_Mock::tearDown();
	}

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

		$this->assertEquals( 21, $result );
	}


	/**
	 * When both USPS user id and source id are filled, consider it "configured".
	 *
	 * TODO: User ids probably have distinct features, e.g. always 8+ characters.
	 *
	 * @covers ::is_configured
	 */
	public function test_is_configured(): void {

		$sut = new USPS_Settings();

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array(
					'bh_wc_shipment_tracking_updates_usps_user_id',
					null,
				),
				'times'  => 1,
				'return' => 'user_id',
			)
		);
		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array(
					'bh_wc_shipment_tracking_updates_usps_source_id',
					null,
				),
				'times'  => 1,
				'return' => 'source_id',
			)
		);

		$result = $sut->is_configured();

		$this->assertTrue( $result );
	}


	/**
	 * When either USPS user id or source id are not filled, DO NOT consider it "configured".
	 * here: null user_id.
	 *
	 * @covers ::is_configured
	 */
	public function test_is_not_configured_user_id(): void {

		$sut = new USPS_Settings();

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array(
					'bh_wc_shipment_tracking_updates_usps_user_id',
					null,
				),
				'times'  => 1,
				'return' => null,
			)
		);
		// `$this->get_usps_source_id()` never runs because the boolean short circuits.
		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array(
					'bh_wc_shipment_tracking_updates_usps_source_id',
					null,
				),
				'times'  => 0,
				'return' => 'source_id',
			)
		);

		$result = $sut->is_configured();

		$this->assertFalse( $result );
	}


	/**
	 * When either USPS user id or source id are not filled, DO NOT consider it "configured".
	 * here: empty source.
	 *
	 * @covers ::is_configured
	 */
	public function test_is_not_configured_source_id(): void {

		$sut = new USPS_Settings();

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array(
					'bh_wc_shipment_tracking_updates_usps_user_id',
					null,
				),
				'times'  => 1,
				'return' => 'user_id',
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array(
					'bh_wc_shipment_tracking_updates_usps_source_id',
					null,
				),
				'times'  => 1,
				'return' => '',
			)
		);

		$result = $sut->is_configured();

		$this->assertFalse( $result );
	}

}
