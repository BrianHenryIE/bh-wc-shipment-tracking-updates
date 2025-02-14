<?php
/**
 * A Tracking_Details_Abstract object for a USPS API response.
 *
 * TODO: What timezone is used by USPS?
 *
 * @see https://www.usps.com/business/web-tools-apis/track-and-confirm-api.htm
 * @see Tracking_Details_Abstract
 *
 * @package     brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\USPS;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\Tracking_Details_Abstract;
use BrianHenryIE\WC_Shipment_Tracking_Updates\USPS\TrackConfirm;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Order_Statuses;
use DateTime;
use DateTimeInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Generally parses the USPS API response into a Tracking_Details_Abstract object.
 */
class USPS_Tracking_Details extends Tracking_Details_Abstract {

	use LoggerAwareTrait;

	protected ?string $usps_status_category = null;

	/**
	 * Construct a Tracking_Details_Abstract with data from USPS API.
	 *
	 * @see TrackConfirm::getArrayResponse()
	 *
	 * @param string                                $tracking_number The tracking number this Tracking_Details_Abstract represents.
	 * @param array<string,array<int|string,mixed>> $details The array returned from the USPS API. // TODO: This PhpDoc is incorrect.
	 * @param LoggerInterface                       $logger A PSR logger.
	 */
	public function __construct( string $tracking_number, array $details, LoggerInterface $logger ) {

		$this->setLogger( $logger );

		$this->carrier         = 'usps';
		$this->tracking_number = $tracking_number;
		$this->details         = $details;

		$status_categories = array(
			'',
			'Pre-Shipment',
			'Accepted',
			'In Transit',
			'In Transit from Origin Processing',
			'Alert',
			'Out for Delivery',
			'Delivered',
			'Delivered to Agent',
			'Delivery Attempt',
			'Available for Pickup',
			'Moving Through Network',
			'International Transit',
			'Customs Transit',
			'In Transit from Origin Processing',
			'Processing at Destination',
			'Preparing for Delivery',
		);

		if ( isset( $details['StatusCategory'] ) && ! empty( $details['StatusCategory'] ) ) {
			$this->usps_status_category = $details['StatusCategory'];

			if ( ! in_array( $details['StatusCategory'], $status_categories, true ) ) {
				$logger->notice( 'New USPS StatusCategory : "' . $details['StatusCategory'] . '" - ' . $tracking_number, array( 'details' => $details ) );
			}
		} else {
			$this->usps_status_category = null;
		}

		if ( isset( $details['TrackSummary'] ) ) {
			$track_summary = $details['TrackSummary'];
			$this->set_last_updated_time( $track_summary );

			$this->carrier_status = $details['TrackSummary']['Event'];

		} elseif ( isset( $details['Error'] ) ) {

			$error = $details['Error'];

			$description = isset( $error['Description'] ) ? $error['Description'] : '';

			$this->logger->error( "Error with tracking number {$tracking_number}. {$description}", array( 'details' => $details ) );

		}
	}

	/**
	 * Determines if the package has already been picked up and scanned.
	 *
	 * @return bool
	 */
	public function is_dispatched(): bool {
		return ! is_null( $this->carrier_status ) && ! in_array( $this->carrier_status, $this->get_not_picked_up_statuses(), true );
	}

	/**
	 * Parse the details for the time of the most recent update.
	 *
	 * @param array{EventDate:string, EventTime?:string} $track_summary The TrackSummary key from the USPS API.
	 */
	protected function set_last_updated_time( array $track_summary ): void {
		// last_updated "August 7, 2021, 12:12pm".
		$last_updated = $track_summary['EventDate'];
		$format       = 'F j, Y';

		// There may or may not be a time.
		if ( ! empty( $track_summary['EventTime'] ) ) {
			$last_updated .= ', ' . $this->details['TrackSummary']['EventTime'];
			$format       .= ', g:ia';
		}

		// TODO: What timezone is used by USPS?
		$timezone              = null; // as DateTimeZone object.
		$last_updated_datetime = DateTime::createFromFormat( $format, $last_updated, $timezone );

		if ( false === $last_updated_datetime ) {
			$this->logger->error( $this->tracking_number . ' ' . $last_updated, array( 'track_summary' => $track_summary ) );
			return;
		}

		$this->last_updated_time = $last_updated_datetime;
	}

