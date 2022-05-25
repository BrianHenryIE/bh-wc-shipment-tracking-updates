<?php
/**
 * Settings object that fetches settings from wp_options, as saved by the WooCommerce Settings API.
 *
 * @see \BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Shipping_Settings_Page::shipment_tracking_updates_settings()
 *
 * @package     brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\UPS;

/**
 * Facade using get_option() over settings saved via WooCommerce Settings API.
 *
 * @see Shipping_Settings_Page
 */
class UPS_Settings implements UPS_Settings_Interface {

	const UPS_USER_ID_OPTION_NAME    = 'bh_wc_shipment_tracking_updates_ups_user_id';
	const UPS_PASSWORD_OPTION_NAME   = 'bh_wc_shipment_tracking_updates_ups_password';
	const UPS_ACCESS_KEY_OPTION_NAME = 'bh_wc_shipment_tracking_updates_ups_access_key';

	/**
	 * Check are all required settings present before instantiating the tracker.
	 *
	 * @used-by Container
	 */
	public function is_configured(): bool {
		return ! ( empty( $this->get_user_id() ) || empty( $this->get_password() ) || empty( $this->get_access_key() ) );
	}

	/**
	 * User id for logging into UPS.com. (TODO: check does email work interchangeably when accessing the API).
	 *
	 * @return ?string
	 */
	public function get_user_id(): ?string {
		return get_option( self::UPS_USER_ID_OPTION_NAME, null );
	}

	/**
	 * UPS website password for the above user id.
	 *
	 * @return ?string
	 */
	public function get_password(): ?string {
		return get_option( self::UPS_PASSWORD_OPTION_NAME, null );
	}

	/**
	 * API access key.
	 *
	 * @return ?string
	 */
	public function get_access_key(): ?string {
		return get_option( self::UPS_ACCESS_KEY_OPTION_NAME, null );
	}
}
