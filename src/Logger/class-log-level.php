<?php
/**
 * HTTP library was logging everything as info rather than debug.
 *
 * @package    brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\Logger;

use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\API\BH_WP_PSR_Logger;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\Logger_Settings_Interface;
use Psr\Log\LogLevel;

/**
 * Hooks into `BH_WP_PSR_Logger::log()`'s filter to manipulate the logs.
 */
class Log_Level {

	/**
	 * The library php-http/logger-plugin is using INFO for detailed logging, let's change that to DEBUG.
	 *
	 * @see \BrianHenryIE\WC_Shipment_Tracking_Updates\Http\Client\Common\Plugin\LoggerPlugin::doHandleRequest()
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
	public function info_to_debug( array $log_data, Logger_Settings_Interface $settings, BH_WP_PSR_Logger $bh_wp_psr_logger ): array {

		if ( LogLevel::INFO !== $log_data['level'] ) {
			return $log_data;
		}

		if ( 0 === strpos( $log_data['message'], 'Received response' ) ) {
			$log_data['level'] = LogLevel::DEBUG;
		}

		if ( 0 === strpos( $log_data['message'], 'Sending request' ) ) {
			$log_data['level'] = LogLevel::DEBUG;
		}

		return $log_data;
	}

}
