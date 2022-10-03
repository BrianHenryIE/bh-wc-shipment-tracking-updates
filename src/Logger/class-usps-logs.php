<?php
/**
 * Hide logs from USPS that we know aren't important.
 *
 * @package brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\Logger;

use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\API\BH_WP_PSR_Logger;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\Logger_Settings_Interface;
use Psr\Log\LogLevel;

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

		if ( isset( $context['array_response'], $context['array_response']['Error'], $context['array_response']['Error']['Description'] ) ) {
			$error_message = $context['array_response']['Error']['Description'];

		} elseif ( isset( $context['details'], $context['details']['Error'], $context['details']['Error']['Description'] ) ) {
			$error_message = $context['details']['Error']['Description'];

		} else {
			return $log_data;
		}

		foreach ( $acceptable_errors as $acceptable_error ) {
			if ( 0 === strpos( $error_message, $acceptable_error ) ) {
				$log_data['level']   = LogLevel::DEBUG;
				$log_data['message'] = 'Muted error: ' . $log_data['message'];
				return $log_data;
			}
		}

		return $log_data;
	}

}
