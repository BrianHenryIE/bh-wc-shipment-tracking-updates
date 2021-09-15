<?php
/**
 * The main functions the plugin provides.
 *
 * @link       https://BrianHenryIE.com
 * @since      2.0.0
 *
 * @package    BrianHenryIE\WC_Shipment_Tracking_Updates
 * @subpackage BrianHenryIE\WC_Shipment_Tracking_Updates/admin
 */


namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API;

use BrianHenryIE\WC_Shipment_Tracking_Updates\Includes\CLI;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Action_Scheduler\Scheduler;

interface API_Interface {

	/**
	 * Finds the set of orders whose status indicates they are still in transit
	 * and schedules an update of each.
	 *
	 * @used-by Scheduler::execute()
	 */
	public function start_background_update_jobs(): void;

	/**
	 * @used-by Scheduler::execute_batch()
	 *
	 * @param int[] $order_ids
	 */
	public function update_orders( array $order_ids ): array;

	/**
	 * @used-by CLI::find_undispatched_orders()
	 */
	public function find_undispatched_orders(): array;

}
