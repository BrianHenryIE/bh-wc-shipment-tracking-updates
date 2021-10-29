<?php
/**
 * Tests for Deactivator.
 * * Change custom order statuses to completed.
 * * Unregister action scheduler ations.
 *
 * @package brianhenryie/bh-wc-shipment-tracking-updates
 * @author  BrianHenryIE <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\Includes;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Settings_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Order_Statuses;
use WC_Order;

/**
 * Class Deactivator_WPUnit_Test
 *
 * @see Deactivator
 * @coversDefaultClass \BrianHenryIE\WC_Shipment_Tracking_Updates\Includes\Deactivator
 */
class Deactivator_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::change_order_statuses
	 */
	public function test_changes_custom_statuses_to_completed(): void {

		$logger = new ColorLogger();

		$order_statuses = new Order_Statuses( $logger );
		$order_statuses->register_status();
		add_filter( 'wc_order_statuses', array( $order_statuses, 'add_order_status_to_woocommerce' ) );

		$order = new WC_Order();
		$order->set_status( Order_Statuses::PACKING_COMPLETE_WC_STATUS );
		$order->save();

		$order = new WC_Order();
		$order->set_status( Order_Statuses::IN_TRANSIT_WC_STATUS );
		$order->save();

		$order = new WC_Order();
		$order->set_status( Order_Statuses::RETURNING_WC_STATUS );
		$order->save();

		/** @var WC_Order[] $orders */
		$orders = wc_get_orders(
			array(
				'limit'    => -1,
				'status'   => array(
					'wc-' . Order_Statuses::PACKING_COMPLETE_WC_STATUS,
					'wc-' . Order_Statuses::IN_TRANSIT_WC_STATUS,
					'wc-' . Order_Statuses::RETURNING_WC_STATUS,
				),
				'paginate' => false,
			)
		);

		assert( 3 === count( $orders ) );

		/** @var WC_Order[] $orders */
		$orders = wc_get_orders(
			array(
				'limit'    => -1,
				'status'   => array( 'wc-completed' ),
				'paginate' => false,
			)
		);

		assert( 0 === count( $orders ) );

		// Act.
		Deactivator::deactivate();

		/** @var WC_Order[] $orders */
		$orders = wc_get_orders(
			array(
				'limit'    => -1,
				'status'   => array(
					'wc-' . Order_Statuses::PACKING_COMPLETE_WC_STATUS,
					'wc-' . Order_Statuses::IN_TRANSIT_WC_STATUS,
					'wc-' . Order_Statuses::RETURNING_WC_STATUS,
				),
				'paginate' => false,
			)
		);

		$this->assertCount( 0, $orders );

		/** @var WC_Order[] $orders */
		$orders = wc_get_orders(
			array(
				'limit'    => -1,
				'status'   => array( 'wc-completed' ),
				'paginate' => false,
			)
		);

		$this->assertCount( 3, $orders );

		remove_filter( 'wc_order_statuses', array( $order_statuses, 'add_order_status_to_woocommerce' ) );
	}

}
