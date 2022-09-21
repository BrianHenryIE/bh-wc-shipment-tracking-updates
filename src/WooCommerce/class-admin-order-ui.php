<?php

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API_Interface;
use WC_Order;

class Admin_Order_UI {

	protected API_Interface $api;

	public function __construct( API_Interface $api ) {
		$this->api = $api;
	}

	/**
	 * Add "Mark completed – no email" to order actions in admin UI order edit page.
	 *
	 * @hooked woocommerce_order_actions
	 * @see class-wc-meta-box-order-actions.php
	 *
	 * @param array<string,string> $actions
	 * @return array<string,string>
	 */
	public function add_admin_ui_order_action( $actions ): array {

		$actions['bh_wc_shipment_tracking_updates_mark_completed'] = __( 'Mark completed – no email', 'bh-wc-shipment-tracking-updates' );

		return $actions;
	}

	/**
	 * Handle the action.
	 *
	 * @hooked woocommerce_order_action_bh_wc_shipment_tracking_updates_mark_completed
	 *
	 * @param WC_Order $order
	 */
	public function handle_mark_order_complete_action( $order ): void {

		$result = $this->api->mark_order_complete_no_email( $order );

		// TODO: Add admin notice.
	}

}
