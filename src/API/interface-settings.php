<?php


namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API;

use BrianHenryIE\WC_Shipment_Tracking_Updates\Action_Scheduler\Scheduler;

interface Settings_Interface {

	public function get_plugin_slug(): string;

	/**
	 * x.x.x
	 *
	 * @see https://semver.org/
	 *
	 * @return string
	 */
	public function get_plugin_version(): string;

	/**
	 *
	 * @see https://registration.shippingapis.com/
	 *
	 * @return string
	 */
	public function get_usps_username(): ?string;

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

	/**
	 *
	 * @used-by API::find_orders_to_update()
	 * @return string[]
	 */
	public function get_order_statuses_to_watch(): array;

	/**
	 * @used-by Scheduler::register()
	 * @return mixed
	 */
	public function is_configured();

}
