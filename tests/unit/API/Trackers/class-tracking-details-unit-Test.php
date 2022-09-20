<?php

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers;

use DateTime;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\Tracking_Details_Abstract
 */
class Tracking_Details_Unit_Test extends \Codeception\Test\Unit {

	/**
	 * Fix: Typed property must not be accessed before initialization
	 *
	 * @covers ::get_carrier_status
	 */
	public function test_nullable_carrier_status(): void {

		$sut = new class() extends Tracking_Details_Abstract {

			public function is_dispatched(): bool {
				return false;
			}

			public function get_equivalent_order_status(): ?string {
				return null;
			}

			public function get_delivery_time(): ?DateTime {
				return null;
			}
		};

		$result = $sut->get_carrier_status();

		$this->assertNull( $result );

	}

	/**
	 * Fix: Typed property must not be accessed before initialization
	 *
	 * @covers ::get_carrier_summary
	 */
	public function test_nullable_carrier_summary(): void {

		$sut = new class() extends Tracking_Details_Abstract {

			public function is_dispatched(): bool {
				return false;
			}

			public function get_equivalent_order_status(): ?string {
				return null;
			}

			public function get_delivery_time(): ?DateTime {
				return null;
			}
		};

		$result = $sut->get_carrier_summary();

		$this->assertNull( $result );

	}

}
