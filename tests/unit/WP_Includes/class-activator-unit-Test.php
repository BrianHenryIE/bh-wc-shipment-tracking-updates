<?php

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Includes;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\USPS\USPS_Settings;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Includes\Activator
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
	public function test_find_usps_username_checks_options(): void {

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( USPS_Settings::USPS_USER_ID_OPTION ),
				'return' => false,
				'times'  => 1,
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
				'args'   => array( 'bh_wc_address_validation_usps_username' ),
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
	public function test_find_usps_username_updates_option(): void {

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( USPS_Settings::USPS_USER_ID_OPTION ),
				'return' => false,
				'times'  => 1,
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
				'args'  => array( USPS_Settings::USPS_USER_ID_OPTION, 'FOOBAR' ),
				'times' => 1,
			)
		);

		Activator::activate();
	}


	/**
	 * When it's already configured, even though another plugin has a USPS username, update_option will never be called.
	 */
	public function test_exits_early_when_already_configured(): void {

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( USPS_Settings::USPS_USER_ID_OPTION ),
				'return' => 'ALREADY_CONFIGURED',
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( 'bh-wc-address-validation-usps-username' ),
				'return' => 'FOOBAR',
				'times'  => 0,
			)
		);

		\WP_Mock::userFunction(
			'update_option',
			array(
				'times' => 0,
			)
		);

		Activator::activate();
	}
}
