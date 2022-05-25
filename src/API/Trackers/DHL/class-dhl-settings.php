<?php
/**
 *
 *
 * @package     brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\DHL;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\Tracker_Settings_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Container;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Shipping_Settings_Page;

/**
 * Facade using get_option() over settings saved via WooCommerce Settings API.
 *
 * @see Shipping_Settings_Page
 */
class DHL_Settings implements DHL_Settings_Interface, Tracker_Settings_Interface {

	const DHL_CONSUMER_API_KEY_OPTION_NAME = 'bh_wc_shipment_tracking_updates_dhl_consumer_api_key';

	/**
	 * Return the value saved for the DHL API key.
	 *
	 * @see Shipping_Settings_Page::shipment_tracking_updates_settings()
	 */
	public function get_consumer_api_key(): ?string {
		$value = get_option( self::DHL_CONSUMER_API_KEY_OPTION_NAME, null );
		// An empty string returns an empty string rather than null.
		if ( empty( $value ) ) {
			return null;
		}
		return $value;
	}

	/**
	 * Check are all required settings present before instantiating the tracker.
	 *
	 * @used-by Container
	 */
	public function is_configured(): bool {
		return ! is_null( $this->get_consumer_api_key() );
	}
}
