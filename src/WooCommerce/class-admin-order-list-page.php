<?php
/**
 * Adds a bulk action "Change status to packed".
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

		ksort( $order_ids_by_number_of_days_since_packed );

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



	/**
	 * Add "Change status to packed" to bulk actions drop-down, immediately after "Change status to processing".
	 *
	 * `<option value="mark_packed">Change status to packed</option>`.
	 *
	 * @see https://rudrastyh.com/woocommerce/bulk-change-custom-order-status.html
	 *
	 * @hooked bulk_actions-edit-shop_order

	 * @param array<string,string> $bulk_actions The existing bulk action on the shop_order list page.
	 * @return array<string,string>
	 */
	public function register_bulk_action_print_shipping_labels_pdf( array $bulk_actions ): array {

		$new_bulk_actions = array();
		foreach ( $bulk_actions as $key => $value ) {
			$new_bulk_actions[ $key ] = $value;
			if ( 'mark_processing' === $key ) {
				$new_bulk_actions['mark_packed'] = 'Change status to packed';
			}
		}

		return $new_bulk_actions;
	}

	/**
	 * @see https://rudrastyh.com/woocommerce/bulk-change-custom-order-status.html
	 *
	 * @hooked admin_action_mark_packed
	 */
	public function update_order_statuses(): void {

		if ( false === check_admin_referer( 'bulk-posts' ) ) {
			return;
		}

		// If an array with order IDs is not presented, exit the function.
		// 'post' as in post id, not HTTP POST.
		if ( ! isset( $_REQUEST['post'] ) && ! is_array( $_REQUEST['post'] ) ) {

			// TODO: Admin notice to say "none selected".

			return;
		}

		$order_ids = array_map( 'intval', $_REQUEST['post'] );

		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! ( $order instanceof \WC_Order ) ) {
				continue;
			}
			$order->update_status( Order_Statuses::PACKING_COMPLETE_WC_STATUS );
			$order->save();
		}
	}

	/**
	 * TODO: The wrong message is being printed! "50 order statuses changed.".
	 *
	 * @hooked admin_notices
	 */
	public function print_bulk_mark_packed_status_notice(): void {

		global $pagenow, $typenow;

		if ( 'shop_order' !== $typenow || 'edit.php' !== $pagenow || ! isset( $_REQUEST['marked_packed'] ) ) {
			return;
		}

		if ( false === check_admin_referer( 'bulk-posts' ) ) {
			return;
		}

		$changed = intval( $_REQUEST['marked_packed'] );

		$message = sprintf( _n( 'Order status set to Packed.', '%i order statuses set to Packed.', $changed ), number_format_i18n( $changed ) );

		$allowed_html = array(
			'div' => array(
				'class' => array(),
			),
			'p'   => array(),
		);

		echo wp_kses( "<div class=\"updated\"><p>{$message}</p></div>", $allowed_html );

	}


}
