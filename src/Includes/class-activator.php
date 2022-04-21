<?php
/**
 * Fired during plugin activation.
 *
 * Checks for USPS username from other plugins to pre-fill the settings.
 *
 * @link       https://BrianHenryIE.com
 * @since      2.0.0
 *
 * @package           brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\Includes;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Settings;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\USPS\USPS_Settings;

/**
 * Checks wp_options for USPS username saved by other plugins.
 */
class Activator {

	/**
	 * If we're not already configured, try to find a USPS username.
	 *
	 * @since    1.0.0
	 */
	public static function activate(): void {

		if ( empty( get_option( USPS_Settings::USPS_USER_ID_OPTION ) ) ) {
			self::find_usps_username();
		}

	}

	/**
	 * Check the settings of other plugins for the USPS username.
	 */
	protected static function find_usps_username(): void {

		$option_names = array(
			'bh-wc-address-validation-usps-username',
			'bh_wc_address_validation_usps_username',
			'usps_id', // @see https://wordpress.org/plugins/woocommerce-usps-address-verification/
		);

		foreach ( $option_names as $option_name ) {
			$usps_user_id = get_option( $option_name );
			if ( ! empty( $usps_user_id ) ) {
				update_option( USPS_Settings::USPS_USER_ID_OPTION, $usps_user_id );
				break;
			}
		}

	}

}
