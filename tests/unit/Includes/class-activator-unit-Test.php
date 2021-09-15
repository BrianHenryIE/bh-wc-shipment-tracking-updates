<?php

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\Includes;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Settings;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Shipment_Tracking_Updates\Includes\Activator
 */
class Activator_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::_tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * Check other plugins wp_option entries for a USPS username.
	 *
	 * @covers ::find_usps_username
	 */
	public function test_find_usps_username_checks_options() {

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( Settings::USPS_USER_ID_OPTION ),
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( 'bh-wc-address-validation-usps-username' ),
				'return' => false,
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( 'usps_id' ),
				'return' => false,
				'times'  => 1,
			)
		);

		Activator::activate();
	}


	/**
	 * When a username is found from another plugin, save it into our plugin settings.
	 *
	 * @covers ::find_usps_username
	 */
	public function test_find_usps_username_updates_option() {

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( Settings::USPS_USER_ID_OPTION ),
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( 'bh-wc-address-validation-usps-username' ),
				'return' => 'FOOBAR',
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( 'usps_id' ),
				'return' => false,
				'times'  => 0,
			)
		);

		\WP_Mock::userFunction(
			'update_option',
			array(
				'args'  => array( Settings::USPS_USER_ID_OPTION, 'FOOBAR' ),
				'times' => 1,
			)
		);

		Activator::activate();
	}
}
