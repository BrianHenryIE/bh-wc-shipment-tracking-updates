<?php

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers;

interface Tracker_Interface {

	/**
	 * @param string $tracking_number
	 *
	 * @return Tracking_Details_Abstract
	 */
	public function query_single_tracking_number( string $tracking_number ): Tracking_Details_Abstract;

	/**
	 * @param string[] $tracking_numbers
	 *
	 * @return Tracking_Details_Abstract[]
	 */
	public function query_multiple_tracking_numbers( array $tracking_numbers ): array;

}
