<?php

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\Action_Scheduler;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\API_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Settings_Interface;
use Codeception\Stub\Expected;


/**
 * @coversDefaultClass \BrianHenryIE\WC_Shipment_Tracking_Updates\Action_Scheduler\Scheduler
 */
class Scheduler_WPUnit_Test extends \Codeception\TestCase\WPTestCase {


	/**
	 * @covers ::register
	 */
	public function test_schedule_regular_updates(): void {

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();

		$scheduler = new Scheduler( $api, $settings, $logger );

		assert( false === as_next_scheduled_action( Scheduler::SCHEDULED_UPDATE_HOOK ) );

		$scheduler->register();

		$this->assertNotFalse( as_next_scheduled_action( Scheduler::SCHEDULED_UPDATE_HOOK ) );
	}


	/**
	 * @covers ::register
	 */
	public function test_schedule_check_packed_orders(): void {

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();

		$scheduler = new Scheduler( $api, $settings, $logger );

		assert( false === as_next_scheduled_action( Scheduler::SCHEDULED_CHECK_PACKED_ORDERS_HOOK ) );

		$scheduler->register();

		$this->assertNotFalse( as_next_scheduled_action( Scheduler::SCHEDULED_CHECK_PACKED_ORDERS_HOOK ) );
	}


	/**
	 * @covers ::check_packed_orders
	 */
	public function test_check_packed_orders(): void {

		$api      = $this->makeEmpty(
			API_Interface::class,
			array(
				'check_packed_orders' => Expected::once(
					function() {
						return array();}
				),
			)
		);
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();

		$scheduler = new Scheduler( $api, $settings, $logger );

		$scheduler->check_packed_orders();

	}

}
