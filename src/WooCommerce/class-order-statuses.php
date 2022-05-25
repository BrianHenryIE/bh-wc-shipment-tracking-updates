<?php
/**
 * Registers new statuses:
 * * packing
 * * in-transit
 * * returning
 *
 * @package    brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Registers the post statuses with WordPress.
 * Adds them to WooCommerce via filter.
 * Adds them to WooCommerce's "paid statuses" list via filter.
 * Includes them in WooCommerce reports via filter.
 */
class Order_Statuses {

	use LoggerAwareTrait;

	/**
	 *
	 * Max length 20 characters.
	 *
	 * @see https://developer.wordpress.org/reference/functions/register_post_status/#user-contributed-notes
	 */
	const PACKING_COMPLETE_WC_STATUS = 'packed';
	const IN_TRANSIT_WC_STATUS       = 'in-transit'; // The package has been scanned (updated since printed).
	const RETURNING_WC_STATUS        = 'returning';

	/**
	 * Constructor
	 *
	 * @param LoggerInterface $logger The plugin's PSR logger.
	 */
	public function __construct( $logger ) {
		$this->setLogger( $logger );
	}

	/**
	 * Register the order/post status with WordPress.
	 *
	 * Seems to be no harm registering the post status multiple times.
	 *
	 * @hooked woocommerce_init
	 * @see WooCommerce::init()
	 */
	public function register_status(): void {

		register_post_status(
			'wc-' . self::PACKING_COMPLETE_WC_STATUS,
			array(
				'label'                     => 'Packed',
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: Has the order been "packed" into its shipping container?. */
				'label_count'               => _n_noop( 'Packed <span class="count">(%s)</span>', 'Packed <span class="count">(%s)</span>', 'bh-wc-shipment-tracking-updates' ),
			)
		);

		register_post_status(
			'wc-' . self::IN_TRANSIT_WC_STATUS,
			array(
				'label'                     => 'In Transit',
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: Has the order been picked up by the shipping company and is now "in transit" to its destination. */
				'label_count'               => _n_noop( 'In Transit <span class="count">(%s)</span>', 'In Transit <span class="count">(%s)</span>', 'bh-wc-shipment-tracking-updates' ),
			)
		);

		register_post_status(
			'wc-' . self::RETURNING_WC_STATUS,
			array(
				'label'                     => 'Returning',
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: Was the shipping company unable to deliver the package and is now "returning" it. */
				'label_count'               => _n_noop( 'Returning <span class="count">(%s)</span>', 'Returning <span class="count">(%s)</span>', 'bh-wc-shipment-tracking-updates' ),
			)
		);
	}


	/**
	 * Adds the new order statuses after "processing".
	 *
	 * @hooked wc_order_statuses
	 * @see wc_get_order_statuses()
	 *
	 * @param string[] $order_statuses WooCommerce order statuses.
	 * @return string[]
	 */
	public function add_order_status_to_woocommerce( $order_statuses ): array {

		$new_order_statuses = array();

		foreach ( $order_statuses as $key => $status ) {
			$new_order_statuses[ $key ] = $status;
			if ( 'wc-processing' === $key ) {
				$new_order_statuses[ 'wc-' . self::PACKING_COMPLETE_WC_STATUS ] = __( 'Packed', 'bh-wc-shipment-tracking-updates' );
				$new_order_statuses[ 'wc-' . self::IN_TRANSIT_WC_STATUS ]       = __( 'In Transit', 'bh-wc-shipment-tracking-updates' );
				$new_order_statuses[ 'wc-' . self::RETURNING_WC_STATUS ]        = __( 'Returning', 'bh-wc-shipment-tracking-updates' );
			}
		}
		return $new_order_statuses;
	}

	/**
	 * Add the statuses to the list considered "paid" when considered by WooCommerce and other plugins, e.g. analytics.
	 *
	 * @hooked woocommerce_order_is_paid_statuses
	 * @see wc_get_is_paid_statuses()
	 *
	 * @param string[] $statuses ['processing', completed'] and other custom statuses that apply to paid orders.
	 * @return string[]
	 */
	public function add_to_paid_status_list( $statuses ): array {
		$statuses[] = self::PACKING_COMPLETE_WC_STATUS;
		$statuses[] = self::IN_TRANSIT_WC_STATUS;
		$statuses[] = self::RETURNING_WC_STATUS;
		return $statuses;
	}

	/**
	 * WooCommerce's reports do not respect wc_get_is_paid_statuses() so we need to add the status here too.
	 *
	 * @hooked woocommerce_reports_order_statuses
	 *
	 * @see \WC_Admin_Report::get_order_report_data()
	 * @see wp-admin/admin.php?page=wc-reports
	 *
	 * @param bool|string[] $order_statuses The existing statuses in the report.
	 * @return bool|string[] false|string[]
	 */
	public function add_to_reports_status_list( $order_statuses ) {

		// In the refund report it is false.
		if ( false === $order_statuses || ! is_array( $order_statuses ) ) {
			return $order_statuses;
		}

		// In all paid scenarios, there are at least 'completed', 'processing', 'on-hold' already in the list.
		if ( ! ( in_array( 'completed', $order_statuses, true )
				&& in_array( 'processing', $order_statuses, true )
				&& in_array( 'on-hold', $order_statuses, true )
		) ) {
			return $order_statuses;
		}

		$order_statuses[] = self::PACKING_COMPLETE_WC_STATUS;
		$order_statuses[] = self::IN_TRANSIT_WC_STATUS;
		$order_statuses[] = self::RETURNING_WC_STATUS;

		return array_unique( $order_statuses );
	}
}
