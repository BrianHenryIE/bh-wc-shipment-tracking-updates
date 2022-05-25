<?php
/**
 * "When you first request access to the Shipment Tracking - Unified API, you will get the initial service level which
 * allows 250 calls per day with a maximum of 1 call per second."
 *
 * @see https://developer.dhl.com/api-reference/shipment-tracking
 *
 * @package     brianhenryie/bh-wc-shipment-tracking-updates
 */



namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\DHL;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\Tracker_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\Tracking_Details_Abstract;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Dhl\Sdk\UnifiedTracking\Exception\AuthenticationException;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Dhl\Sdk\UnifiedTracking\Service\ServiceFactory as DhlServiceFactory;
use DateTimeZone;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class DHL_Tracker implements Tracker_Interface {
	use LoggerAwareTrait;

	protected DhlServiceFactory $dhl_service_factory;

	protected DHL_Settings_Interface $settings;

	public function __construct( DhlServiceFactory $dhl_service_factory, DHL_Settings_Interface $settings, LoggerInterface $logger ) {
		$this->setLogger( $logger );

		$this->settings = $settings;

		$this->dhl_service_factory = $dhl_service_factory;
	}

	public function query_single_tracking_number( string $tracking_number ): Tracking_Details_Abstract {

		$consumer_api_key = $this->settings->get_consumer_api_key();

		$time_zone = new DateTimeZone( 'UTC' );

		// TODO: Doesn't need to be in this class, inject it.
		$tracking_service = $this->dhl_service_factory->createTrackingService( $consumer_api_key, $this->logger, $time_zone );

		// TODO: Catch invalid tracking number error.

		try {
			$response = $tracking_service->retrieveTrackingInformation( $tracking_number );
		} catch ( AuthenticationException $e ) {
			// TODO: Handle authentication error.
		}

		if ( count( $response ) > 1 ) {
			$this->logger->error(
				'Unexpectedly got more than one entry in response for tracking number ' . $tracking_number,
				array(
					'tracking_number' => $tracking_number,
					'response'        => $response,
				)
			);
		}

		$received_tracking_data = array_pop( $response );

		return new DHL_Tracking_Details( $received_tracking_data, $this->logger );
	}

	/**
	 *
	 * Until a rate limit request for the account is made (on the DHL website), the API is limited to "a maximum
	 * of 1 call per second", so pause briefly after each API call if one second has not passed.
	 *
	 * @param array $tracking_numbers
	 *
	 * @return array|Tracking_Details_Abstract[]
	 */
	public function query_multiple_tracking_numbers( array $tracking_numbers ): array {

		$result = array();

		$is_rate_limited = true;

		foreach ( $tracking_numbers as $tracking_number ) {

			$time_start = microtime( true );

			$result[ $tracking_number ] = $this->query_single_tracking_number( $tracking_number );

			if ( $is_rate_limited && microtime( true ) - $time_start < 1 ) {
				// 1 second is the smallest time to sleep.
				sleep( 1 );
			}
		}

		return $result;
	}
}
