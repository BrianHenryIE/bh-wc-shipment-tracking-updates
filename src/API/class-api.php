<?php
/**
 * The main functionality of the plugin.
 *
 * @link       https://BrianHenryIE.com
 * @since      1.0.0
 *
 * @package    BrianHenryIE\WC_Shipment_Tracking_Updates
 * @subpackage BrianHenryIE\WC_Shipment_Tracking_Updates\API
 *
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\Tracking_Details_Abstract;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\USPS_Tracker;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Container;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Action_Scheduler\Scheduler;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Order_Statuses;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Order;
use WC_Shipment_Tracking_Actions;

/**
 *
 */
class API implements API_Interface {

	use LoggerAwareTrait;

	protected Settings_Interface $settings;

	protected ContainerInterface $container;

	public function __construct( ContainerInterface $container, Settings_Interface $settings, LoggerInterface $logger ) {

		$this->setLogger( $logger );
		$this->settings  = $settings;
		$this->container = $container;
	}


	/**
	 * Finds the set of orders whose status indicates they are still in transit
	 * and schedules an update of each.
	 *
	 * @used-by Scheduler::execute()
	 */
	public function start_background_update_jobs(): void {

		if ( ! class_exists( WC_Shipment_Tracking_Actions::class ) ) {
			$this->logger->warning( 'WC_Shipment_Tracking_Actions class not present. Shipment Tracking plugin presumably not active' );
			return;
		}

		$order_ids = $this->find_orders_to_update();

		$jobs = array_chunk( $order_ids, USPS_Tracker::MAX_TRACKING_IDS_PER_USPS_API_CALL );

		foreach ( $jobs as $job ) {
			as_schedule_single_action( time(), Scheduler::SINGLE_UPDATE_HOOK, array( $job ), Scheduler::ACTION_SCHEDULER_GROUP );
		}

	}

	/**
	 * Return orders that are packing, in-transit, returning.
	 *
	 * @return int[]
	 */
	protected function find_orders_to_update(): array {

		$statuses = $this->settings->get_order_statuses_to_watch();

		$statuses = apply_filters( 'bh_wc_shipment_tracking_updates_statuses', $statuses );

		// Chosen because that's the limit for orders that can be fetched for a wp-admin shop_order view.
		// It may be hardcoded somewhere in WooCommerce or WordPress for queries.
		$limit  = 200;
		$offset = 0;

		$all_orders_to_track = array();

		$since      = time() - ( MONTH_IN_SECONDS * 2 );
		$since      = time() - ( WEEK_IN_SECONDS * 2 );
		$date_after = ( new \DateTime( '@' . $since ) )->setTimezone( wp_timezone() );

		do {

			$args = array(
				'limit'   => $limit,
				'status'  => $statuses,
				'orderby' => 'ID',
				'order'   => 'DESC', // most recent.
				'offset'  => $offset,
				// TODO: date since, should be DateTime.THIS IS NOT WORKING!
				// 'date_after' => $date_after,
				'return'  => 'ids',
			);

			$this->logger->debug( "Fetching $limit orders from offset $offset", array( 'args' => $args ) );

			/** @var int[] $orders_to_track */
			$orders_to_track = wc_get_orders( $args );

			$this->logger->debug( 'Returned ' . count( $orders_to_track ) . ' orders' );

			$all_orders_to_track = array_merge( $all_orders_to_track, $orders_to_track );

			$offset = $offset + $limit;

			$orders_count = count( $orders_to_track );

		} while ( $orders_count === $limit && $offset < 1501 );

		// reverse to check the oldest first.
		return array_reverse( $all_orders_to_track );

	}


