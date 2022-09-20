<?php
/**
 * User facing changes.
 *
 * Add a button allowing customers to mark their order complete manually. (because USPS doesn't always update)
 *
 * @package brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce;

use WC_Order;

class My_Account {

	/**
	 * Add a button on each order in the orders list which allows the customer to mark the order completed.
	 *
	 * @hooked woocommerce_my_account_my_orders_actions
	 * @see woocommerce/includes/wc-account-functions.php
	 *
	 * @param array<string, array{url:string, name:string}> $actions The actions already added to this row.
	 * @param WC_Order                                      $order The WooCommerce order for this row.
	 *
	 * @return array<string, array{url:string, name:string}>
	 */
	public function add_button( array $actions, WC_Order $order ): array {

		if ( ! in_array( $order->get_status(), wc_get_is_paid_statuses(), true ) ) {
			return $actions;
		}

		$irrelevant_statuses = array(
			'completed',
			'cancelled',
			'refunded',
			'failed',
		);

		if ( in_array( $order->get_status(), $irrelevant_statuses, true ) ) {
			return $actions;
		}

		// Basically, here we're at 'processing' and custom statuses, 'packed', 'in-transit', and other plugins' custom statuses.

		// The current page.
		$url = wc_get_endpoint_url( 'orders', '', wc_get_page_permalink( 'myaccount' ) );

		$url = add_query_arg(
			array(
				'action'   => 'mark-completed',
				'order_id' => $order->get_id(),
			),
			$url
		);

		$url = wp_nonce_url( $url, 'mark-completed' );

		$actions['mark-completed'] = array(
			'url'  => $url,
			'name' => __( 'Mark Completed', 'bh-wc-shipment-tracking-updates' ),
		);

		return $actions;

	}

	/**
	 * Change the order's status to completed.
	 *
	 * @hooked init
	 *
	 * TODO: Also handle this change via AJAX.
	 */
	public function handle_mark_complete_action(): void {

		if ( ! isset( $_GET['action'], $_GET['order_id'], $_GET['_wpnonce'] ) || 'mark-completed' !== $_GET['action'] ) {
			return;
		}

		$order_id = intval( $_GET['order_id'] );

		if ( false === wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'mark-completed' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! ( $order instanceof WC_Order ) ) {
			return;
		}

		$user     = wp_get_current_user();
		$username = $user->user_login;

		$note = "Order status changed from {$order->get_status()} to Completed by {$username} in my-account area.";

		$order->update_status( 'completed', $note, true );
	}

}
