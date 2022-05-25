<?php
/**
 *
 * 1. Sign up for UPS / Log in to existing account.
 * 2. Request an Access Key.
 * 3. ???
 *
 * @see https://www.ups.com/upsdeveloperkit
 * @see https://github.com/gabrielbull/php-ups-api
 *
 * @package     brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\UPS;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\Tracker_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\Tracking_Details_Abstract;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Ups\Tracking as UPS_Tracking;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Query UPS XMl Tracking API.
 */
class UPS_Tracker implements Tracker_Interface {
	use LoggerAwareTrait;

	/**
	 * User id, password and access key.
	 *
	 * @var UPS_Settings_Interface
	 */
	protected UPS_Settings_Interface $settings;

	protected UPS_Tracking $tracker;

	/**
	 * Constructor.
	 *
	 * @param UPS_Settings_Interface $settings Settings to access the UPS API.
	 * @param LoggerInterface        $logger PSR Logger.
	 */
	public function __construct( UPS_Settings_Interface $settings, LoggerInterface $logger ) {
		$this->setLogger( $logger );

		$access_key = $settings->get_access_key();
		$user_id    = $settings->get_user_id();
		$password   = $settings->get_password();

		$this->tracker = new UPS_Tracking( $access_key, $user_id, $password );

	}

	/**
	 * Get the tracking details for a single UPS tracking number.
	 *
	 * TODO: A single tracking number can cover multiple packages in one shipment. How to detect/handle that? Wait for
	 * an Exception and bug report?!
	 *
	 * @param string $tracking_number The UPS tracking number to check.
	 *
	 * @return Tracking_Details_Abstract
	 * @throws \Exception
	 */
	public function query_single_tracking_number( string $tracking_number ): Tracking_Details_Abstract {
		return $this->query_multiple_tracking_numbers( array( $tracking_number ) )[0];
	}

	/**
	 * The UPS API doesn't allow multiple tracking calls per request, so this function is really just a loop over
	 * `query_single_tracking_number()`.
	 *
	 * UPS Developer Kit API Tech Support Guide page 30 says:
	 * "The API only tracks a single tracking or reference number with each tracking request".
	 *
	 * @see https://www.ups.com/assets/resources/webcontent/en_US/ups-dev-kit-user-guide.pdf
	 *
	 * @param string[] $tracking_numbers Array of tracking numbers to query.
	 *
	 * @return Tracking_Details_Abstract[]
	 */
	public function query_multiple_tracking_numbers( array $tracking_numbers ): array {

		$result = array();

		foreach ( $tracking_numbers as $tracking_number ) {
			try {
				$result[ $tracking_number ] = $this->execute_query( $tracking_number );
			} catch ( \Exception $e ) {
				$this->logger->error(
					$e->getMessage(),
					array(
						'exception'       => $e,
						'exception_class' => get_class( $e ),
					)
				);

				// TODO: Decide if the one tracking number was the problem or will every request fail?
				// TODO: `break;` or `return $result`.

				// e.g. "Failure: Invalid Access License number (250003)". is bad auth, just return.
			}
		}

		return $result;
	}


	protected function execute_query( string $tracking_number ): Tracking_Details_Abstract {

		$shipment = $this->tracker->track( $tracking_number );

		return new UPS_Tracking_Details( $shipment, $this->logger );
	}
}
