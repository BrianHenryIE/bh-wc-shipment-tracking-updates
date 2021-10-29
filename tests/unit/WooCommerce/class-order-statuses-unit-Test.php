<?php
/**
 *
 *
 * @package BrianHenryIE\WC_Shipment_Tracking_Updates
 * @author  BrianHenryIE <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Settings_Interface;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Order_Statuses
 */
class Order_Statuses_Unit_Test extends \Codeception\Test\Unit {

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
	 * WordPress's register_status() function should be called three times.
	 *
	 * @covers ::register_status
	 */
	public function test_register_statuses(): void {

		\WP_Mock::userFunction(
			'register_post_status',
			array(
				'args' => array( 'wc-' . Order_Statuses::PACKING_COMPLETE_WC_STATUS, \WP_Mock\Functions::type( 'array' ) ),
			)
		);

		\WP_Mock::userFunction(
			'register_post_status',
			array(
				'args' => array( 'wc-' . Order_Statuses::IN_TRANSIT_WC_STATUS, \WP_Mock\Functions::type( 'array' ) ),
			)
		);

		\WP_Mock::userFunction(
			'register_post_status',
			array(
				'args' => array( 'wc-' . Order_Statuses::RETURNING_WC_STATUS, \WP_Mock\Functions::type( 'array' ) ),
			)
		);

		\WP_Mock::userFunction(
			'_n_noop',
			array(
				'times' => 3,
			)
		);

		$logger = new ColorLogger();

		$sut = new Order_Statuses( $logger );

		$sut->register_status();
	}

	/**
	 * @covers ::add_to_paid_status_list
	 */
	public function test_add_to_paid_status_list(): void {

		// Should have three statuses.

		$logger = new ColorLogger();

		$sut = new Order_Statuses( $logger );

		$result = (array) $sut->add_to_paid_status_list( array() );

		$this->assertCount( 3, $result );
	}

	/**
	 * Some of the refund reports have no order status set. We don't want to add to them anyway.
	 *
	 * @covers ::add_to_reports_status_list
	 */
	public function test_add_to_reports_status_list_false(): void {

		$logger = new ColorLogger();

		$sut = new Order_Statuses( $logger );

		$result = (bool) $sut->add_to_reports_status_list( false );

		$this->assertFalse( $result );

	}

	/**
	 * Some of the refund reports only have refunded as the status. We don't want to add to them anyway.
	 *
	 * @see Order_Statuses::add_to_reports_status_list()
	 */
	public function test_add_to_reports_status_list_one_status(): void {

		$logger = new ColorLogger();

		$sut = new Order_Statuses( $logger );

		$result = (array) $sut->add_to_reports_status_list( array( 'refunded' ) );

		// Verify it hasn't changed.
		$this->assertCount( 1, $result );
		$this->assertContains( 'refunded', $result );

	}


	/**
	 * Any report which is including completed, on-hold and processing should have our new statuses,
	 * since they are paid order statuses.
	 *
	 * @covers ::add_to_reports_status_list
	 */
	public function test_add_to_reports_status_list(): void {

		$logger = new ColorLogger();

		$sut = new Order_Statuses( $logger );

		$result = (array) $sut->add_to_reports_status_list( array( 'completed', 'on-hold', 'processing' ) );

		$this->assertContains( Order_Statuses::PACKING_COMPLETE_WC_STATUS, $result );
		$this->assertContains( Order_Statuses::IN_TRANSIT_WC_STATUS, $result );
		$this->assertContains( Order_Statuses::RETURNING_WC_STATUS, $result );

	}

	/**
	 * Check they're not added if they're already present.
	 *
	 * @covers ::add_to_reports_status_list
	 */
	public function test_add_to_reports_status_list_once_only(): void {

		$logger = new ColorLogger();

		$sut = new Order_Statuses( $logger );

		$result = (array) $sut->add_to_reports_status_list(
			array(
				'completed',
				'on-hold',
				'processing',
				Order_Statuses::PACKING_COMPLETE_WC_STATUS,
				Order_Statuses::IN_TRANSIT_WC_STATUS,
				Order_Statuses::RETURNING_WC_STATUS,
			)
		);

		$this->assertCount( 6, $result );
	}

}
