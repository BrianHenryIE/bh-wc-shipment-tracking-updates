<?php

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce_Shipment_Tracking;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\USPS\USPS_Tracking_Details;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce_Shipment_Tracking\Order_List_Table
 */
class Order_List_Table_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Given a tracking number with an update,
	 *
	 * @covers ::append_tracking_detail_to_column
	 */
	public function test_happy_path(): void {

		$logger = new ColorLogger();

		$sut = new Order_List_Table();

		$html = '<ul><li><a href="https://tools.usps.com/go/TrackConfirmAction_input?qtc_tLabels1=9400%201234%209523%202905%207128%2000" target="_blank">9400 1234 9523 2905 7128 00</a></li></ul>';

		$tracking_items = array(
			0 =>
				array(
					'tracking_provider'        => 'USPS',
					'custom_tracking_provider' => '',
					'custom_tracking_link'     => '',
					'tracking_number'          => '9400 1234 9523 2905 7128 00',
					'date_shipped'             => '1643328000',
					'tracking_id'              => '8e1266158e24e184020a6eba042c426f',
				),
		);

		$order = new \WC_Order();

		$order_meta_bh_wc_shipment_tracking_updates = array(
			'9400123495232905712800' => new USPS_Tracking_Details(
				'9400123495232905712800',
				array(
					'Class'                => 'First-Class Package Service',
					'ClassOfMailCode'      => 'FC',
					'DestinationCity'      => 'KATY',
					'DestinationState'     => 'TX',
					'DestinationZip'       => '77494',
					'EmailEnabled'         => 'true',
					'ExpectedDeliveryDate' => 'January 31, 2022',
					'KahalaIndicator'      => 'false',
					'MailTypeCode'         => 'DM',
					'MPDATE'               => '2022-01-27 10:15:01.000000',
					'MPSUFFIX'             => '329296990',
					'OnTime'               => 'false',
					'OriginCity'           => 'SACRAMENTO',
					'OriginState'          => 'CA',
					'OriginZip'            => '95815',
					'PodEnabled'           => 'false',
					'TPodEnabled'          => 'false',
					'RestoreEnabled'       => 'false',
					'RramEnabled'          => 'false',
					'RreEnabled'           => 'false',
					'Service'              => 'USPS Tracking<SUP>&#174;</SUP>',
					'ServiceTypeCode'      => '001',
					'Status'               => 'Arrived at USPS Regional Origin Facility',
					'StatusCategory'       => 'In Transit',
					'StatusSummary'        => 'Your item arrived at our SACRAMENTO CA DISTRIBUTION CENTER origin facility on January 27, 2022 at 8:47 pm. The item is currently in transit to the destination.',
					'TABLECODE'            => 'T',
					'TrackSummary'         =>
						array(
							'EventTime'       => '8:47 pm',
							'EventDate'       => 'January 27, 2022',
							'Event'           => 'Arrived at USPS Regional Origin Facility',
							'EventCity'       => 'SACRAMENTO CA DISTRIBUTION CENTER',
							'EventState'      => '',
							'EventZIPCode'    => '',
							'EventCountry'    => '',
							'FirmName'        => '',
							'Name'            => '',
							'AuthorizedAgent' => 'false',
							'EventCode'       => '10',
						),
					'TrackDetail'          =>
						array(
							0 =>
								array(
									'EventTime'       => '7:32 pm',
									'EventDate'       => 'January 27, 2022',
									'Event'           => 'Accepted at USPS Origin Facility',
									'EventCity'       => 'SACRAMENTO',
									'EventState'      => 'CA',
									'EventZIPCode'    => '95815',
									'EventCountry'    => '',
									'FirmName'        => '',
									'Name'            => '',
									'AuthorizedAgent' => 'false',
									'EventCode'       => 'OA',
								),
							1 =>
								array(
									'EventTime'       => '4:09 pm',
									'EventDate'       => 'January 27, 2022',
									'Event'           => 'Shipping Label Created, USPS Awaiting Item',
									'EventCity'       => 'SACRAMENTO',
									'EventState'      => 'CA',
									'EventZIPCode'    => '95815',
									'EventCountry'    => '',
									'FirmName'        => '',
									'Name'            => '',
									'AuthorizedAgent' => 'false',
									'EventCode'       => 'GX',
									'DeliveryAttributeCode' => '33',
								),
						),
					'@attributes'          =>
						array(
							'ID' => '9400136895232905712800',
						),
				),
				$logger
			),
		);

		$order->add_meta_data( 'bh_wc_shipment_tracking_updates', $order_meta_bh_wc_shipment_tracking_updates );
		$order_id = $order->save();

		$result = $sut->append_tracking_detail_to_column( $html, $order_id, $tracking_items );

		$this->assertStringContainsString( 'Expected delivery:', $result );
		$this->assertStringContainsString( 'Monday, 31-Jan', $result );

	}

}
