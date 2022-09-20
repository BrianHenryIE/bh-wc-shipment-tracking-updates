<?php
/**
 * @see https://developer.dhl.com/api-reference/shipment-tracking
 *
 * Rate limit: 250 requests every 1 day
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\DHL;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Dhl\Sdk\UnifiedTracking\Service\ServiceFactory;
use DateTimeZone;

class DHL_Scratch_Test extends \Codeception\TestCase\WPTestCase {

	public function test_api(): void {

		$this->markTestIncomplete();

		// API: Shipment Tracking - Unified.
		// api_key and consumer_key are synonymous.

		$settings = new class() implements DHL_Settings_Interface {

			public function get_consumer_api_key(): string {
				return $_ENV['DHL_CONSUMER_API_KEY'];
			}
		};
		$logger   = new ColorLogger();

		$tracker = new DHL_Tracker( new ServiceFactory(), $settings, $logger );

		$tracking_number = 'xxx';

		$tracking_details = $tracker->query_single_tracking_number( $tracking_number );

		$m = $tracking_details;

	}

	public function test_bad_credentials(): void {

		$this->markTestIncomplete();

		$settings = new class() implements DHL_Settings_Interface {

			public function get_consumer_api_key(): string {
				return 'BADooCREDENTIALS';
			}
		};
		$logger   = new ColorLogger();

		$tracker = new DHL_Tracker( new ServiceFactory(), $settings, $logger );

		$tracking_number = 'xxx';

		$tracking_details = $tracker->query_single_tracking_number( $tracking_number );

		$m = $tracking_details;

	}

}
