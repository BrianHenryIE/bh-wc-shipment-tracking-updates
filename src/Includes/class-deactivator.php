<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://BrianHenryIE.com
 * @since      2.0.0
 *
 * @package    brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\Includes;

use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Order_Statuses;
use WC_Order;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    BrianHenryIE\WC_Shipment_Tracking_Updates
 * @subpackage BrianHenryIE\WC_Shipment_Tracking_Updates/includes
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */
class Deactivator {

	const DEACTIVATED_ORDER_STATUS_CHANGED_META_KEY = 'bh_wc_shipment_tracking_updates_deactivated_status_changed';

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate(): void {

		self::change_order_statuses();

		self::unregister_action_scheduler_actions();
	}

	/**
	 * If WooCommerce is active, query for all orders using on of this plugin's custom statuses, change them to
	 * `completed` and add an order note and meta data dating the change.
	 */
	protected static function change_order_statuses(): void {

		// If WooCommerce were deactivated first, this would otherwise cause a fatal error.
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return;
		}

		$orders = (array) wc_get_orders(
			array(
				'limit'    => -1,
				'status'   => array(
					'wc-' . Order_Statuses::PACKING_COMPLETE_WC_STATUS,
					'wc-' . Order_Statuses::IN_TRANSIT_WC_STATUS,
					'wc-' . Order_Statuses::RETURNING_WC_STATUS,
				),
				'paginate' => false,
			)
		);

		foreach ( $orders as $order ) {
			$existing_status = $order->get_status();
			$order_note      = "Changed from {$existing_status} on plugin deactivation.";
			$order->set_status( 'completed', $order_note );
			$order->add_meta_data( self::DEACTIVATED_ORDER_STATUS_CHANGED_META_KEY, array( gmdate( DATE_ATOM ), $existing_status ), true );
			$order->save();
		}
	}

	/**
	 * TODO: Unregister actions with action scheduler.
	 */
	protected static function unregister_action_scheduler_actions(): void {

	}

}
