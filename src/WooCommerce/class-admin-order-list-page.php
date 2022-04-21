<?php
/**
 * Displays a list of number-of-days-since-packed : number-of-orders.
 *
 * TODO: Add bulk actions to change between new order statuses.
 *
 * @package brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\API_Interface;

/**
 * Prints an admin notice only on the admin order list page for the packed status.
 */
class Admin_Order_List_Page {

	/**
	 * Main plugin class to process the data.
	 *
	 * @uses API_Interface::get_order_ids_by_number_of_days_since_packed()
	 *
	 * @var API_Interface
	 */
	protected API_Interface $api;

	/**
	 * Constructor
	 *
	 * @param API_Interface $api The main plugin functions.
	 */
	public function __construct( API_Interface $api ) {
		$this->api = $api;
	}

	/**
	 * Prints an admin notice when orders are filtered to `packed` status, showing how long it has been since
	 * the order was marked packed, in order to find times where the packages were never scanned by USPS.
	 *
	 * @hooked admin_notices
	 */
	public function print_packed_stats(): void {

		$current_screen = get_current_screen();

		global $post_type, $post_status;

		if ( is_null( $current_screen ) || 'edit-shop_order' !== $current_screen->id || 'shop_order' !== $post_type || 'wc-' . Order_Statuses::PACKING_COMPLETE_WC_STATUS !== $post_status ) {
			return;
		}

		$order_ids_by_number_of_days_since_packed = $this->api->get_order_ids_by_number_of_days_since_packed();

		if ( empty( $order_ids_by_number_of_days_since_packed ) ) {
			return;
		}

		echo '<div class="notice">';

		// TODO: Make it clear that these stats apply to all packed orders, not just the 20 currently being displayed.

		foreach ( $order_ids_by_number_of_days_since_packed as $num_days => $order_ids ) {

			// TODO: Link to both only those N days old, and also all those N days and OLDER.
			$url = admin_url( 'edit.php?post_type=shop_order&post_status=' . Order_Statuses::PACKING_COMPLETE_WC_STATUS . '&s=' . implode( ',', $order_ids ) );

			$output = '';

			$num_days = intval( $num_days );

			$order_id_count = count( $order_ids );

			$output .= '<p><a href="' . esc_url( $url ) . '">';
			$output .= "{$order_id_count} order";
			$output .= 1 === $order_id_count ? '' : 's';
			$output .= " waiting {$num_days} day";
			$output .= 1 === $num_days ? '' : 's';
			$output .= ' since being packed.';
			$output .= '</a>';
			$output .= '</p>';

			echo wp_kses(
				$output,
				array(
					'p' => array(),
					'a' => array(
						'href' => array(),
					),
				)
			);

		}

		echo '</div>';

	}

}
