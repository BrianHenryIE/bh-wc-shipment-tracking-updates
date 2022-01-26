<?php
/**
 * Tests for the root plugin file.
 *
 * @package BrianHenryIE\WC_Shipment_Tracking_Updates
 * @author  BrianHenryIE <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\API;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Settings;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Includes\BH_WC_Shipment_Tracking_Updates;

/**
 *
 */
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
			array( Settings::class, 'get_plugin_slug' ),
			function(): string {
				return 'bh-wc-shipment-tracking-updates'; }
		);
		\Patchwork\redefine(
			array( Settings::class, 'get_log_level' ),
			function(): string {
				return 'info'; }
		);
		\Patchwork\redefine(
			array( Settings::class, 'get_plugin_basename' ),
			function(): string {
				return 'bh-wc-shipment-tracking-updates/bh-wc-shipment-tracking-updates.php'; }
		);

		$plugin_root_dir = dirname( __DIR__, 2 ) . '/src';

		\WP_Mock::userFunction(
			'plugin_dir_path',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'return' => $plugin_root_dir . '/',
			)
		);

		\WP_Mock::userFunction(
			'plugin_basename',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'return' => 'bh-wc-shipment-tracking-updates/bh-wc-shipment-tracking-updates.php',
			)
		);

		\WP_Mock::userFunction(
			'register_activation_hook'
		);

		\WP_Mock::userFunction(
			'register_deactivation_hook'
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( 'bh_wc_shipment_tracking_updates_log_level', 'info' ),
				'return' => 'notice',
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( 'active_plugins' ),
				'return' => array(),
			)
		);

		\WP_Mock::userFunction(
			'is_admin',
			array(
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'get_current_user_id'
		);

		\WP_Mock::userFunction(
			'wp_normalize_path',
			array(
				'return_arg' => true,
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( 'active_plugins' ),
				'return' => array( 'woocommerce/woocommerce.php' ),
			)
		);

		\WP_Mock::userFunction(
			'did_action',
			array(
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'add_action',
			array(
				'return' => false,
			)
		);

		ob_start();

		include $plugin_root_dir . '/bh-wc-shipment-tracking-updates.php';

		$printed_output = ob_get_contents();

		ob_end_clean();

		$this->assertEmpty( $printed_output );

		$this->assertArrayHasKey( 'bh_wc_shipment_tracking_updates', $GLOBALS );

		$this->assertInstanceOf( API::class, $GLOBALS['bh_wc_shipment_tracking_updates'] );

	}

}
