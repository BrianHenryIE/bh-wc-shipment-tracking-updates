<?php
/**
 * Replace mentions of order ids in log messages with links to the orders
 *
 * @package brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\Logger;

use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\API\BH_WP_PSR_Logger;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\Logger_Settings_Interface;

/**
 * Filters BH_WP_PSR_Logger.
 */
class WooCommerce_Logs {

	/**
	 * Update `wc_order:123` with links to the order.
	 *
	 * @hooked bh-wc-shipment-tracking-updates_bh_wp_logger_column
	 *
	 * @param string                                                          $column_output The column output so far.
	 * @param array{time:string, level:string, message:string, context:array} $item The log entry row.
	 * @param string                                                          $column_name The current column name.
	 * @param Logger_Settings_Interface                                       $logger_settings The logger settings.
	 * @param BH_WP_PSR_Logger                                                $bh_wp_psr_logger The logger API instance.
	 *
	 * @return string
	 */
	public function replace_wc_order_id_with_link( string $column_output, array $item, string $column_name, Logger_Settings_Interface $logger_settings, BH_WP_PSR_Logger $bh_wp_psr_logger ): string {

		if ( 'message' !== $column_name ) {
			return $column_output;
		}

		$callback = function( array $matches ): string {

			$url  = admin_url( "post.php?post={$matches[1]}&action=edit" );
			$link = "<a href=\"{$url}\">Order {$matches[1]}</a>";

			return $link;
		};

		$message = preg_replace_callback( '/wc_order:(\d+)/', $callback, $column_output ) ?? $column_output;

		return $message;
	}

}
