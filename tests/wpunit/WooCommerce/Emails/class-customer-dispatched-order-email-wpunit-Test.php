<?php

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Emails;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Emails\Customer_Dispatched_Order_Email
 */
class Customer_Dispatched_Order_Email_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * The template was being searched for in plugins/woocommerce/templates/emails/customer-dispatched-order.php
	 *
	 * @covers ::get_content_plain
	 */
	public function test_plain_template_loads(): void {

		$wc_emails = new \WC_Emails();
		$wc_emails->init();

		$order = new \WC_Order();
		$order->save();

		$sut         = new Customer_Dispatched_Order_Email();
		$sut->object = $order;

		$e = null;
		try {
			$result = $sut->get_content_plain();
			$this->assertStringContainsString( 'We have finished processing your order.', $result );

		} catch ( \Exception $exception ) {
			$e = $exception;
		}

		$this->assertNull( $e );
	}


	/**
	 * As above but for the html template.
	 *
	 * @covers ::get_content_html
	 */
	public function test_html_template_loads(): void {

		$wc_emails = new \WC_Emails();
		$wc_emails->init();

		$order = new \WC_Order();
		$order->save();

		$sut         = new Customer_Dispatched_Order_Email();
		$sut->object = $order;

		$e = null;
		try {
			$result = $sut->get_content_html();
			$this->assertStringContainsString( 'We have finished processing your order.', $result );

		} catch ( \Exception $exception ) {
			$e = $exception;
		}

		$this->assertNull( $e );
	}

}
