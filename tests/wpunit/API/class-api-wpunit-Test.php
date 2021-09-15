<?php
/**
 *
 *
 * @package BrianHenryIE\WC_Shipment_Tracking_Updates
 * @author  BrianHenryIE <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\Tracker_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\Tracking_Details_Abstract;
use Psr\Container\ContainerInterface;
use WC_Order;

/**
 *
 * @see API
 * @coversDefaultClass \BrianHenryIE\WC_Shipment_Tracking_Updates\API\API
 */
class API_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::update_orders
	 */
	public function test_update_orders() {

		$logger   = new ColorLogger();
		$settings = new Settings();

		$tracking_query_result = $this->makeEmpty(
			Tracking_Details_Abstract::class,
			array()
		);

		$usps_tracker = $this->makeEmpty(
			Tracker_Interface::class,
			array(
				'query_multiple_tracking_numbers' => array( '123' => $tracking_query_result ),
			)
		);

		$container = $this->makeEmpty(
			ContainerInterface::class,
			array(
				'get' => $usps_tracker,
			)
		);
		$api       = new API( $container, $settings, $logger );

		$order    = new WC_Order();
		$order_id = $order->save();

		wc_st_add_tracking_number( $order_id, '123', 'usps' );

		$results = $api->update_orders( array( $order_id ) );

		$this->assertNotEmpty( $results );

		$result = $results[ $order_id ]['123'];

		$this->assertInstanceOf( Tracking_Details_Abstract::class, $result );
	}

	/**
	 * Given six orders, which ones should be updated?
	 *
	 * @covers ::find_orders_to_update
	 */
	public function test_get_orders_to_update() {

		$logger    = new ColorLogger();
		$settings  = new Settings();
		$container = $this->makeEmpty(
			ContainerInterface::class
		);

		$api = new API( $container, $settings, $logger );

		$class  = new \ReflectionClass( API::class );
		$method = $class->getMethod( 'find_orders_to_update' );
		$method->setAccessible( true );

		// Since the plugin is not active, the custom statuses will not be properly available, so use the filter
		// to add a standard status to what is searched for.
		$include_statuses = function ( array $statuses ): array {
			$statuses[] = 'completed';
			$statuses[] = 'refunded';
			return $statuses;
		};
		add_filter( 'bh_wc_shipment_tracking_updates_statuses', $include_statuses );

		$order = new WC_Order();
		$order->set_status( 'pending' );
		$order->save();

		$order = new WC_Order();
		$order->set_status( 'processing' );
		$order->save();

		$order = new WC_Order();
		$order->set_status( 'on-hold' );
		$order->save();

		$order = new WC_Order();
		$order->set_status( 'completed' );
		$order->save();

		$order = new WC_Order();
		$order->set_status( 'on-hold' );
		$order->save();

		$order = new WC_Order();
		$order->set_status( 'refunded' );
		$order->save();

		$result = $method->invoke( $api );

		$this->assertCount( 2, $result );

	}

	/**
	 * @covers ::find_undispatched_orders
	 */
	public function test_find_undispatched_orders() {

		$logger   = new ColorLogger();
		$settings = new Settings();

		$tracking_query_result123 = $this->makeEmpty(
			Tracking_Details_Abstract::class,
			array()
		);
		$tracking_query_result456 = $this->makeEmpty(
			Tracking_Details_Abstract::class,
			array()
		);

		$usps_tracker = $this->makeEmpty(
			Tracker_Interface::class,
			array(
				'query_multiple_tracking_numbers' => array(
					'123' => $tracking_query_result123,
					'456' => $tracking_query_result456,
				),
			)
		);

		$container = $this->makeEmpty(
			ContainerInterface::class,
			array(
				'get' => $usps_tracker,
			)
		);
		$api       = new API( $container, $settings, $logger );

		// Since the plugin is not active, the custom statuses will not be properly available, so use the filter
		// to add a standard status to what is searched for.
		$include_statuses = function ( array $statuses ): array {
			$statuses[] = 'processing';
			return $statuses;
		};
		add_filter( 'bh_wc_shipment_tracking_updates_statuses', $include_statuses );

		$order = new WC_Order();
		$order->set_status( 'pending' );
		$order->save();

		$order = new WC_Order();
		$order->set_status( 'completed' );
		$order_id = $order->save();

		$tracking_number = '123';
		wc_st_add_tracking_number( $order_id, $tracking_number, 'USPS' );

		$order = new WC_Order();
		$order->set_status( 'processing' );
		$order_id = $order->save();

		$tracking_number = '456';
		wc_st_add_tracking_number( $order_id, $tracking_number, 'USPS' );

		$result = $api->find_undispatched_orders();

		$this->assertCount( 2, $result );

	}
}
