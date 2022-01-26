<?php
/**
 * Every tracker should have functions to query single or multiple tracking numbers.
 *
 * @package     brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers;

interface Tracker_Interface {

	/**
	 * Synchronously query the tracking API for a single tracking number.
	 *
	 * @param string $tracking_number The tracking number to query.
	 *
	 * @return Tracking_Details_Abstract
	 */
	public function query_single_tracking_number( string $tracking_number ): Tracking_Details_Abstract;

	/**
	 * Synchronously query the tracking API for a set of tracking numbers.
	 *
	 * @param string[] $tracking_numbers The tracking numbers to query.
	 *
	 * @return Tracking_Details_Abstract[]
	 */
	public function query_multiple_tracking_numbers( array $tracking_numbers ): array;

}
