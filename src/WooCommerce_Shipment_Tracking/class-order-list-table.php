<?php
/**
 * Adds the last tracking update after the tracking number on the orders admin list page.
 *
 * TODO: Currently only does one tracking number. Multiple are possible.
 *
 * @link       https://BrianHenryIE.com
 * @since      2.0.0
 *
 * @package    BrianHenryIE\WC_Shipment_Tracking_Updates
 *
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce_Shipment_Tracking;

class Order_List_Table {

	/**
	 * @hooked woocommerce_shipment_tracking_get_shipment_tracking_column
	 *
	 * @param string $html The existing HTML being output.
	 * @param int    $order_id The WooCommerce order id.
	 * @param array  $tracking_items
	 */
	public function append_tracking_detail_to_column( $html, $order_id, $tracking_items ) {

		// %s</a></li>

		// wc_shipment_tracking()->actions->

		/** @var \WC_Order $order */
		$order = wc_get_order( $order_id );

		$update = $order->get_meta( 'bh_wc_shipment_tracking_updates' );

		if ( ! empty( $update ) ) {

			$html = str_replace( '</li>', "<br/>$update</li>", $html );

		}

		return $html;
	}

}
