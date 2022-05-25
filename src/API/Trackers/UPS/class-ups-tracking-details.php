<?php
/**
 *
 *
 * @package     brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\UPS;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\Tracking_Details_Abstract;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Order_Statuses;
use DateTime;
use DateTimeInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 */
class UPS_Tracking_Details extends Tracking_Details_Abstract {
	use LoggerAwareTrait;

	protected ?DateTimeInterface $expected_delivery = null;

	protected ?string $package_message_description = null;

	public function __construct( \stdClass $track_response, LoggerInterface $logger ) {
		$this->setLogger( $logger );

		$this->carrier         = 'UPS';
		$this->tracking_number = $track_response->ShipmentIdentificationNumber;
		$this->details         = (array) $track_response;

		$latest_activity = is_array( $track_response->Package->Activity ) ? $track_response->Package->Activity[0] : $track_response->Package->Activity;

		$this->carrier_status = $latest_activity->Status->StatusType->Description;

		// 2022-04-20 10:35:00
		$last_updated_time_string = "$latest_activity->GMTDate $latest_activity->GMTTime";

		$timezone          = null; // as DateTimeZone object.
		$last_updated_time = DateTime::createFromFormat( 'Y-m-d H:i:s', $last_updated_time_string, $timezone );

		if ( false !== $last_updated_time ) {
			$this->last_updated_time = $last_updated_time;
		}

		if ( isset( $track_response->ScheduledDeliveryDate ) ) {
			// e.g. "20220425"
			// TODO: Timezone
			$timezone          = null; // as DateTimeZone object.
			$expected_delivery = DateTime::createFromFormat( 'Ymd', $track_response->ScheduledDeliveryDate, $timezone );
			if ( false === $expected_delivery ) {
				$this->logger->error( 'Error parsing UPS expected delivery date "' . $track_response->ScheduledDeliveryDate . '".', array( 'track_response' => $track_response ) );
			} else {
				$this->expected_delivery = $expected_delivery;
			}
		}

		// Does not exist before pickup.
		if ( isset( $track_response->Package->Message ) ) {
			// Message->Code = 01 : Description "On Time".
			$this->package_message_description = $track_response->Package->Message->Description;
		}
	}

	/**
	 * TODO: Make sure expected_delivery is null before pickup scan.
	 *
	 * @return bool
	 */
	public function is_dispatched(): bool {
		return ! is_null( $this->expected_delivery );
	}

	/**
	 * Translate the UPS status to its matching WooCommerce status.
	 *
	 * @return ?string
	 */
	public function get_equivalent_order_status(): ?string {

		switch ( $this->carrier_status ) {
			case 'Arrived at Facility':
			case 'Departed from Facility':
			case 'Processing at UPS Facility':
			case 'A late UPS trailer arrival has caused a delay. We\'re adjusting plans to deliver your package as quickly as possible.':
				return Order_Statuses::IN_TRANSIT_WC_STATUS;
			case 'Delivered':
				return 'completed';
			case 'Shipper created a label, UPS has not received the package yet.':
				// Purchased/printed but not yet picked up.
				return null;
			default:
				$this->logger->notice( 'UPS carrier_status: ' . $this->carrier_status, array( 'tracking_number' => $this->tracking_number ) );
				return null;
		}
	}

	/**
	 * Null when unavailable.
	 * In the past when already delivered.
	 *
	 * @return ?DateTimeInterface
	 */
	public function get_delivery_time(): ?DateTimeInterface {
		return $this->expected_delivery;
	}
}
