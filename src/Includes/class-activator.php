<?php
/**
 * Fired during plugin activation
 *
 * @link       https://BrianHenryIE.com
 * @since      2.0.0
 *
 * @package    BrianHenryIE\WC_Shipment_Tracking_Updates
 * @subpackage BrianHenryIE\WC_Shipment_Tracking_Updates/includes
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\Includes;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Settings;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    BrianHenryIE\WC_Shipment_Tracking_Updates
 * @subpackage BrianHenryIE\WC_Shipment_Tracking_Updates/includes
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */
class Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		self::find_usps_username();

		// TODO: Check the last few weeks' orders... including "completed"?
		// The scheduler will already start an update immediately.
		if ( ! empty( get_option( Settings::USPS_USER_ID_OPTION ) ) ) {

		}

	}

	/**
	 * Check the settings of other plugins for the USPS username.
	 */
	protected static function find_usps_username() {

		// Look for USPS API key in other plugins.
		if ( ! empty( get_option( Settings::USPS_USER_ID_OPTION ) ) ) {
			return;
		}

		$option_names = array(
			'bh-wc-address-validation-usps-username',
			'usps_id', // @see https://wordpress.org/plugins/woocommerce-usps-address-verification/
		);

		foreach ( $option_names as $option_name ) {
			$usps_user_id = get_option( $option_name );
			if ( ! empty( $usps_user_id ) ) {
				update_option( Settings::USPS_USER_ID_OPTION, $usps_user_id );
				break;
			}
		}

	}

}
