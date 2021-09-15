<?php

namespace BrianHenryIE\WC_Shipment_Tracking_Updates;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Settings_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\USPS_Tracker;
use BrianHenryIE\WC_Shipment_Tracking_Updates\USPS\TrackConfirm;
use BrianHenryIE\WC_Shipment_Tracking_Updates\USPS\USPSBase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class Container implements ContainerInterface {

	use LoggerAwareTrait;

	protected Settings_Interface $settings;

	const USPS_SHIPMENT_TRACKER     = 'usps_shipment_tracker';
	const EASYPOST_SHIPMENT_TRACKER = 'easypost_shipment_tracker';

	const USPS_TRACK_CONFIRM_API = 'usps_track_confirm_api';

	public function __construct( Settings_Interface $settings, LoggerInterface $logger ) {
		$this->setLogger( $logger );
		$this->settings = $settings;
	}

	/**
	 * Finds an entry of the container by its identifier and returns it.
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @return mixed Entry.
	 * @throws ContainerExceptionInterface Error while retrieving the entry.
	 *
	 * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
	 */
	public function get( $id ) {

		switch ( $id ) {
			case self::USPS_SHIPMENT_TRACKER:
				return new USPS_Tracker( $this, $this->logger );

			// case self::EASYPOST_SHIPMENT_TRACKER:
			// return new EasyPost_Tracker( $this->settings->get_easypost_api_key(), $this->logger );

			case self::USPS_TRACK_CONFIRM_API:
				$track_confirm_api = new TrackConfirm( $this->settings->get_usps_username() );
				$track_confirm_api->setSourceId( $this->settings->get_usps_source_id() );
				$track_confirm_api->setClientIp( \WC_Geolocation::get_external_ip_address() );

				return $track_confirm_api;
		}

	}

	/**
	 * Returns true if the container can return an entry for the given identifier.
	 * Returns false otherwise.
	 *
	 * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
	 * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @return bool
	 */
	public function has( $id ) {
		// TODO: Implement has() method.
	}
}