	/**
	 * Parse the details for an expected delivery date.
	 *
	 * Input format e.g. "September 7, 2021".
	 *
	 * @return ?DateTimeInterface
	 */
	public function get_delivery_time(): ?DateTimeInterface {

		if ( ! isset( $this->details['ExpectedDeliveryDate'] ) ) {
			return null;
		}

		$carrier_formatted_time     = $this->details['ExpectedDeliveryDate'];
		$timezone                   = null; // as DateTimeZone object.
		$format                     = 'F j, Y';
		$expected_delivery_datetime = DateTime::createFromFormat( $format, $carrier_formatted_time, $timezone );

		if ( false === $expected_delivery_datetime ) {
			$this->logger->warning(
				'Failed to parse ' . $carrier_formatted_time . ' to expected delivery time.',
				array(
					'carrier_formatted_time' => $carrier_formatted_time,
					'datetime_format'        => $format,
				)
			);
			return null;
		}

		return $expected_delivery_datetime;
	}

	/**
	 * Translates the status returned from the USPS API (which could be null, still), into its
	 * equivalent WooCommerce order status.
	 *
	 * @see Tracking_Details_Abstract::get_equivalent_order_status()
	 * @see USPS_Tracking_Details::carrier_status
	 * @see Order_Statuses
	 * @see wc_get_order_statuses()
	 *
	 * @return ?string null presumably means 'Shipping Label Created, USPS Awaiting Item'
	 */
	public function get_equivalent_order_status(): ?string {

		if ( in_array( $this->carrier_status, $this->get_returning_statuses(), true ) ) {
			return Order_Statuses::RETURNING_WC_STATUS;
		}

		switch ( $this->usps_status_category ) {
			case null:
				break;
			case 'Accepted':
			case 'In Transit':
			case 'In Transit from Origin Processing':
			case 'Out for Delivery':
			case 'Moving Through Network':
			case 'International Transit':
			case 'Customs Transit':
			case 'Processing at Destination':
				return Order_Statuses::IN_TRANSIT_WC_STATUS;
			case 'Alert':
			case 'Delivery Attempt':
			case 'Available for Pickup':
			case 'Preparing for Delivery':
				// The same as above, but not precisely the same meaning.
				return Order_Statuses::IN_TRANSIT_WC_STATUS;
			case 'Delivered':
			case 'Delivered to Agent':
				return 'completed';
		}

		$usps_status = $this->carrier_status;

		if ( is_null( $usps_status ) ) {
			return null;
		}

		if ( in_array( $usps_status, $this->get_in_transit_statuses(), true ) ) {
			return Order_Statuses::IN_TRANSIT_WC_STATUS;
		}

		if ( in_array( $usps_status, $this->get_returning_statuses(), true ) ) {
			return Order_Statuses::RETURNING_WC_STATUS;
		}

		if ( in_array( $usps_status, $this->get_delivered_statuses(), true ) ) {
			return 'completed';
		}

		// Unexpected status.
		// Please contact support so this status can be added to the plugin.
		// The filters later in this class can be used to add it in the meantime.
		if ( ! in_array( $usps_status, $this->get_not_picked_up_statuses(), true ) ) {
			$this->logger->notice( 'An unexpected status was returned from USPS: ' . $usps_status, array( 'usps_status' => $usps_status ) );
		}

		return null;
	}

