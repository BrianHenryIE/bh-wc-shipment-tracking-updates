<?php

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\Includes;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\API;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Settings_Interface;
use Codeception\Stub\Expected;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Shipment_Tracking_Updates\Includes\CLI
 */
class CLI_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::check_packed_orders
	 */
	public function test_check_packed_orders(): void {

		$api      = $this->makeEmpty(
			API::class,
			array(
				'check_packed_orders' => Expected::once(
					function() {
						return array(
							'count_packed_orders'         => 100,
							'count_old_packed_orders'     => 80,
							'orders_marked_completed_ids' => array( 123, 126, 135, 139, 144, 147, 151, 152 ),
							'count_orders_without_tracking' => 5,
							'count_orders_with_unsupported_tracking' => 3,
						);
					}
				),
			)
		);
		$settings = $this->makeEmpty( Settings_Interface::class );

		$cli = new CLI( $api, $settings );

		$cli->check_packed_orders();

	}

}
