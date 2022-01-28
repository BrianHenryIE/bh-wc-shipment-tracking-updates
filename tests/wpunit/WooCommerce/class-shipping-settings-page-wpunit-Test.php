<?php

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Shipping_Settings_Page
 */
class Shipping_Settings_Page_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::shipment_tracking_updates_settings
	 */
	public function test_settings_array_happy_path(): void {

		$sut = new Shipping_Settings_Page();

		$result = $sut->shipment_tracking_updates_settings( array(), 'bh-wc-shipment-tracking-updates' );

		$this->assertIsArray( $result );

	}


}
