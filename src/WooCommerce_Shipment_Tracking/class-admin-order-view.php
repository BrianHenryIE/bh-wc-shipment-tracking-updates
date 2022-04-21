<?php
/**
 * When viewing a single order in the admin UI, enqueue JavaScript to replace the existing tracking details with
 * richer information.
 *
 * @package brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce_Shipment_Tracking;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\API_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\Tracking_Details_Abstract;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Edit the HTML generated by WooCommerce Shipment Tracking after page load.
 */
class Admin_Order_View {
	use LoggerAwareTrait;

    protected API_Interface $api;

	public function __construct( API_Interface $api, LoggerInterface $logger ) {
		$this->setLogger( $logger );
        $this->api = $api;
	}

	/**
	 * Generates replacement HTML for the Shipment Tracking metabox, using JavaScript to replace the existing HTML
	 * after page load.
	 *
	 * Adds:
	 * * current tracking status
	 * * Updated/delivered date
	 *
	 * @see \WC_Shipment_Tracking_Actions::display_html_tracking_item_for_meta_box()
	 *
	 * @hooked admin_enqueue_scripts
	 */
	public function tracking_information_for_order(): void {

		// Check is 'wc-shipment-tracking-js' script enqueued.
		$shipment_tracking_script_handle      = 'wc-shipment-tracking-js';
		$is_shipment_tracking_script_enqueued = wp_scripts()->query( $shipment_tracking_script_handle );

		if ( ! $is_shipment_tracking_script_enqueued ) {
			return;
		}
        // We assume now we're on `edit.php?post_type=shop_order`.

		global $post;
		$order_id = $post->ID;

		$wc_shipment_tracking_actions = wc_shipment_tracking()->actions;

		$items = $wc_shipment_tracking_actions->get_tracking_items( $order_id );

		$replacement_html = array();

		/**
		 * Tracking item data from WooCommerce Shipment Tracking.
		 * `date_shipped` is a timestamp stored as a string, `tracking_provider`|`custom_tracking_link` may be empty.
		 *
		 * @var array{tracking_provider:string, custom_tracking_provider:string, custom_tracking_link:string, tracking_number:string, date_shipped:string, tracking_id:string} $tracking_item
		 */
		foreach ( $items as $tracking_item ) {

			$tracking_number = str_replace( ' ', '', $tracking_item['tracking_number'] );

			/**
			 * Saved tracking details for this order.
			 *
			 * @var array<string, Tracking_Details_Abstract>|false $order_meta_all_tracking
			 */
			$order_meta_all_tracking = $this->api->get_saved_tracking_data_for_order( $order_id );

			if ( ! isset( $order_meta_all_tracking[ $tracking_number ] ) ) {
				continue;
			}

			$tracking_detail = $order_meta_all_tracking[ $tracking_number ];

			if ( ! is_null( $tracking_detail->get_expected_delivery_time() ) ) {
				$new_html = 'Expected delivery: ' . $tracking_detail->get_expected_delivery_time()->format( 'l, d-M' );
			} elseif ( ! is_null( $tracking_detail->get_carrier_status() ) ) {
				$new_html = $tracking_detail->get_carrier_status();
			} else {
				continue;
			}

			$formatted = $wc_shipment_tracking_actions->get_formatted_tracking_item( $order_id, $tracking_item );

			ob_start();
			?>
			<div class="tracking-content">
				<strong><?php echo esc_html( $formatted['formatted_tracking_provider'] ); ?></strong>
				- <a href="<?php echo esc_url( $formatted['formatted_tracking_link'] ); ?>" target="_blank" title="<?php echo esc_attr( __( 'Click here to track your shipment', 'woocommerce-shipment-tracking' ) ); ?>"><?php echo esc_html( __( 'Track', 'woocommerce-shipment-tracking' ) ); ?></a>
				<p><?php echo esc_html( $new_html ); ?></p>
				<p><em><?php echo esc_html( $tracking_item['tracking_number'] ); ?></em></p>
			</div>

			<div class="meta">
				<?php
				// TODO: Print using WordPress configured timezone.
				// TODO: Don't print the year if it is this year.
				$date_added = gmdate( 'l, j F Y', intval( $tracking_item['date_shipped'] ) );
				?>
				<p>Added: <?php echo esc_html( $date_added ); ?></p>
				<?php

				// TODO: Add the date when the order started to move.

				if ( ! is_null( $tracking_detail->get_last_updated_time() ) ) {
					$last_updated_time = $tracking_detail->get_last_updated_time();
					$date_updated      = $last_updated_time->format( 'l, j F Y' );
					if ( $date_updated !== $date_added ) {
						echo '<p>';
						// TODO: Add a method `get_delivered_time()`.
						echo 'completed' === $tracking_detail->get_equivalent_order_status() ? 'Delivered: ' : 'Updated: ';
						echo esc_html( $date_updated );
						echo '</p>';
					}
				}
				?>
				<a href="#" class="delete-tracking" rel="<?php echo esc_html( $tracking_item['tracking_id'] ); ?>">Delete</a>
			</div>

			<?php

			$output = ob_get_clean();

			if ( false !== $output ) {

				$replacement_html[ $tracking_item['tracking_id'] ] = $output;

			}
		}

		if ( empty( $replacement_html ) ) {
			return;
		}

		$data = wp_json_encode( $replacement_html );

		$script = <<<EOD
(function( $ ) {
    'use strict';
    $(document).ready(function() {
	    var data = $data;
	    for (const tracking_id in data) {
            var selector = '#tracking-item-' + tracking_id + ' ;
            var html = data[tracking_id];
            $(selector).html(html);
        }
	});
})( jQuery );
EOD;

		wp_add_inline_script( $shipment_tracking_script_handle, $script );
	}

}
