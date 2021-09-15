<?php
/**
 * Class Plugin_Test. Tests the root plugin setup.
 *
 * @package BrianHenryIE\WC_Shipment_Tracking_Updates
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\API_Interface;

/**
 * Verifies the plugin has been instantiated and added to PHP's $GLOBALS variable.
 */
class Plugin_Integration_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Test the main plugin object is added to PHP's GLOBALS and that it is the correct class.
	 */
	public function test_plugin_instantiated() {

		$this->assertArrayHasKey( 'bh_wc_shipment_tracking_updates', $GLOBALS );

		$this->assertInstanceOf( API_Interface::class, $GLOBALS['bh_wc_shipment_tracking_updates'] );
	}

}
