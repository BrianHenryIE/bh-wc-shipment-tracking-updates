<?php
/**
 * Register new emails with WooCommerce.
 *
 * * Customer order dispatched email
 * * TODO: Customer order delivered email
 * * TODO: Conditionally send the default "order complete" email
 * * TODO: Order returning email
 *
 * @package brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce;

use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Emails\Customer_Dispatched_Order_Email;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Emails\Customer_Packed_Order_Email;
use WC_Email;

/**
 * Add this plugin's emails to WooCommerce's list of emails as WC_Emails is init'd.
 */
class Emails {

	/**
	 * Registers the plugins' emails with WooCommerce, i.e. in the list displayed at
	 * WooCommerce / Settings / Emails.
	 *
	 * @see wp-admin/admin.php?page=wc-settings&tab=email
	 *
	 * @hooked woocommerce_email_classes
	 * @see WC_Emails::init()
	 *
	 * @param array<string, WC_Email> $email_classes The list of emails registered with WooCommerce.
	 * @return array<string, WC_Email>
	 */
	public function register_emails_with_woocommerce( array $email_classes ): array {

		$updated_email_classes = array();

		foreach ( $email_classes as $key => $value ) {
			if ( 'WC_Email_Customer_Completed_Order' === $key ) {
				$updated_email_classes['customer_packed_order']     = new Customer_Packed_Order_Email();
				$updated_email_classes['customer_dispatched_order'] = new Customer_Dispatched_Order_Email();
			}
			$updated_email_classes[ $key ] = $value;
		}

		return $updated_email_classes;
	}

}
