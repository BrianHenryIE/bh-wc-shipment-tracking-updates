<?php
/**
 * Settings required for the plugin to function.
 *
 * @package    brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API;

use BrianHenryIE\WC_Shipment_Tracking_Updates\Action_Scheduler\Scheduler;

interface Settings_Interface {

	/**
	 * The plugin slug, used in settings page name, css+scripts handles.
	 *
	 * @return string
	 */
	public function get_plugin_slug(): string;

	/**
	 * Used when adding Settings link on plugins.php.
	 *
	 * @used-by BH_WC_Shipment_Tracking_Updates::define_plugins_page_hooks()
	 *
	 * @return string
	 */
	public function get_plugin_basename(): string;

	/**
	 * Plugin version in semver ("major.minor.patch"), used when enqueuing CSS and JS.
	 *
	 * @see https://semver.org/
	 *
	 * @return string
	 */
	public function get_plugin_version(): string;

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

	/**
	 * List of order statuses considered to be dispatched but not yet delivered.
	 *
	 * @used-by API::find_orders_to_update()
	 *
	 * @return string[]
	 */
	public function get_order_statuses_to_watch(): array;

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
