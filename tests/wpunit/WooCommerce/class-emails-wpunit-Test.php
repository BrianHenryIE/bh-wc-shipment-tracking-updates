<?php
/**
 *
 *
 * @package brianhenryie/bh-wc-shipment-tracking-updates
 * @author  BrianHenryIE <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce;

use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Emails\Customer_Dispatched_Order_Email;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Emails
 */
class Emails_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::register_emails_with_woocommerce
	 */
	public function test_register_emails(): void {

		$emails = new Emails();

		$email_classes                                      = array();
		$email_classes['WC_Email_Customer_Completed_Order'] = new \WC_Email_Customer_Completed_Order();

		$result = $emails->register_emails_with_woocommerce( $email_classes );

		$this->assertArrayHasKey( 'customer_dispatched_order', $result );

		$email_class_instance = $result['customer_dispatched_order'];

		$this->assertInstanceOf( Customer_Dispatched_Order_Email::class, $email_class_instance );
	}

}
