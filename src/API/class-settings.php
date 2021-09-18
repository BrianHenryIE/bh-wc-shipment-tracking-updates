<?php
/**
 * A plain old object as a facade over the WordPress/WooCommerce settings.
 *
 * @link       https://BrianHenryIE.com
 * @since      2.0.0
 *
 * @package    BrianHenryIE\WC_Shipment_Tracking_Updates
 * @subpackage BrianHenryIE\WC_Shipment_Tracking_Updates/admin
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API;

use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\API\Logger_Settings_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\WooCommerce\WooCommerce_Logger_Interface;
use Psr\Log\LogLevel;

class Settings implements Settings_Interface, Logger_Settings_Interface, WooCommerce_Logger_Interface {

	const USPS_USER_ID_OPTION            = 'bh_wc_shipment_tracking_updates_usps_user_id';
	const USPS_SOURCE_ID_OPTION          = 'bh_wc_shipment_tracking_updates_usps_source_id'; // Company name.
	const LOG_LEVEL_OPTION               = 'bh_wc_shipment_tracking_updates_log_level';
	const ORDER_STATUSES_TO_WATCH_OPTION = 'bh_wc_shipment_tracking_updates_order_statuses_to_watch';

	public function get_plugin_name(): string {
		return 'Shipment Tracking Updates';
	}

	/**
	 *
	 * @return string
	 */
	public function get_plugin_version(): string {
		return '2.0.4';
	}

	public function get_plugin_slug(): string {
		return 'bh-wc-shipment-tracking-updates';
	}

	/**
	 * @return string
	 */
	public function get_log_level(): string {
		return get_option( self::LOG_LEVEL_OPTION, LogLevel::INFO );
	}

	public function get_usps_username(): ?string {
		return get_option( self::USPS_USER_ID_OPTION, null );
	}

	/**
	 * "company name"
	 *
	 * @return string|null
	 */
	public function get_usps_source_id(): ?string {
		return get_option( self::USPS_SOURCE_ID_OPTION, null );
	}

	public function get_order_statuses_to_watch(): array {
		$default_statuses = array( 'shippingpurchased', 'printed', 'packing', 'packed', 'in-transit', 'returning' );
		return get_option( self::ORDER_STATUSES_TO_WATCH_OPTION, $default_statuses );
	}

	public function get_number_of_days_to_mark_overseas_orders_complete(): int {
		return 30;
	}

	public function get_plugin_basename(): string {
		return 'bh-wc-shipment-tracking-updates/bh-wc-shipment-tracking-updates.php';
	}

	public function is_configured() {
		return ! empty( $this->get_usps_username() ) && ! empty( $this->get_usps_source_id() );
	}
}
