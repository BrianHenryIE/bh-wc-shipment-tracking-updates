<?php
/**
 * Settings required for the plugin to function.
 *
 * @package    brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API;

use BrianHenryIE\WC_Shipment_Tracking_Updates\Action_Scheduler\Scheduler;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\Tracker_Settings_Interface;

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
	 * List of order statuses considered to be dispatched but not yet delivered.
	 *
	 * @used-by API::find_orders_to_update()
	 *
	 * @return string[]
	 */
	public function get_order_statuses_to_watch(): array;

	/**
	 * Get the saved settings for a particular carrier.
	 *
	 * @see \WC_Shipment_Tracking_Actions::get_providers()
	 *
	 * @param string $provider The tracking provider identifier.
	 *
	 * @return ?Tracker_Settings_Interface
	 */
	public function get_tracker_settings( string $provider ): ?Tracker_Settings_Interface;
}
