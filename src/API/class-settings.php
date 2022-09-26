<?php
/**
 * A plain old object as a facade over the WordPress/WooCommerce settings.
 *
 * @link       https://BrianHenryIE.com
 * @since      2.0.0
 *
 * @package    brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\Tracker_Settings_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\USPS\USPS_Settings;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Settings_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\Logger_Settings_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\Logger_Settings_Trait;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\WooCommerce_Logger_Settings_Interface;
use Psr\Log\LogLevel;

/**
 * Only getters for settings.
 * Some hardcoded, some fetched from wp_options.
 */
class Settings implements Settings_Interface, WooCommerce_Logger_Settings_Interface {
	use Logger_Settings_Trait;

	const LOG_LEVEL_OPTION               = 'bh_wc_shipment_tracking_updates_log_level';
	const ORDER_STATUSES_TO_WATCH_OPTION = 'bh_wc_shipment_tracking_updates_order_statuses_to_watch';

	/**
	 * The plugin name, used by the logger.
	 *
	 * @see Logger_Settings_Interface
	 *
	 * @return string
	 */
	public function get_plugin_name(): string {
		return 'Shipment Tracking Updates';
	}

	/**
	 * The plugin version, used when enqueuing CSS and JS.
	 *
	 * @return string
	 */
	public function get_plugin_version(): string {
		return defined( 'BH_WC_SHIPMENT_TRACKING_UPDATES_VERSION' ) ? BH_WC_SHIPMENT_TRACKING_UPDATES_VERSION : '2.10.3';
	}

	/**
	 * The plugin slug, used in settings page name, css+scripts handles.
	 *
	 * @see Logger_Settings_Interface
	 *
	 * @return string
	 */
	public function get_plugin_slug(): string {
		return 'bh-wc-shipment-tracking-updates';
	}

	/**
	 * What detail of logs should the PSR logger record?
	 *
	 * @see Logger_Settings_Interface
	 *
	 * @return string
	 */
	public function get_log_level(): string {
		return get_option( self::LOG_LEVEL_OPTION, LogLevel::INFO );
	}

	/**
	 * List of order statuses considered to be dispatched but not yet delivered.
	 *
	 * @used-by API::find_orders_to_update()
	 *
	 * @return string[]
	 */
	public function get_order_statuses_to_watch(): array {
		$default_statuses = array( 'shippingpurchased', 'printed', 'packing', 'packed', 'in-transit', 'returning' );
		return get_option( self::ORDER_STATUSES_TO_WATCH_OPTION, $default_statuses );
	}

	/**
	 * Used by the logger to match errors to this plugin.
	 *
	 * @see Logger_Settings_Interface
	 * @used-by BH_WC_Shipment_Tracking_Updates::define_plugins_page_hooks()
	 *
	 * @return string
	 */
	public function get_plugin_basename(): string {
		return defined( 'BH_WC_SHIPMENT_TRACKING_UPDATES_BASENAME' ) ? BH_WC_SHIPMENT_TRACKING_UPDATES_BASENAME : 'bh-wc-shipment-tracking-updates/bh-wc-shipment-tracking-updates.php';
	}


	/**
	 * Get the saved settings for a particular carrier.
	 *
	 * @see \WC_Shipment_Tracking_Actions::get_providers()
	 *
	 * @param string $provider The tracking provider identifier.
	 *
	 * @return ?Tracker_Settings_Interface
	 */
	public function get_tracker_settings( string $provider ): ?Tracker_Settings_Interface {

		switch ( $provider ) {
			case 'USPS':
				return new USPS_Settings();
			default:
				return null;
		}
	}
}
