<?php
/**
 * CLI interface to plugin's API.
 *
 * @see \BrianHenryIE\WC_Shipment_Tracking_Updates\API_Interface
 *
 * @link       https://BrianHenryIE.com
 * @since      2.0.0
 *
 * @package    BrianHenryIE\WC_Shipment_Tracking_Updates
 * @subpackage BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Includes
 *
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Includes;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Settings_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\Tracking_Details_Abstract;
use WP_CLI;
use WP_CLI_Command;


class CLI extends WP_CLI_Command {

	/**
	 *
	 * @var Settings_Interface
	 */
	protected Settings_Interface $settings;

	/**
	 * All CLI functions call into an instance of the API_Interface.
	 *
	 * @var API_Interface $api The main plugin API definition.
	 */
	protected API_Interface $api;

	/**
	 * @param API_Interface      $api The main plugin functions.
	 * @param Settings_Interface $settings The plugin's settings.
	 */
	public function __construct( API_Interface $api, Settings_Interface $settings ) {
		parent::__construct();
		$this->settings = $settings;
		$this->api      = $api;
	}

	/**
	 * Query the tracking information for a single order or list of orders.
	 *
	 * `wp shipment_tracking_updates check_order_ids 1230 4506 7089`
	 *
	 * `wp shipment_tracking_updates check_order_ids 203907 --debug=bh-wc-shipment-tracking-updates`
	 *
	 * Use in conjunction with regular cli functions:
	 * `wp shipment_tracking_updates check_order_ids $(wp post list --post_type=shop_order --post_status=wc-processing --posts_per_page=10 --paged=1 --format=ids) --debug=bh-wc-shipment-tracking-updates`
	 *
	 * @param string[] $args
	 */
	public function check_tracking_for_order_numbers( array $args ): void {

		$valid_order_ids = array();

		foreach ( $args as $arg ) {
			$order_id = intval( $arg );
			$order    = wc_get_order( $order_id );
			if ( $order instanceof \WC_Order ) {
				$valid_order_ids[] = $order_id;
			}
		}

		$result = $this->api->update_orders( $valid_order_ids );
	}

	/**
	 * `wp shipment_tracking_updates find_undispatched_orders`
	 */
	public function find_undispatched_orders(): void {

		WP_CLI::log( 'Find undispatched orders.' );

		$unmoved_tracking_details = $this->api->find_undispatched_orders();

		if ( 0 === count( $unmoved_tracking_details ) ) {
			WP_CLI::log( 'No undispatched orders found.' );
			return;
		}

		WP_CLI::log( 'order id, tracking number, equivalent status, last updated, carrier status ' );

		foreach ( $unmoved_tracking_details as $tracking_number => $tracking_array ) {
			$tracking_details            = $tracking_array['tracking_details'];
			$order_id                    = $tracking_array['order_id'];
			$last_updated_time           = $tracking_details->get_last_updated_time();
			$last_updated_time_formatted = is_null( $last_updated_time ) ? '' : $last_updated_time->format( DATE_ATOM );
			WP_CLI::log(
				$order_id . ','
				. $tracking_details->get_tracking_number() . ', '
				. $tracking_details->get_equivalent_order_status() . ', '
				. $last_updated_time_formatted
				. $tracking_details->get_carrier_status() // . ', '
			);
		}

	}

	/**
	 * `wp shipment_tracking_updates check_packed_orders --debug=bh-wc-shipment-tracking-updates`
	 *
	 * @since 2.2.0
	 */
	public function check_packed_orders(): void {

		$result = $this->api->check_packed_orders();

		$result_formatted = wp_json_encode( $result, JSON_PRETTY_PRINT );
		$result_formatted = $result_formatted ? $result_formatted : '';

		WP_CLI::log( $result_formatted );

	}

	/**
	 * `wp shipment_tracking_updates mark_order_complete 123`
	 *
	 * @param string[] $args
	 *
	 * @since 2.10.0
	 */
	public function mark_order_complete( array $args ): void {

		$order_id = intval( $args[0] );

		$order = wc_get_order( $order_id );

		$result = $this->api->mark_order_complete_no_email( $order );

	}

}
