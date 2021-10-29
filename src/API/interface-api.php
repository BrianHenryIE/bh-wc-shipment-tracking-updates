<?php
/**
 * Defines the main functions the plugin provides.
 *
 * @link       https://BrianHenryIE.com
 * @since      2.0.0
 *
 * @package    brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\Tracking_Details_Abstract;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Includes\CLI;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Action_Scheduler\Scheduler;

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

}