	/**
	 *
	 * Changes the order status
	 * Saves the tracking details to order meta key `bh_wc_shipment_tracking_updates`.
	 *
	 * @see Order_Statuses
	 *
	 * @used-by Scheduler::execute_batch()
	 *
	 * @param array $order_ids
	 * @return array<string, array<string, Tracking_Details_Abstract> array<order_id, array<tracking_number, Details>
	 */
	public function update_orders( array $order_ids ): array {

		$this->logger->debug( 'Updating ' . count( $order_ids ) . ' orders', array( 'order_ids' => $order_ids ) );

		$tracking_numbers = $this->get_tracking_numbers_for_orders( $order_ids );

		$usps_tracking_numbers_orders = array_filter(
			$tracking_numbers,
			function( $element ) {
				return 'usps' === $element['tracking_provider'];
			}
		);

		$all_details = array();

		if ( ! empty( $usps_tracking_numbers_orders ) ) {

			/** @var USPS_Tracker $usps_tracker */
			$usps_tracker = $this->container->get( Container::USPS_SHIPMENT_TRACKER );

			$usps_tracking_numbers = array_map(
				function( $element ) {
					return $element['tracking_number'];
				},
				$usps_tracking_numbers_orders
			);
			$details               = $usps_tracker->query_multiple_tracking_numbers( $usps_tracking_numbers );

			$this->logger->debug( 'Tracking information returned for ' . count( $details ) . ' USPS tracking numbers', array( 'sample' => array_slice( $details, 1, true ) ) );

			foreach ( $details as $tracking_number => $detail ) {

				$order_id = $tracking_numbers[ $tracking_number ]['order_id'];
				$detail->set_order_id( $order_id );

				$order = wc_get_order( $order_id );

				$meta_key   = 'bh_wc_shipment_tracking_updates';
				$order_meta = $order->get_meta( $meta_key, true );

				if ( empty( $order_meta ) || ! is_array( $order_meta ) ) {
					$order_meta = array();
				}

				// Compare the last updated time see has it changed.
				// Compare the order status and make sure it is the same.

				// First update is when USPS pick up the package.
				$updated = ! empty( $detail->get_order_status() );
				if (
					(
						isset( $order_meta[ $tracking_number ] ) && $order_meta[ $tracking_number ]->get_last_updated_time() === $detail->get_last_updated_time()
					)
					&&
					(
						$detail->get_order_status() === $order->get_status()
					)
					) {
					$updated = false;
				}

				// $this->logger->debug( 'order ' . $order_id . ' last updated ' . $detail->get_last_updated_time()->format( DATE_ATOM ) . ' with carrier status "' . $detail->get_carrier_status() . '" and order status "' . $detail->get_order_status() . '"' );

				$order_meta[ $tracking_number ] = $detail;

				if ( $updated ) {
					// TODO when an order has two tracking numbers, do not mark it complete unless both are delivered.
					$existing_order_status = $order->get_status();
					if ( $existing_order_status !== $detail->get_order_status() && ! empty( $detail->get_order_status() ) ) {
						$order->set_status( $detail->get_order_status() );

						$this->logger->info( 'Updating order ' . $order_id . ' status to ' . $detail->get_order_status(), array( 'new_status' => $detail->get_order_status() ) );
					} else {

					}

					$order->update_meta_data( $meta_key, $order_meta );
					$order->save();

					/**
					 * @param WC_Order $order
					 * @param Tracking_Details_Abstract $detail
					 */
					do_action( 'bh_wc_shipment_tracking_updates_order_udpated', $order, $detail );
				}

				$order_meta[ $tracking_number ]->set_is_updated( $updated );

				$all_details[ $order_id ] = $order_meta;
			}
		}

		return $all_details;
	}


	/**
	 * Get the tracking number and shipping provider (DHL/USPS/...).
	 *
	 * @param string[] $order_ids
	 * @return array<string, array{
	 *  string: tracking_number
	 *  string: tracking_provider,
	 *  int: order_id
	 * }>
	 */
	protected function get_tracking_numbers_for_orders( array $order_ids ): array {

		$tracking_numbers = array();

		$shipment_tracking_actions = WC_Shipment_Tracking_Actions::get_instance();

		foreach ( $order_ids as $order_id ) {

			/**
			 * Multiple can be returned.
			 * array{
			 *  array{
			 *   string: tracking_provider,
			 *   string: custom_tracking_provider,
			 *   string: custom_tracking_link,
			 *   string: tracking_number,
			 *   string: date_shipped,
			 *   string: tracking_id
			 *  }
			 * }
			 */
			$trackings_for_order = $shipment_tracking_actions->get_tracking_items( $order_id );

			foreach ( $trackings_for_order as $tracking_for_order ) {

				$tracking_provider = strtolower( $tracking_for_order['tracking_provider'] );

				$tracking_number = str_replace( ' ', '', $tracking_for_order['tracking_number'] );

				$tracking_numbers[ $tracking_number ] = array(
					'tracking_number'   => $tracking_number,
					'tracking_provider' => $tracking_provider,
					'order_id'          => $order_id,
				);
			}
		}

		return $tracking_numbers;
	}

	/**
	 * NB: This discards orders with no tracking information.
	 *
	 * @used-by CLI::find_undispatched_orders()
	 *
	 * @return array<string, Tracking_Details_Abstract> Array indexed by the tracking number.
	 */
	public function find_undispatched_orders(): array {

		$this->logger->debug( 'Beginning search for undispatched orders' );

		// Same query as above but include "completed" orders.
		$include_completed = function ( array $statuses ): array {
			$statuses[] = 'completed';
			return $statuses;
		};

		add_filter( 'bh_wc_shipment_tracking_updates_statuses', $include_completed );

		$order_ids = $this->find_orders_to_update();

		remove_filter( 'bh_wc_shipment_tracking_updates_statuses', $include_completed );

		$order_tracking_details = $this->update_orders( $order_ids );

		$this->logger->debug( 'Tracking details returned for ' . count( $order_tracking_details ) . ' orders' );

		$unmoved_tracking_details = array();
		foreach ( $order_tracking_details as $order_id => $tracking_numbers_details ) {
			/**
			 * @var  string $tracking_number
			 * @var  Tracking_Details_Abstract $detail
			 */
			foreach ( $tracking_numbers_details as $tracking_number => $detail ) {
				if ( ! $detail->is_dispatched() ) {
					$unmoved_tracking_details[ $tracking_number ] = $detail;
				}
			}
		}
		$this->logger->debug( count( $unmoved_tracking_details ) . ' unmoved orders.' );

		return $unmoved_tracking_details;
	}

}
