<?php

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce_Shipment_Tracking;

use _WP_Dependency;
use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\API_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\Tracking_Details_Abstract;
use Codeception\Stub\Expected;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce_Shipment_Tracking\Admin_Order_View
 */
class Admin_Order_View_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	public function tearDown(): void {
		parent::_tearDown();

		$wp_scripts = wp_scripts();

		unset( $wp_scripts->registered['wc-shipment-tracking-js'] );
	}

	/**
	 * Happy path test to proofread the function is correct.
	 *
	 * Should enqueue a script.
	 *
	 * @covers ::tracking_information_for_order
	 * @covers ::__construct
	 */
	public function test_happy_tracking_information_for_order(): void {

		$dummy_tracking = $this->makeEmpty(
			Tracking_Details_Abstract::class,
			array(
				'get_delivery_time' => new \DateTime(),
			)
		);

		$logger = new ColorLogger();
		$api    = $this->makeEmpty(
			API_Interface::class,
			array(
				'get_saved_tracking_data_for_order' => Expected::once(
					function( $order_id ) use ( $dummy_tracking ) {
						return array( 'test123' => $dummy_tracking ); }
				),
			)
		);

		$sut = new Admin_Order_View( $api, $logger );

		// phpcs:disable WordPress.WP.EnqueuedResourceParameters.NotInFooter
		// phpcs:disable WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_register_script( 'wc-shipment-tracking-js', '' );

		$order    = new \WC_Order();
		$order_id = $order->save();

		$post     = new \stdClass();
		$post->ID = $order_id;

		$GLOBALS['post'] = $post;

		// Populate some tracking information
		// It will return at `if ( empty( $replacement_html ) ) {` otherwise.
		$tracking_number = 'test123';
		$provider        = 'test-carrier';
		wc_st_add_tracking_number( $order_id, $tracking_number, $provider );

		$sut->tracking_information_for_order();

		// Was the script enqueued?
		$shipment_tracking_script_handle = 'wc-shipment-tracking-js';
		/** @var _WP_Dependency $is_shipment_tracking_script_enqueued */
		$is_shipment_tracking_script_enqueued = wp_scripts()->query( $shipment_tracking_script_handle );
		// This should have the script: `$is_shipment_tracking_script_enqueued->extra['after'][1]`.
		$this->assertArrayHasKey( 'after', $is_shipment_tracking_script_enqueued->extra );
	}

	/**
	 * If the Shipment Tracking plugin has not enqueued its script, there's nothing to do, it should return quickly.
	 *
	 * @covers ::tracking_information_for_order
	 */
	public function test_no_shipment_tracking_to_update(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty( API_Interface::class, array( 'get_saved_tracking_data_for_order' => Expected::never() ) );

		$sut = new Admin_Order_View( $api, $logger );

		$sut->tracking_information_for_order();

		// Was the script enqueued?
		$shipment_tracking_script_handle      = 'wc-shipment-tracking-js';
		$is_shipment_tracking_script_enqueued = wp_scripts()->query( $shipment_tracking_script_handle );
		// This would have the script: `$is_shipment_tracking_script_enqueued->extra['after'][1]`.
		$this->assertFalse( $is_shipment_tracking_script_enqueued );
	}


}
