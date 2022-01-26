<?php
/**
 * Every tracker's settings should have a function to report if it is fully configured and ready to use.
 *
 * @package     brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers;

interface Tracker_Settings_Interface {

	/**
	 * Check are all required settings configured.
	 *
	 * @used-by Scheduler::register()
	 * @used-by CLI::find_undispatched_orders()
	 *
	 * @return bool
	 */
	public function is_configured(): bool;

}
