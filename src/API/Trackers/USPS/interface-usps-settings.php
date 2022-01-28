<?php
/**
 * Settings that are unique to USPS: USPS user id, source id (company name), time after which to consider orders delivered.
 *
 * @package brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\USPS;

interface USPS_Settings_Interface {

	/**
	 * The USPS API user id.
	 *
	 * @see https://registration.shippingapis.com/
	 *
	 * @used-by TrackConfirm
	 *
	 * @return string
	 */
	public function get_usps_username(): ?string;

	/**
	 * USPS requires a company name when requesting extended information (the expected delivery date)
	 * e.g. "company name".
	 *
	 * @see https://www.usps.com/business/web-tools-apis/track-and-confirm-api.htm
	 * @see https://stackoverflow.com/questions/23902091/usps-tracking-api-expected-delivery-date
	 *
	 * @return string|null
	 */
	public function get_usps_source_id(): ?string;

	/**
	 * USPS does not always track to the destination, often only to the time the package leaves the country.
	 * Because of this, orders will never be marked "complete".
	 *
	 * TODO: Don't send the order complete email with these status changes.
	 * TODO: Alternatively, send the order complete email when the package leaves the US, with an appropriate message.
	 *
	 * @return int
	 */
	public function get_number_of_days_to_mark_overseas_orders_complete(): int;

}