	/**
	 * The list of statuses which might be returned by USPS before the package has been scanned.
	 *
	 * @return string[]
	 */
	protected function get_not_picked_up_statuses(): array {

		$not_picked_up_statuses = array(
			'Shipping Label Created, USPS Awaiting Item', // Immediately upon creation. Does not indicate "packed".
			'Pre-Shipment Info Sent to USPS, USPS Awaiting Item', // This is customs?
			'Label Cancelled', // Not the same thing as order cancelled.
		);

		/**
		 * Filter the list of statuses which might be returned by USPS before the package has been scanned.
		 *
		 * @param string[] $not_picked_up_statuses The list of potential USPS statuses before the package has been scanned.
		 */
		return apply_filters( 'bh_wc_shipment_tracking_updates_not_picked_up_statuses', $not_picked_up_statuses );
	}

	/**
	 * The list of order statuses USPS might return that indicate the package has been scanned by USPS and is in transit
	 * to its destination.
	 *
	 * @see Order_Statuses::IN_TRANSIT_WC_STATUS
	 *
	 * @return string[]
	 */
	protected function get_in_transit_statuses(): array {

		$in_transit_statuses = array(
			'USPS picked up item',
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
			'Out for Delivery, Expected Delivery by 9:00pm',
			'Out for Delivery',
			'Delivery Attempted - No Access to Delivery Location',
			'Arrived at Facility',
			'Arrival at Post Office',
			'Processed Through Regional Facility',
			'Processed Through Facility',
			'Processed through Facility',

			'Arrived at USPS Regional Destination Facility',
			'Arrived at USPS Destination Facility',
			'Departed',
			'Departed Facility',
			'Forwarded',
			'Rescheduled to Next Delivery Day',

			'Customs Clearance',
			'Held in Customs',

			'Delivery Exception, Animal Interference', // TODO: Use this as an example for actions.
			'Arrived at Military Post Office', // TODO: Should this be considered delivered?!

			'Notice Left (No Secure Location Available)',

			'No Access to Delivery Location', // Should this be a new "needs attention" status?
		);

		/**
		 * Filter the list of "in-transit" statuses.
		 *
		 * @see Order_Statuses::IN_TRANSIT_WC_STATUS
		 *
		 * @param string[] $in_transit_statuses The list of USPS statuses that indicate the package has been picked up and is in transit to its destination.
		 */
		return apply_filters( 'bh_wc_shipment_tracking_updates_in_transit_statuses', $in_transit_statuses );
	}

	/**
	 * The list of order statuses USPS might return that indicate the package has been delivered.
	 *
	 * @return string[]
	 */
	protected function get_delivered_statuses(): array {

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
			'Available for Pickup',
			'Collect for Pick Up',
			'Intercepted', // @see https://www.usps.com/manage/package-intercept.htm

			'Arrived', // Seems to mean delivered internationally?
		);

		/**
		 * Filter the list of "delivered" statuses.
		 *
		 * @param string[] $delivered_statuses The list of USPS statuses that indicate the package has been delivered.
		 */
		return apply_filters( 'bh_wc_shipment_tracking_updates_delivered_statuses', $delivered_statuses );
	}

	/**
	 * The list of order statuses USPS might return that indicate the package is being returned.
	 *
	 * @see Order_Statuses::RETURNING_WC_STATUS
	 *
	 * @return string[]
	 */
	protected function get_returning_statuses(): array {

		$returning_statuses = array(
			'Delivered, To Original Sender', // This is "returned"... there must always be an earlier status that indicates "returning".
			'Addressee Unknown',
			'Sent to Mail Recovery Center',
			'Insufficient Address', // Does this definitely mean returning?
			'Moved, Left no Address',
			'Return to Sender',
			'Return to Sender Processed',
		);

		/**
		 * Filter the list of "returning" statuses.
		 *
		 * @see Order_Statuses::RETURNING_WC_STATUS
		 *
		 * @param string[] $returning_statuses The list of USPS statuses that indicate the package is being returned.
		 */
		return apply_filters( 'bh_wc_shipment_tracking_updates_returning_statuses', $returning_statuses );
	}

}
