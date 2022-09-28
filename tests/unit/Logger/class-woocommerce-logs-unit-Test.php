<?php

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\Logger;

use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\API\BH_WP_PSR_Logger;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\Logger_Settings_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\WooCommerce_Logger_Settings_Interface;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Shipment_Tracking_Updates\Logger\WooCommerce_Logs
 */
class WooCommerce_Logs_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::_tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * @covers ::replace_wc_order_id_with_link
	 */
	public function test_replace_wc_order_id_with_link(): void {

		$sut = new WooCommerce_Logs();

		$column_output   = 'The first order is wc_order:123 and the second order is wc_order:456.';
		$item            = array(
			'time'    => 'string',
			'level'   => 'string',
			'message' => 'string',
			'context' => array(),
		);
		$column_name     = 'message';
		$logger_settings = $this->makeEmpty( WooCommerce_Logger_Settings_Interface::class );
		$logger          = $this->makeEmpty( BH_WP_PSR_Logger::class );

		\WP_Mock::userFunction(
			'admin_url',
			array(
				'args'       => array( \WP_Mock\Functions::type( 'string' ) ),
				'return_arg' => true,
				'times'      => 2,
			)
		);

		$result = $sut->replace_wc_order_id_with_link( $column_output, $item, $column_name, $logger_settings, $logger );

		$this->assertStringContainsString( 'href="post.php?post=123&action=edit', $result );
		$this->assertStringContainsString( 'href="post.php?post=456&action=edit', $result );

	}

}
