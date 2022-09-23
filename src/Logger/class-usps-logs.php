<?php
/**
 * Hide logs from USPS that we know aren't important.
 *
 * @package brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\Logger;

use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\API\BH_WP_PSR_Logger;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\Logger_Settings_Interface;

/**
 * Hooks into `BH_WP_PSR_Logger::log()`'s filter to manipulate the logs.
 */
class USPS_Logs {

	/**
	 * Some USPS "error" responses aren't really errors, others occur so often they can be safely ignored.
	 *
	 * @hooked bh-wc-shipment-tracking-updates_bh_wp_logger_log
	 * @see BH_WP_PSR_Logger::log()
	 *
	 * @param array{level:string,message:string,context:array} $log_data Array of log data that will be used, return null to cancel logging.
	 * @param Logger_Settings_Interface                        $settings The logger settings.
	 * @param BH_WP_PSR_Logger                                 $bh_wp_psr_logger The logger.
	 *
	 * @return array{level:string,message:string,context:array}
	 */
	public function mute_errors( array $log_data, Logger_Settings_Interface $settings, BH_WP_PSR_Logger $bh_wp_psr_logger ): ?array {

		$acceptable_errors = array(
			'A status update is not yet available',
			'An unexpected system error has occurred.',
			'An error has occurred with the service',
		);

		$context = $log_data['context'];

		if ( ! isset( $context['details'] ) ) {
			return $log_data;
		}

		if ( ! isset( $context['details']['Error'] ) || ! isset( $context['details']['Error']['Description'] ) ) {
			return $log_data;
		}

		$error_description = $context['details']['Error']['Description'];

		foreach ( $acceptable_errors as $acceptable_error ) {
			if ( 0 === strpos( $error_description, $acceptable_error ) ) {
				return null;
			}
		}

		return $log_data;

	}

}
