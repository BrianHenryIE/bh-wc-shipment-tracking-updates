<?php
/**
 * @see https://www.usps.com/business/web-tools-apis/track-and-confirm-api.htm
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers;

use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Order_Statuses;
use DateTime;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class USPS_Tracking_Details extends Tracking_Details_Abstract {

	use LoggerAwareTrait;

	public function __construct( string $tracking_number, array $details, LoggerInterface $logger ) {
		$logger->debug( 'Constructing USPS_Tracking_Details', array( $tracking_number, $details ) );

		$this->setLogger( $logger );
		$this->tracking_number = $tracking_number;
		$this->carrier         = 'usps';
		$this->details         = $details;

		if ( isset( $details['TrackSummary'] ) ) {
			$track_summary = $details['TrackSummary'];
			$this->set_last_updated_time( $track_summary );

			$this->carrier_status = $details['TrackSummary']['Event'];

		} elseif ( isset( $details['Error'] ) ) {

			$a = 'what to do?';

		}

	}

	/**
	 * @return bool
	 */
	public function is_dispatched(): bool {
		return ! is_null( $this->carrier_status ) && ! in_array( $this->carrier_status, $this->get_not_picked_up_statuses(), true );
	}

	/**
	 *
	 * @see wc_get_order_statuses()
	 *
	 * @return ?string null presumably means 'Shipping Label Created, USPS Awaiting Item'
	 */
	public function get_order_status(): ?string {

		$order_status = $this->get_order_status_for_usps_status( $this->carrier_status );

		return $order_status;
	}

	/**
	 * @param array{EventDate:string, EventTime?:string} $track_summary
	 */
	protected function set_last_updated_time( array $track_summary ) {
		// last_updated "August 7, 2021, 12:12pm".
		$last_updated = $this->details['TrackSummary']['EventDate'];
		$format       = 'F j, Y';

		// There may or may not be a time.
		if ( ! empty( $this->details['TrackSummary']['EventTime'] ) ) {
			$last_updated .= ', ' . $this->details['TrackSummary']['EventTime'];
			$format       .= ', g:ia';
		}
		// TODO: What timezone is used by USPS?
		$timezone = null; // as DateTimeZone object.

		$time = DateTime::createFromFormat( $format, $last_updated, $timezone );

		if ( false === $time ) {
			$this->logger->error( $this->tracking_number . ' ' . $last_updated, array( 'track_summary' => $track_summary ) );
		}

		$this->last_updated_time = $time;
	}

	/**
	 * e.g. "September 7, 2021"
	 *
	 * @return DateTime|null
	 */
	public function get_expected_delivery_time(): ?DateTime {
		if ( ! isset( $this->details['ExpectedDeliveryDate'] ) ) {
			return null;
		}
		$timezone = null; // as DateTimeZone object.
		$format   = 'F j, Y';
		return DateTime::createFromFormat( $format, $this->details['ExpectedDeliveryDate'], $timezone );
	}


	protected function get_order_status_for_usps_status( string $usps_status ): ?string {

		if ( in_array( $usps_status, $this->get_picked_up_statuses(), true ) ) {
			return Order_Statuses::IN_TRANSIT_WC_STATUS;
		}

		if ( in_array( $usps_status, $this->get_returned_statuses(), true ) ) {
			return Order_Statuses::RETURNING_WC_STATUS;
		}

		if ( in_array( $usps_status, $this->get_delivered_statuses(), true ) ) {
			return 'completed';
		}

		if ( ! in_array( $usps_status, $this->get_not_picked_up_statuses(), true ) ) {
			// Unexpected status.
			// TODO: Log.
			$this->logger->warning( 'An unexpected status was returned from USPS: ' . $usps_status, array( 'usps_status' => $usps_status ) );
		}

		return null;
	}

	// Available for Pickup

	/**
	 * @return string[]
	 */
	protected function get_not_picked_up_statuses(): array {

		$not_picked_up_statuses = array(
			'Shipping Label Created, USPS Awaiting Item', // NOT packed.
			'Pre-Shipment Info Sent to USPS, USPS Awaiting Item', // This customs?
			'Label Cancelled', // Not necessarily order cancelled.
		);

		// TODO: Filter
		return $not_picked_up_statuses;
	}

	/**
	 * @return string[]
	 */
	protected function get_picked_up_statuses(): array {

		$picked_up_statuses = array(
			'Acceptance',
			'Accepted at USPS Origin Facility',
			'USPS in possession of item',
			'Shipment Received, Package Acceptance Pending',
			'Accepted at USPS Origin Facility',
			'Arrived at USPS Regional Origin Facility',
			'Arrived at Post Office',
			'Arrived at USPS Regional Destination Facility',
			'Arrived at Hub',
			'Arrived at USPS Facility',
			'Arrived at USPS Regional Facility',
			'Sorting Complete',
			'Departed Post Office',
			'Dispatched from USPS International Service Center',
			'USPS in possession of item',
			'Departed USPS Regional Origin Facility',
			'Departed USPS Regional Facility',
			'Departed USPS Facility',
			'In Transit to Next Facility',
			'In Transit, Arriving On Time',
			'In Transit, Arriving Late',
			'Delivered to Agent for Final Delivery',
			'Awaiting Delivery Scan',
			'Departed USPS Regional Destination Facility',
			'Departed USPS Destination Facility',
			'Delivery Exception, Animal Interference', // TODO:
			'Out for Delivery, Expected Delivery by 9:00pm',
			'Out for Delivery',
			'Delivery Attempted - No Access to Delivery Location',
			'Held in Customs',
			'Arrived at Facility',
			'Processed Through Regional Facility',
			'Processed Through Facility',
			'Arrived at USPS Regional Destination Facility',
			'Departed',

			'Arrived at Military Post Office', // TODO: Delivered?!
		);

		// TODO: Filter.
		return $picked_up_statuses;
	}

	protected function get_delivered_statuses(): array {

		// $deliveredAtributeCodes = array( '01', '02', '03', '04', '05', '06', '08', '09', '10', '11', '17', '19', '23' );

		// $is_delivered = in_array($array['TrackInfo']['TrackSummary']['DeliveryAttributeCode'], $deliveredAtributeCodes);

		$delivered_statuses = array(
			'Delivered',
			'Delivered, Left with Individual',
			'Delivered, In/At Mailbox',
			'Delivered, Front Door/Porch',
			'Delivered, Front Desk/Reception/Mail Room',
			'Delivered, Garage or Other Location at Address',
			'Delivered, Parcel Locker',
			'Delivered, PO Box',
			'Delivered, Individual Picked Up at Post Office',
			'Held at Post Office, At Customer Request',
			'Delivered to Recipient by Agent',
			'Delivered, Individual Picked Up at Postal Facility',
			'Delivered, Neighbor as Requested',
		);

		return $delivered_statuses;
	}

	protected function get_returned_statuses(): array {

		$returned_statuses = array(
			'Delivered, To Original Sender',
			'Addressee Unknown',
			'USPS: Sent to Mail Recovery Center',
		);

		// TODO: Filter.
		return $returned_statuses;
	}

}
