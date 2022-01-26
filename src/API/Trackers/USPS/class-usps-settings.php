<?php

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\USPS;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\Tracker_Settings_Interface;

class USPS_Settings implements USPS_Settings_Interface, Tracker_Settings_Interface {

	const USPS_USER_ID_OPTION   = 'bh_wc_shipment_tracking_updates_usps_user_id';
	const USPS_SOURCE_ID_OPTION = 'bh_wc_shipment_tracking_updates_usps_source_id'; // Company name.

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
	 * International orders (USPS) often do not get updated after they change from domestic to international. This
	 * setting allows setting older order, assumed to be delivered, as completed,
	 *
	 * @return int
	 */
	public function get_number_of_days_to_mark_overseas_orders_complete(): int {
		return 30;
	}

}
