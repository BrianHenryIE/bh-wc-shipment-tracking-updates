<?php
/**
 * @see https://www.usps.com/business/web-tools-apis/track-and-confirm-api.htm
 *
 * @package brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\USPS;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\Tracker_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\Tracking_Details_Abstract;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Container;
use BrianHenryIE\WC_Shipment_Tracking_Updates\USPS\TrackConfirm;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Methods for querying single or multiple tracking numbers.
 */
class USPS_Tracker implements Tracker_Interface {

	use LoggerAwareTrait;

	/**
	 * "The Track/Confirm API limits the data requested to thirty-five (35) packages per transaction."
	 *
	 * @var int
	 */
	const MAX_TRACKING_IDS_PER_USPS_API_CALL = 35;

	/**
	 * Used to get an instance of the USPS TrackConfirm API.
	 *
	 * @var ContainerInterface
	 */
	protected ContainerInterface $container;

	/**
	 * Constructor.
	 *
	 * @param ContainerInterface $container PSR DI container for the plugin.
	 * @param LoggerInterface    $logger PSR logger.
	 */
	public function __construct( ContainerInterface $container, LoggerInterface $logger ) {
		$this->setLogger( $logger );
		$this->container = $container;
	}

	/**
	 * Run the query now.
	 *
	 * Synchronous
	 *
	 * @param string $tracking_number A single tracking number to check.
	 * @return Tracking_Details_Abstract
	 */
	public function query_single_tracking_number( string $tracking_number ): Tracking_Details_Abstract {
		return $this->query_multiple_tracking_numbers( array( $tracking_number ) )[ $tracking_number ];
	}

	/**
	 * Run the query now.
	 *
	 * Synchronous
	 *
	 * @param string[] $tracking_numbers A list of tracking numbers to check.
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

			// TODO: Temporary logging until the problem is understood.
			if ( ! isset( $array_response['TrackResponse'] ) ) {

				// <xml...><Error><Description>An unexpected system error has occurred. Please try again later or contact the System Administrator.

				// Another case: $xml_response empty

				if ( isset( $array_response['Errror']['Description'] ) ) {
					$error_message = $array_response['Errror']['Description'];
				} else {
					$error_message = 'Unexpectedly TrackResponse is not part of response';
				}

				$this->logger->error(
					$error_message,
					array(
						'xml_response'   => $xml_response,
						'array_response' => $array_response,
					)
				);
				return array();
			}

			if ( isset( $array_response['TrackResponse']['TrackInfo']['@attributes'] ) ) {
				$details[ $array_response['TrackResponse']['TrackInfo']['@attributes']['ID'] ] = $array_response['TrackResponse']['TrackInfo'];
			} else {
				foreach ( $array_response['TrackResponse']['TrackInfo'] as $detail ) {
					$details[ $detail['@attributes']['ID'] ] = $detail;
				}
			}

			foreach ( $details as $tracking_number => $detail ) {

				/** @var array<string, Tracking_Details_Abstract> $result $tracking_number will always be a string, and USPS_Tracking_Details extends Tracking_Details_Abstract. */
				$result[ $tracking_number ] = new USPS_Tracking_Details( $tracking_number, $detail, $this->logger );
			}
		}

		return $result;
	}

}
