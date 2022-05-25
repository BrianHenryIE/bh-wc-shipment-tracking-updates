<?php

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\Logger;

use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\API\BH_WP_PSR_Logger;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\Logger_Settings_Interface;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Shipment_Tracking_Updates\Logger\DHL_Logs
 */
class DHL_Logs_Unit_Test extends \Codeception\Test\Unit {

	/**
	 * @covers ::add_message_json_to_context
	 */
	public function test_add_message_json_to_context(): void {

		$sut              = new DHL_Logs();
		$settings         = $this->makeEmpty( Logger_Settings_Interface::class );
		$bh_wp_psr_logger = $this->makeEmpty( BH_WP_PSR_Logger::class );

		$message = <<<'EOD'
A log message.
{"with_an_object":"in_the_message"}
EOD;

		$log_data = array(
			'level'   => 'info', // NA.
			'message' => $message,
			'context' => array(),
		);

		assert( empty( $log_data['context'] ) );
		assert( false !== strpos( $message, 'with_an_object' ) );

		$result = $sut->add_message_json_to_context( $log_data, $settings, $bh_wp_psr_logger );

		$this->assertNotEmpty( $result['context'] );
		$this->assertStringNotContainsString( 'with_an_object', $result['message'] );

	}
}
