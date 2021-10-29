<?php
/**
 * The main functionality of the plugin.
 *
 * @link       https://BrianHenryIE.com
 * @since      1.0.0
 *
 * @package    brianhenryie/bh-wc-shipment-tracking-updates
 *
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\Tracker_Interface;
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
 * Made available as a global variable.
 *
 * @see $GLOBALS['bh_wc_shipment_tracking_updates']
 */
class API implements API_Interface {

	use LoggerAwareTrait;

	const BH_WC_SHIPMENT_TRACKING_UPDATES_ORDER_META_KEY = 'bh_wc_shipment_tracking_updates';

	/**
	 * Used to determine which order statuses are of interest.
	 *
	 * @see Settings_Interface::get_order_statuses_to_watch()
	 * @var Settings_Interface
	 */
	protected Settings_Interface $settings;

	/**
	 * Used to get the carrier API object.
	 *
	 * @var ContainerInterface
	 */
	protected ContainerInterface $container;

	/**
	 * Constructor.
	 *
	 * @param ContainerInterface $container Provider of objects in the plugin.
	 * @param Settings_Interface $settings The plugin settings.
	 * @param LoggerInterface    $logger PSR logger for the plugin.
	 */
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

			/**
			 * The query args ask to return only the order ids.
			 *
			 * @var int[] $orders_to_track
			 */
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
	 * For a given list of order ids, checks all those orders' tracking numbers for updates, firing actions when
	 * a tracking number's detail is updated and when an order status is updated.
	 *
	 * When an order has multiple tracking numbers, the status is updated only to the tracking which has progressed
	 * further, i.e. if one tracking number suggests in-transit and another suggests delivered, the order status will
	 * not change to completed until both suggest delivered.
	 *
	 * @see Order_Statuses
	 * @see API::BH_WC_SHIPMENT_TRACKING_UPDATES_ORDER_META_KEY
	 *
	 * @used-by Scheduler::execute_batch()
	 *
	 * @param array<int|string> $order_ids List of WooCommerce order ids to check for tracking updates.
	 * @return array<int|string, array<string, Tracking_Details_Abstract>> array<order_id, array<tracking_number, details>>
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

		if ( empty( $usps_tracking_numbers_orders ) ) {
			return array();
		}

		/**
		 * An instance of a tracker API class for querying for tracking updates.
		 *
		 * @var Tracker_Interface $usps_tracker
		 */
		$usps_tracker = $this->container->get( Container::USPS_SHIPMENT_TRACKER );

		$usps_tracking_numbers = array_map(
			function ( $element ) {
				return $element['tracking_number'];
			},
			$usps_tracking_numbers_orders
		);

		$details = $usps_tracker->query_multiple_tracking_numbers( $usps_tracking_numbers );

		$this->logger->debug( 'Tracking information returned for ' . count( $details ) . ' USPS tracking numbers', array( 'sample' => array_slice( $details, 0, 1, true ) ) );

		$updated_order_ids = array();

