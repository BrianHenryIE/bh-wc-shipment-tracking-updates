<?php
/**
 * Tests for BrianHenryIE\WC_Shipment_Tracking_Updates main setup class. Tests the actions are correctly added.
 *
 * @package    brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Includes;

use BrianHenryIE\WC_Shipment_Tracking_Updates\Admin\Admin;

/**
 * Class Develop_Test
 */
class BH_WC_Shipment_Tracking_Updates_Integration_Test extends \Codeception\TestCase\WPTestCase {

	public function hooks() {
		$hooks = array(
			array( 'init', I18n::class, 'load_plugin_textdomain' ),
			array( 'admin_enqueue_scripts', Admin::class, 'enqueue_scripts' ),
			array( 'admin_enqueue_scripts', Admin::class, 'enqueue_styles' ),
		);
		return $hooks;
	}

	/**
	 * @dataProvider hooks
	 */
	public function test_is_function_hooked_on_action( $action_name, $class_type, $method_name, $expected_priority = 10 ) {

		global $wp_filter;

		$this->assertArrayHasKey( $action_name, $wp_filter, "$method_name definitely not hooked to $action_name" );

		$actions_hooked = $wp_filter[ $action_name ];

		$this->assertArrayHasKey( $expected_priority, $actions_hooked, "$method_name definitely not hooked to $action_name priority $expected_priority" );

		$hooked_method = null;
		foreach ( $actions_hooked[ $expected_priority ] as $action ) {
			$action_function = $action['function'];
			if ( is_array( $action_function ) ) {
				if ( $action_function[0] instanceof $class_type ) {
					if ( $method_name === $action_function[1] ) {
						$hooked_method = $action_function[1];
						break;
					}
				}
			}
		}

		$this->assertNotNull( $hooked_method, "No methods on an instance of $class_type hooked to $action_name" );

		$this->assertEquals( $method_name, $hooked_method, "Unexpected method name for $class_type class hooked to $action_name" );

	}
}
