<?php

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\USPS;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Shipment_Tracking_Updates\USPS\XML2Array;
use Exception;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\USPS\USPS_Tracking_Details
 */
class USPS_Tracking_Details_Unit_Test extends \Codeception\Test\Unit {

	/**
	 * "A status update is not yet available for your package..."
	 */
	public function test_package_not_yet_picked_up_error(): void {

		$details = array(
			'Error'       =>
				array(
					'Number'      => '-2147219283',
					'Description' => 'A status update is not yet available on your package. It will be available when the shipper provides an update or the package is delivered to USPS. Check back soon. Sign up for Informed Delivery<SUP>&reg;</SUP> to receive notifications for packages addressed to you.',
					'HelpFile'    => '',
					'HelpContext' => '',
				),
			'@attributes' =>
				array(
					'ID' => '9123136895232361123123',
				),
		);

		$logger = new ColorLogger();

		$sut = new USPS_Tracking_Details( '9123136895232361123123', $details, $logger );

		$this->assertTrue( $logger->hasErrorRecords() );

		$this->assertEquals( $details['Error']['Description'], $logger->records[0]['context']['details']['Error']['Description'] );

	}

}
