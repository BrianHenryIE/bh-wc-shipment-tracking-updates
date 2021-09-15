<?php
/**
 * @see https://www.usps.com/business/web-tools-apis/track-and-confirm-api.htm
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers;

use BrianHenryIE\WC_Shipment_Tracking_Updates\Container;
use BrianHenryIE\WC_Shipment_Tracking_Updates\USPS\TrackConfirm;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareTrait;

class USPS_Tracker implements Tracker_Interface {

	use LoggerAwareTrait;

	/**
	 * "The Track/Confirm API limits the data requested to thirty-five (35) packages per transaction."
	 *
	 * @var int
	 */
	const MAX_TRACKING_IDS_PER_USPS_API_CALL = 35;

	protected ContainerInterface $container;

	public function __construct( ContainerInterface $container, $logger ) {
		$this->setLogger( $logger );
		$this->container = $container;
	}


	/**
	 * Run the query now.
	 *
	 * Synchronous
	 *
	 * @param string $tracking_id
	 * @return Tracking_Details_Abstract
	 */
	public function query_single_tracking_number( string $tracking_number ): Tracking_Details_Abstract {
		return $this->query_multiple_tracking_numbers( array( $tracking_number ) )[$tracking_number];
	}

	/**
	 * Run the query now.
	 *
	 * Synchronous
	 *
	 * @param string[] $tracking_ids
	 * @return array<string, Tracking_Details_Abstract>
	 */
	public function query_multiple_tracking_numbers( array $tracking_numbers ): array {

		$jobs = array_chunk( $tracking_numbers, self::MAX_TRACKING_IDS_PER_USPS_API_CALL );

		$result = array();

		foreach ( $jobs as $job ) {
			/** @var TrackConfirm $track_confirm_api */
			$track_confirm_api = $this->container->get( Container::USPS_TRACK_CONFIRM_API );

			foreach ( $job as $tracking_number ) {
				$track_confirm_api->addPackage( $tracking_number );
			}

			// Needed for expected delivery date.
			$track_confirm_api->setRevision( 1 );

			// Perform the request and return result.
			$xml_response   = $track_confirm_api->getTracking();
			$array_response = $track_confirm_api->convertResponseToArray();

			// $this->logger->debug( $xml_response );

			/** @var array<string, array> <tracking number, details> $details */
			$details = array();

			if ( isset( $array_response['TrackResponse']['TrackInfo']['@attributes'] ) ) {
				$details[ $array_response['TrackResponse']['TrackInfo']['@attributes']['ID'] ] = $array_response['TrackResponse']['TrackInfo'];
			} else {
				foreach ( $array_response['TrackResponse']['TrackInfo'] as $detail ) {
					$details[ $detail['@attributes']['ID'] ] = $detail;
				}
			}
			// Single successful.

			foreach ( $details as $tracking_number => $detail ) {

				$result[ $tracking_number ] = new USPS_Tracking_Details( $tracking_number, $detail, $this->logger );

			}
		}

		return $result;
	}

}
