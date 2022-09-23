<?php

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\Logger;

use BrianHenryIE\WC_Shipment_Tracking_Updates\Settings_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\API\BH_WP_PSR_Logger;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\Logger_Settings_Interface;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Shipment_Tracking_Updates\Logger\USPS_Logs
 */
class USPS_Logs_Unit_Test extends \Codeception\Test\Unit {

	public function test_mute_status_upate_not_yet_available(): void {

		$log_data = array(
			'level'   => 'error',
			'message' => 'Error with tracking number 9123136895232361123123. A status update is not yet available on your package. It will be available when the shipper provides an update or the package is delivered to USPS. Check back soon. Sign up for Informed Delivery<SUP>&reg;</SUP> to receive notifications for packages addressed to you.',
			'context' =>
				array(
					'details' =>
						array(
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
						),
				),
		);

		$sut = new USPS_Logs();

		$logger_settings  = $this->makeEmpty( Logger_Settings_Interface::class );
		$bh_wp_psr_logger = $this->makeEmpty( BH_WP_PSR_Logger::class );

		$result = $sut->mute_errors( $log_data, $logger_settings, $bh_wp_psr_logger );

		$this->assertNull( $result );

	}
}