		foreach ( $details as $tracking_number => $fresh_detail ) {

			$order_id = $tracking_numbers[ $tracking_number ]['order_id'];

			$order = wc_get_order( $order_id );

			if ( ! ( $order instanceof WC_Order ) ) {
				$this->logger->error( 'Unexpectedly failed to instantiate order ' . $order_id, array( 'order_id' => $order_id ) );
				continue;
			}

			/**
			 * Prior saved tracking details for this order.
			 *
			 * @var array<string, Tracking_Details_Abstract>|false $order_meta_all_tracking
			 */
			$order_meta_all_tracking = $order->get_meta( self::BH_WC_SHIPMENT_TRACKING_UPDATES_ORDER_META_KEY, true );

			if ( empty( $order_meta_all_tracking ) || ! is_array( $order_meta_all_tracking ) ) {
				$order_meta_all_tracking = array();
			}

			// If this is the first time we have checked the tracking number against the carrier API.
			if ( ! isset( $order_meta_all_tracking[ $tracking_number ] ) ) {

				if ( ! is_null( $fresh_detail->get_equivalent_order_status() ) ) {
					$updated_order_ids[] = $order_id;

					$fresh_status           = $fresh_detail->get_equivalent_order_status();
					$send_email_action_name = "bh_wc_shipment_tracking_updates_{$fresh_status}_email";
					/**
					 * Fire the action to send the email when the tracking number's status updates.
					 * Presumably the status here is `in-transit`, which indicates the order has been scanned
					 * by the carrier and is "dispatched", because this is the first time information has been
					 * returned for this tracking number for this order.
					 *
					 * @hooked bh_wc_shipment_tracking_updates_in-transit_email
					 *
					 * @param int|string $order_id Integer post id for WooCommerce order.
					 * @param WC_Order $order WooCommerce order object.
					 */
					do_action( $send_email_action_name, $order_id, $order );

					/**
					 * Fires here the first time this tracking number's API returns data since it was added to this
					 * order.
					 *
					 * @param string $tracking_number The tracking number which has been updated.
					 * @param Tracking_Details_Abstract $fresh_detail The latest, complete information about this tracking number.
					 * @param WC_Order $order The associated WooCommerce order object (with the previous tracking details still saved in its meta).
					 */
					do_action( 'bh_wc_shipment_tracking_updates_tracking_number_updated', $tracking_number, $fresh_detail, $order );
				}
			} else {
				$previous_detail = $order_meta_all_tracking[ $tracking_number ];

				if ( $previous_detail->get_last_updated_time() !== $fresh_detail->get_last_updated_time() ) {
					$updated_order_ids[] = $order_id;

					// We have moved from one WooCommerce status to another, i.e. packed -> in-transit -> completed (delivered) / returning.
					if ( $previous_detail->get_equivalent_order_status() !== $fresh_detail->get_equivalent_order_status() ) {

						$status                 = $fresh_detail->get_equivalent_order_status();
						$send_email_action_name = "bh_wc_shipment_tracking_updates_{$status}_email";
						/**
						 * Fire the action to send the email when the tracking number's status updates.
						 *
						 * The status and corresponding email here will presumably:
						 * * completed : delivered
						 * * returning : returning
						 *
						 * @param int|string $order_id Integer post id for WooCommerce order.
						 * @param WC_Order $order WooCommerce order object.
						 */
						do_action( $send_email_action_name, $order_id, $order );
					}

					/**
					 * Fires here the every time the carrier's API reports the data has changed.
					 *
					 * @param string $tracking_number The tracking number which has been updated.
					 * @param Tracking_Details_Abstract $fresh_detail The latest, complete information about this tracking number.
					 * @param WC_Order $order The associated WooCommerce order object (with the previous tracking details still saved in its meta).
					 */
					do_action( 'bh_wc_shipment_tracking_updates_tracking_number_updated', $tracking_number, $fresh_detail, $order );

				}
			}

			$order_meta_all_tracking[ $tracking_number ] = $fresh_detail;
			$order->add_meta_data( self::BH_WC_SHIPMENT_TRACKING_UPDATES_ORDER_META_KEY, $order_meta_all_tracking, true );
			$order->save();
		}

		$updated_order_details = array();

