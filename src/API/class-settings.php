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

use BrianHenryIE\WC_Shipment_Tracking_Updates\Action_Scheduler\Scheduler;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Includes\BH_WC_Shipment_Tracking_Updates;
use BrianHenryIE\WC_Shipment_Tracking_Updates\USPS\TrackConfirm;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\API\Logger_Settings_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\WooCommerce\WooCommerce_Logger_Interface;
use Psr\Log\LogLevel;

/**
 * Only getters for settings.
 * Some hardcoded, some fetched from wp_options.
 */
class Settings implements Settings_Interface, Logger_Settings_Interface, WooCommerce_Logger_Interface {

	const USPS_USER_ID_OPTION            = 'bh_wc_shipment_tracking_updates_usps_user_id';
	const USPS_SOURCE_ID_OPTION          = 'bh_wc_shipment_tracking_updates_usps_source_id'; // Company name.
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
		return defined( 'BH_WC_SHIPMENT_TRACKING_UPDATES_VERSION' ) ? BH_WC_SHIPMENT_TRACKING_UPDATES_VERSION : '2.1.3';
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
	 * The USPS API user id.
	 *
	 * @see https://registration.shippingapis.com
	 *
	 * @used-by TrackConfirm
	 *
	 * @return ?string
	 */
	public function get_usps_username(): ?string {
		return get_option( self::USPS_USER_ID_OPTION, null );
	}

	/**
	 * USPS requires a company name when requesting extended information (the expected delivery date)
	 * e.g. "company name".
	 *
	 * @see https://www.usps.com/business/web-tools-apis/track-and-confirm-api.htm
	 * @see https://stackoverflow.com/questions/23902091/usps-tracking-api-expected-delivery-date
	 *
	 * @return string|null
	 */
	public function get_usps_source_id(): ?string {
		return get_option( self::USPS_SOURCE_ID_OPTION, null );
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
	 * International orders (USPS) often do not get updated after they change from domestic to international. This
	 * setting allows setting older order, assumed to be delivered, as completed,
	 *
	 * @return int
	 */
	public function get_number_of_days_to_mark_overseas_orders_complete(): int {
		return 30;
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
	 * Check are all required settings configured.
	 *
	 * @used-by Scheduler::register()
	 * @used-by CLI::find_undispatched_orders()
	 *
	 * @return bool
	 */
	public function is_configured(): bool {
		return ! empty( $this->get_usps_username() ) && ! empty( $this->get_usps_source_id() );
	}
}
