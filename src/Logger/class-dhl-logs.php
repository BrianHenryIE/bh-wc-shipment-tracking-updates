<?php
/**
 * Some DHL logs add a JSON object to the message. This moves it to the log context.
 *
 * TODO: This affects all logs which end in a JSON object, so should be renamed.
 *
 * @package    brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\Logger;

use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\API\BH_WP_PSR_Logger;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\Logger_Settings_Interface;

/**
 * Hooks into `BH_WP_PSR_Logger::log()`'s filter to manipulate the logs.
 */
class DHL_Logs {

	/**
	 * The DHL library adds a JSON object to the message. Let's move that to the context.
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
	public function add_message_json_to_context( array $log_data, Logger_Settings_Interface $settings, BH_WP_PSR_Logger $bh_wp_psr_logger ): array {

		$message           = $log_data['message'];
		$message_array     = explode( "\n", $message );
		$last_message_line = array_pop( $message_array );
		$message_context   = json_decode( $last_message_line );
		if ( ! is_null( $message_context ) ) {

			$log_data['context']['message_context'] = $message_context;
			$log_data['message']                    = implode( "\n", $message_array );
		}

		return $log_data;

	}
}
