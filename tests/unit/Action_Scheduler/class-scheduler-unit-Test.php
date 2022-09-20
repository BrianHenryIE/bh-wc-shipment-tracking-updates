<?php

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\Action_Scheduler;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Settings_Interface;
use Codeception\Stub\Expected;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Shipment_Tracking_Updates\Action_Scheduler\Scheduler
 */
class Scheduler_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::_tearDown();
		\WP_Mock::tearDown();
	}


	// $scheduler = new Scheduler( $this->api, $this->settings, $this->logger );
	//
	// add_action( 'init', array( $scheduler, 'register' ) );
	// add_action( Scheduler::SINGLE_UPDATE_HOOK, array( $scheduler, 'execute_batch' ) );
	// add_action( Scheduler::SCHEDULED_UPDATE_HOOK, array( $scheduler, 'execute' ) );
	// }

	public function test_single_update_hook_name() {
		$this->assertEquals( 'bh_wc_shipment_tracking_updates_single_update', Scheduler::SINGLE_UPDATE_HOOK );
	}

	public function test_scheduled_update_hook_name() {
		$this->assertEquals( 'bh_wc_shipment_tracking_updates_scheduled_update', Scheduler::SCHEDULED_UPDATE_HOOK );
	}

	public function test_group_name() {
		$this->assertEquals( 'bh_wc_shipment_tracking_updates', Scheduler::ACTION_SCHEDULER_GROUP );
	}

	/**
	 * @throws \Exception
	 * @covers ::execute
	 */
	public function test_api_is_calledon_execute() {
		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );

		$api = $this->makeEmpty(
			API_Interface::class,
			array(
				'start_background_update_jobs' => Expected::once(),
			)
		);

		$sut = new Scheduler( $api, $settings, $logger );

		$sut->execute();

	}

	/**
	 * @throws \Exception
	 * @covers ::execute_batch
	 */
	public function test_api_is_calledon_execute_batch() {
		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );

		$api = $this->makeEmpty(
			API_Interface::class,
			array(
				'update_orders' => Expected::once(
					function() {
						return array();}
				),
			)
		);

		$sut = new Scheduler( $api, $settings, $logger );

		$sut->execute_batch( array() );

	}

}
