<?php
/**
 * Credentials required for the UPS API.
 *
 * Request credentials at https://www.ups.com/upsdeveloperkit
 *
 * @package     brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\UPS;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\Tracker_Settings_Interface;

/**
 * Basic settings required to access the UPS API.
 *
 * @used-by UPS_Tracker
 */
interface UPS_Settings_Interface extends Tracker_Settings_Interface {

	/**
	 * The username used to log into the UPS site.
	 */
	public function get_user_id(): ?string;

	/**
	 * The password used to log into the UPS site.
	 */
	public function get_password(): ?string;

	/**
	 * UPS API access key.
	 *
	 * @see https://www.ups.com/ca/en/help-center/sri/apiaccesskey.page
	 */
	public function get_access_key(): ?string;
}
