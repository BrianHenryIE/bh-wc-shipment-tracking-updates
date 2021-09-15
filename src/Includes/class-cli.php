<?php
/**
 * CLI interface to plugin's API.
 *
 * @see \BrianHenryIE\WC_Shipment_Tracking_Updates\API\API_Interface
 *
 * @link       https://BrianHenryIE.com
 * @since      2.0.0
 *
 * @package    BrianHenryIE\WC_Shipment_Tracking_Updates
 * @subpackage BrianHenryIE\WC_Shipment_Tracking_Updates\Includes
 *
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\Includes;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\API_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Settings_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\Tracking_Details_Abstract;
use WP_CLI_Command;


class CLI extends WP_CLI_Command {

	/**
	 * @see Settings::is_configured()
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
	 * Query the tracking information for a single order number
	 *
	 * TODO: This could be a list of 1...n numbers?
	 */
	public function check_tracking_for_order_number( $args ) {

		// $api->
	}

	/**
	 * Query for n orders
	 *
	 * TODO: Starting from offset
	 */
	public function check_tracking_for_last_n_orders( $args ) {

	}

	/**
	 * Should check past 250 "completed" orders and see whose tracking is still at "waiting for pickup".
	 */
	public function find_undelivered_orders() {

	}

	/**
	 * `wp shipment_tracking_updates find_undispatched_orders`
	 */
	public function find_undispatched_orders() {

		\WP_CLI::log( 'Find undispatched orders.' );

		if ( ! $this->settings->is_configured() ) {
			\WP_CLI::log( 'Not configured.' );
			return;
		}

		$unmoved_tracking_details = $this->api->find_undispatched_orders();

		if ( 0 === count( $unmoved_tracking_details ) ) {
			\WP_CLI::log( 'No undispatched orders found.' );
			return;
		}

		\WP_CLI::log( 'order id, order status, tracking number, last updated, carrier status ' );

		/**
		 * @var string $tracking_number
		 * @var Tracking_Details_Abstract $tracking_detail
		 */
		foreach ( $unmoved_tracking_details as $tracking_number => $tracking_detail ) {
			\WP_CLI::log(
				$tracking_detail->get_order_id() . ', '
				. $tracking_detail->get_order_status() . ', '
				. $tracking_detail->get_tracking_number() . ', '
				. $tracking_detail->get_last_updated_time()->format( DATE_ATOM )
				. $tracking_detail->get_carrier_status() // . ', '
			);
		}

	}

	// TODO: Find order for tracking number

}
