<?php

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\UPS;

use BrianHenryIE\ColorLogger\ColorLogger;

class UPS_Scratch_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Used to step-debug and copy
	 */
	public function test_api(): void {

		$logger = new ColorLogger();

		$settings = new class() implements UPS_Settings_Interface {

			public function get_user_id(): string {
				return $_ENV['UPS_USER_ID'];
			}

			public function get_password(): string {
				return $_ENV['UPS_PASSWORD'];
			}

			public function get_access_key(): string {
				return $_ENV['UPS_ACCESS_KEY'];
			}
		};

		$tracker = new UPS_Tracker( $settings, $logger );

		// $tracking_number = 'xxx';
		$tracking_number = 'xxx'; // Fresh, unpicked up at Friday April 22, 10:14am
		$tracking_number = 'xxx'; // delivered

		$result = $tracker->query_single_tracking_number( $tracking_number );

		$the_result = $result;

	}

}
