<?php
/**
 * Tests for the root plugin file.
 *
 * @package BrianHenryIE\WC_Shipment_Tracking_Updates
 * @author  BrianHenryIE <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\API;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Includes\BH_WC_Shipment_Tracking_Updates;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\Logger;

class Plugin_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		\WP_Mock::tearDown();
		\Patchwork\restoreAll();
	}

	/**
	 * Verifies the plugin initialization.
	 */
	public function test_plugin_include(): void {

		// Prevents code-coverage counting, and removes the need to define the WordPress functions that are used in that class.
		\Patchwork\redefine(
			array( BH_WC_Shipment_Tracking_Updates::class, '__construct' ),
			function( $api, $settings, $logger ) {}
		);

		\Patchwork\redefine(
			array( Logger::class, '__construct' ),
			function( $settings ) {}
		);

		$plugin_root_dir = dirname( __DIR__, 2 ) . '/src';

		\WP_Mock::userFunction(
			'plugin_dir_path',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'return' => $plugin_root_dir . '/',
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'plugin_basename',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'return' => 'bh-wc-shipment-tracking-updates/bh-wc-shipment-tracking-updates.php',
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'register_activation_hook',
			array(
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'register_deactivation_hook',
			array(
				'times' => 1,
			)
		);

		if ( class_exists( \BH_WC_Shipment_Tracking_Updates_SLSWC_Client::class ) ) {
			$slsswc = $this->makeEmpty( \BH_WC_Shipment_Tracking_Updates_SLSWC_Client::class );
			\Patchwork\redefine(
				array( \BH_WC_Shipment_Tracking_Updates_SLSWC_Client::class, 'get_instance' ),
				function () use ( $slsswc ) {
					return $slsswc;
				}
			);
		}

		ob_start();

		include $plugin_root_dir . '/bh-wc-shipment-tracking-updates.php';

		$printed_output = ob_get_contents();

		ob_end_clean();

		$this->assertEmpty( $printed_output );

		$this->assertArrayHasKey( 'bh_wc_shipment_tracking_updates', $GLOBALS );

		$this->assertInstanceOf( API::class, $GLOBALS['bh_wc_shipment_tracking_updates'] );

	}

}
