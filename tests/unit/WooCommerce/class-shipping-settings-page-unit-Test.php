<?php

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Shipping_Settings_Page
 */
class Shipping_Settings_Page_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		\WP_Mock::setUp();
	}

	/**
	 * Without this, WP_Mock userFunctions might stick around for the next test.
	 */
	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * Use hyphens in the section name because it will be used in the URL.
	 *
	 * @covers ::shipment_tracking_updates_section
	 */
	public function test_register_section_filter(): void {

		$sut = new Shipping_Settings_Page();

		\WP_Mock::userFunction(
			'__',
			array(
				'return_arg' => 0,
				'times'      => 1,
			)
		);

		$result = $sut->shipment_tracking_updates_section( array() );

		$this->assertArrayHasKey( 'bh-wc-shipment-tracking-updates', $result );
		$this->assertContains( 'Shipment Tracking Updates', $result );
	}

	/**
	 * Test the settings are not added to the wrong section.
	 *
	 * @covers ::shipment_tracking_updates_settings
	 */
	public function test_settings_array_wrong_section(): void {

		$sut = new Shipping_Settings_Page();

		$result = $sut->shipment_tracking_updates_settings( array(), 'NOT-bh-wc-shipment-tracking-updates' );

		$this->assertEmpty( $result );
	}

}
