<?php

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Shipment_Tracking_Updates\USPS\TrackConfirm;
use BrianHenryIE\WC_Shipment_Tracking_Updates\USPS\USPSBase;
use Psr\Container\ContainerInterface;

class USPS_Tracker_Integration_Test extends \Codeception\TestCase\WPTestCase {

	public function testQueryMultiple() {

		$logger = new ColorLogger();

		$usps_username = $_ENV['USPS_USERNAME'];

		$tracker = new TrackConfirm( $usps_username );
		$tracker->setRevision( 1 );
		$tracker->setClientIp( \WC_Geolocation::get_external_ip_address() );
		$tracker->setSourceId( 'BHtest' );

		$container = $this->makeEmpty(
			ContainerInterface::class,
			array(
				'get' => $tracker,
			)
		);

		USPSBase::$testMode = true;

		$sut = new USPS_Tracker( $container, $logger );

		$ids = array( '95000000000000000000000', '95000000000000000000000' );

		$results = $sut->query_multiple_tracking_numbers( $ids );

		foreach ( $results as $result ) {
			$result->get_last_updated_time();
		}

		$a = $result;

	}

	public function test_one() {

		$logger    = new ColorLogger();
		$container = $this->makeEmpty(
			ContainerInterface::class,
			array(
				'get' => '',
			)
		);

		$usps_username = $_ENV['USPS_USERNAME'];

		$tracker = new TrackConfirm( $usps_username );

		$id = '9400136895232222511032';

		$id2 = '9400136895232239530125';

		$tracker->addPackage( $id );

		$tracker->addPackage( $id2 );

		// Perform the request and return result
		$xml_response   = $tracker->getTracking();
		$array_response = $tracker->convertResponseToArray();

		$logger->info( $xml_response );

		$details = array();

		if ( false === $xml_response ) {
			// no internet connection.
		}

		if ( isset( $array_response['TrackResponse']['TrackInfo']['TrackDetail'] ) ) {
			$details[ $array_response['TrackResponse']['TrackInfo']['@attributes']['ID'] ] = $array_response['TrackResponse']['TrackInfo'];
		} else {
			foreach ( $array_response['TrackResponse']['TrackInfo'] as $detail ) {
				$details[ $detail['@attributes']['ID'] ] = $detail;
			}
		}

		// $array_response['TrackResponse']['TrackInfo']

		$sut = new USPS_Tracker( $container, $logger );

	}

}
