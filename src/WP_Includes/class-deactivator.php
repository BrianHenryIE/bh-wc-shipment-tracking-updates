<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://BrianHenryIE.com
 * @since      2.0.0
 *
 * @package    brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Includes;

use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Order_Statuses;

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
	 * If WooCommerce is active, query for all orders using one of this plugin's custom statuses, change them to
	 * `completed` and add an order note and meta data dating the change.
	 *
	 * For orders whose status is "returning", disable sending the "order complete" email.
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
				),
				'paginate' => false,
			)
		);

		foreach ( $orders as $order ) {
			$existing_status = $order->get_status();
			$order_note      = "Changed from {$existing_status} on deactivation of Shipment Tracking Updates plugin.\n";
			$order->set_status( 'completed', $order_note );
			$order->add_meta_data( self::DEACTIVATED_ORDER_STATUS_CHANGED_META_KEY, array( gmdate( DATE_ATOM ), $existing_status ), true );
			$order->save();
		}

		// We need to instantiate the WC_Emails class which then adds the action we want to remove,
		// otherwise it is not instantiated until after set_status is called. It is probably instantiated
		// due to the orders above, but maybe there were zero.
		\WC_Emails::instance();

		add_filter( 'woocommerce_email_enabled_customer_completed_order', '__return_false' );

		$returning_orders = (array) wc_get_orders(
			array(
				'limit'    => -1,
				'status'   => array(
					'wc-' . Order_Statuses::RETURNING_WC_STATUS,
				),
				'paginate' => false,
			)
		);

		foreach ( $returning_orders as $order ) {
			$existing_status = $order->get_status();
			$order_note      = "Changed from {$existing_status} on deactivation of Shipment Tracking Updates plugin.\n";
			$order->set_status( 'completed', $order_note );
			$order->add_meta_data( self::DEACTIVATED_ORDER_STATUS_CHANGED_META_KEY, array( gmdate( DATE_ATOM ), $existing_status ), true );
			$order->save();
		}

		remove_filter( 'woocommerce_email_enabled_customer_completed_order', '__return_false' );
	}

	/**
	 * TODO: Unregister actions with action scheduler.
	 */
	protected static function unregister_action_scheduler_actions(): void {

	}

}
