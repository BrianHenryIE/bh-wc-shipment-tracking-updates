<?php
/**
 * Define the email to send when an order is dispatched.
 *
 * Triggered when an order status changes to in-transit.
 *
 * TODO: Add expected delivery date.
 *
 * @see WC_Email_Customer_Completed_Order
 *
 * @package brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Emails;

use WC_Email;
use WC_Order;

/**
 * Configure.
 * Add action on `woocommerce_order_status_in-transit_notification`.
 */
class Customer_Dispatched_Order_Email extends WC_Email {

	/**
	 * Customer_Dispatched_Order_Email constructor.
	 */
	public function __construct() {
		$this->id             = 'customer_dispatched_order';
		$this->customer_email = true;
		$this->title          = __( 'Dispatched order', 'woocommerce' );
		$this->description    = __( 'Dispatched order emails are sent to customers when their orders are marked \'in transit\' to indicate that their orders have been shipped.', 'woocommerce' );
		$this->template_base  = WP_PLUGIN_DIR . '/bh-wc-shipment-tracking-updates/templates/';
		$this->template_html  = 'emails/customer-dispatched-order.php';
		$this->template_plain = 'emails/plain/customer-dispatched-order.php';
		$this->placeholders   = array(
			'{order_date}'   => '',
			'{order_number}' => '',
		);

		// Triggers for this email.
		add_action( 'bh_wc_shipment_tracking_updates_in-transit_email', array( $this, 'trigger' ), 10, 2 );

		// Call parent constructor.
		parent::__construct();
	}


	/**
	 * Trigger the sending of this email.
	 *
	 * @param int            $order_id The order ID.
	 * @param WC_Order|false $order Order object.
	 */
	public function trigger( $order_id, $order = false ): void {
		$this->setup_locale();

		if ( $order_id && ! ( $order instanceof WC_Order ) ) {
			$order = wc_get_order( $order_id );
		}

		if ( $order instanceof WC_Order ) {
			$this->object                         = $order;
			$date_created                         = $this->object->get_date_created(); // TODO: This could be false|null?
			$this->recipient                      = $this->object->get_billing_email();
			$this->placeholders['{order_date}']   = wc_format_datetime( $date_created );
			$this->placeholders['{order_number}'] = $this->object->get_order_number();
		}

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

	/**
	 * Get email subject.
	 *
	 * @return string
	 */
	public function get_default_subject(): string {
		return __( 'Your {site_title} order has been dispatched', 'woocommerce' );
	}

	/**
	 * Get email heading.
	 *
	 * @return string
	 */
	public function get_default_heading(): string {
		return __( 'Thanks for shopping with us', 'woocommerce' );
	}

	/**
	 * Get content html.
	 *
	 * @return string
	 */
	public function get_content_html(): string {
		return wc_get_template_html(
			$this->template_html,
			array(
				'order'              => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => false,
				'email'              => $this,
			)
		);
	}

	/**
	 * Get content plain.
	 *
	 * @return string
	 */
	public function get_content_plain(): string {
		return wc_get_template_html(
			$this->template_plain,
			array(
				'order'              => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => true,
				'email'              => $this,
			)
		);
	}

	/**
	 * Default content to show below main email content.
	 *
	 * @return string
	 */
	public function get_default_additional_content(): string {
		return __( 'Thanks for shopping with us.', 'woocommerce' );
	}
}
