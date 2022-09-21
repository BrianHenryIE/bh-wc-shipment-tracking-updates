<?php
/**
 *
 *
 * @package     brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\DHL;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\Tracking_Details_Abstract;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Dhl\Sdk\UnifiedTracking\Model\Tracking\TrackResponse;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Dhl\Sdk\UnifiedTracking\Model\Tracking\Response\ShipmentEvent;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Order_Statuses;
use DateTimeInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class DHL_Tracking_Details extends Tracking_Details_Abstract {
	use LoggerAwareTrait;

	// Allow multiple to be true.

	protected bool $is_dispatched       = false;
	protected bool $is_out_for_delivery = false;
	protected bool $is_delivered        = false;
	protected bool $is_returning        = false;

	// Maybe there's always a TrackResponse::latestStatus.
	protected ?string $latest_status_code = null;

	protected ?DateTimeInterface $estimated_delivery_time = null;

	public function __construct( TrackResponse $dhl_tracking_response, LoggerInterface $logger ) {
		$this->setLogger( $logger );

		$this->carrier            = 'dhl';
		$this->tracking_number    = $dhl_tracking_response->getTrackingId();
		$this->details            = $dhl_tracking_response->getStatusEvents();
		$this->carrier_status     = $dhl_tracking_response->getLatestStatus()->getDescription();
		$this->latest_status_code = $dhl_tracking_response->getLatestStatus()->getStatusCode();

		$this->estimated_delivery_time = ! is_null( $dhl_tracking_response->getEstimatedDeliveryTime() ) ? $dhl_tracking_response->getEstimatedDeliveryTime()->getDateTime() : null;

		$this->is_dispatched = array_reduce(
			$dhl_tracking_response->getStatusEvents(),
			function( bool $carry, ShipmentEvent $event ) {
				return $carry
				|| in_array(
					$event->getDescription(),
					array(
						'Shipment picked up',
					),
					true
				)
				|| in_array(
					$event->getStatusCode(),
					array(
						'transit',
					),
					true
				);
			},
			false
		);

		// $this->is_out_for_delivery = array_reduce();

		$this->is_delivered = 'delivered' === $dhl_tracking_response->getLatestStatus()->getStatusCode() ||
			array_reduce(
				$dhl_tracking_response->getStatusEvents(),
				function( bool $carry, ShipmentEvent $event ) {
					return $carry
					// || in_array(
					// $event->getDescription(),
					// array(),
					// true
					// )
						|| in_array(
							$event->getStatusCode(),
							array(
								'delivered',
							),
							true
						);
				},
				false
			);

		// $this->is_returning        = array_reduce();
	}

	public function is_dispatched(): bool {
		return $this->is_dispatched;
	}

	public function get_equivalent_order_status(): ?string {

		if ( $this->is_delivered ) {
			return 'completed';
		}

		if ( $this->is_dispatched ) {
			return Order_Statuses::IN_TRANSIT_WC_STATUS;
		}

		return null;

	}

	/**
	 * If a delivery time has been predicted, return it.
	 *
	 * @return ?DateTimeInterface
	 */
	public function get_delivery_time(): ?DateTimeInterface {
		return $this->estimated_delivery_time;
	}
}