		// Determine now, do we need to update the order status?
		// Where there are multiple tracking numbers for one order, we only update the order status to the furthest from finishing.
		foreach ( $updated_order_ids as $order_id ) {

			$order = wc_get_order( $order_id );

			if ( ! ( $order instanceof WC_Order ) ) {
				$this->logger->error( 'Unexpectedly failed to instantiate order ' . $order_id, array( 'order_id' => $order_id ) );
				continue;
			}

			/**
			 * Tracking details for this order, presumably saved above in this function (i.e. fresh).
			 *
			 * @var array<string, Tracking_Details_Abstract>|false $order_meta_all_tracking
			 */
			$order_meta_all_tracking = $order->get_meta( self::BH_WC_SHIPMENT_TRACKING_UPDATES_ORDER_META_KEY, true );

			if ( empty( $order_meta_all_tracking ) || ! is_array( $order_meta_all_tracking ) ) {
				$this->logger->warning(
					'Unexpectedly queried for missing order meta - should have been populated already in this function.',
					array(
						'order_id'         => $order_id,
						'queried_meta_key' => self::BH_WC_SHIPMENT_TRACKING_UPDATES_ORDER_META_KEY,
					)
				);
				continue;
			} elseif ( 1 === count( $order_meta_all_tracking ) ) {
				$tracking_detail = $order_meta_all_tracking[ array_key_first( $order_meta_all_tracking ) ];

				$fresh_status = $tracking_detail->get_equivalent_order_status();

				if ( is_null( $fresh_status ) ) {
					continue;
				}
			} else {
				// When there are multiple tracking numbers, do not mark the order complete until all are delivered.

				$expected_order_statuses = array_map(
					function( $element ) {
						return $element->get_equivalent_order_status();
					},
					$order_meta_all_tracking
				);

				// If one of the tracking numbers has not updated yet, its equivalent status will be null.
				if ( in_array( null, $expected_order_statuses, true ) ) {
					// Do not change the actual order status.
					continue;
				}

				// This is the order of precedence.
				// TODO: Move this to the Tracking_Details_Abstract class.
				// or order_statuses class.
				$all_statuses = array(
					Order_Statuses::PACKING_COMPLETE_WC_STATUS,
					Order_Statuses::IN_TRANSIT_WC_STATUS,
					Order_Statuses::RETURNING_WC_STATUS,
					'completed',
				);

				$expected_order_statuses_positions = array_map(
					function( $element ) use ( $all_statuses ) {
						return array_search( $element, $all_statuses, true );
					},
					$expected_order_statuses
				);

				$min_expected_order_status_position = min( $expected_order_statuses_positions );

				$fresh_status = $all_statuses[ $min_expected_order_status_position ];

			}

			if ( $order->get_status() !== $fresh_status ) {

				$this->logger->info( 'Updating order ' . $order_id . ' status to ' . $fresh_status, array( 'new_status' => $fresh_status ) );
				$order->set_status( $fresh_status );
				$order->save();

				$updated_order_details[ $order_id ] = $order_meta_all_tracking;

				/**
				 * Fires when an order's status is updated.
				 *
				 * @param array<string,Tracking_Details_Abstract> $order_meta_all_tracking All tracking data for this order, keyed by tracking number.
				 * @param WC_Order $order The associated WooCommerce order instance.
				 */
				do_action( 'bh_wc_shipment_tracking_updates_order_status_updated', $order_meta_all_tracking, $order );
			}
		}

		return $updated_order_details;
	}


	/**
	 * Get the tracking number and shipping provider (DHL/USPS/...).
	 *
	 * @param array<int|string> $order_ids Array of WooCommerce order ids.
	 * @return array<int|string, array{
	 *  tracking_number: string,
	 *  tracking_provider: string,
	 *  order_id: int|string
	 * }>
	 */
	protected function get_tracking_numbers_for_orders( array $order_ids ): array {

		$tracking_numbers = array();

		$shipment_tracking_actions = WC_Shipment_Tracking_Actions::get_instance();

		foreach ( $order_ids as $order_id ) {

			/**
			 * Multiple tracking items can be returned for each order_id.
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

				$tracking_number = (string) str_replace( ' ', '', $tracking_for_order['tracking_number'] );

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
	 * NB: This does not return orders with no tracking information.
	 *
	 * @used-by CLI::find_undispatched_orders()
	 *
	 * @return array<string, array{order_id: int, tracking_number: string, tracking_details: Tracking_Details_Abstract}>
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

		$this->update_orders( $order_ids );

		$unmoved_tracking_details = array();
		foreach ( $order_ids as $order_id ) {

			$order = wc_get_order( $order_id );

			if ( ! ( $order instanceof WC_Order ) ) {
				$this->logger->error( 'Unexpectedly failed to instantiate order ' . $order_id, array( 'order_id' => $order_id ) );
				continue;
			}

			/**
			 * Having updated the order's tracking numbers' details with the call to updated_orders() above, fetch the data.
			 *
			 * @var  array<string, Tracking_Details_Abstract> $tracking_numbers_details
			 */
			$tracking_numbers_details = $order->get_meta( self::BH_WC_SHIPMENT_TRACKING_UPDATES_ORDER_META_KEY, true );

			foreach ( $tracking_numbers_details as $tracking_number => $detail ) {
				if ( ! $detail->is_dispatched() ) {
					$unmoved_tracking_details[ $tracking_number ] = array(
						'order_id'         => $order_id,
						'tracking_number'  => $tracking_number,
						'tracking_details' => $detail,
					);
				}
			}
		}
		$this->logger->debug( count( $unmoved_tracking_details ) . ' unmoved tracking numbers.' );

		return $unmoved_tracking_details;
	}

}
