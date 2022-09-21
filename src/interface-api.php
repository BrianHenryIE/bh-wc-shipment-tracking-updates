<?php
/**
 * Defines the main functions the plugin provides.
 *
 * @link       https://BrianHenryIE.com
 * @since      2.0.0
 *
 * @package    brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\Tracking_Details_Abstract;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Includes\CLI;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Action_Scheduler\Scheduler;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Admin_Order_List_Page;
use WC_Order;

/**
 * Implemented by the API class.
 *
 * @see API
 */
interface API_Interface {

	/**
	 * Finds the set of orders whose status indicates they are still in transit
	 * and schedules an update of each.
	 *
	 * @used-by Scheduler::execute()
	 */
	public function start_background_update_jobs(): void;

	/**
	 * The main public function of the plugin. Immediately query the carriers' APIs for tracking updates for the
	 * given list of orders.
	 *
	 * @used-by Scheduler::execute_batch()
	 * @used-by API::find_undispatched_orders()
	 *
	 * @param int[] $order_ids List of WooCommerce order ids to find tracking updates for.
	 * @return array<int|string, array<string, Tracking_Details_Abstract>> array<order_id, array<tracking_number, details>>
	 */
	public function update_orders( array $order_ids ): array;

	/**
	 * Function intended to use via CLI to find are any orders yet to be dispatched.
	 * e.g. when shipping labels get printed but misplaced.
	 *
	 * @used-by CLI::find_undispatched_orders()
	 *
	 * @return array<string, array{order_id: int, tracking_number: string, tracking_details: Tracking_Details_Abstract}>
	 */
	public function find_undispatched_orders(): array;

	/**
	 * Split `packed` orders into buckets of the number of full days passed since they were packed.
	 *
	 * @used-by Admin_Order_List_Page::print_packed_stats()
	 *
	 * @return array<string, array<int>> $order_ids_by_number_of_days_since_packed
	 */
	public function get_order_ids_by_number_of_days_since_packed(): array;

	/**
	 * The main API::update_orders() function only updates orders with changes to their tracking numbers' statuses. This function checks for orders without tracking numbers.
	 * TODO: Check for "assumed delivered" orders -- overseas orders after a certain time, "Rescheduled to Next Delivery Day", "Awaiting Delivery Scan".
	 *
	 * @used-by Scheduler::check_packed_orders()
	 * @used-by CLI::check_packed_orders()
	 *
	 * @return  array{count_packed_orders:int, count_old_packed_orders:int, orders_marked_completed_ids:array<int>, count_orders_without_tracking:int, count_orders_with_unsupported_tracking:int} Stats for CLI output.
	 */
	public function check_packed_orders(): array;

	/**
	 * @param int $order_id
	 *
	 * @return array<string, Tracking_Details_Abstract>
	 */
	public function get_saved_tracking_data_for_order( int $order_id ): array;

	/**
	 * Allow changing the order status to complete without sending the order complete email.
	 * i.e. when orders are stuck in-transit due to USPS status not updating.
	 *
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	public function mark_order_complete_no_email( WC_Order $order ): array;
}
