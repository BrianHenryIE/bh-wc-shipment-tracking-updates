<?php
/**
 * Define the email to send when an order is packed.
 *
 * Triggered when an order status changes to packed.
 * Does not send if the order packed (wc-packed) email has already been sent.
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
 * Add action on `woocommerce_order_status_packed_notification`.
 */
class Customer_Packed_Order_Email extends WC_Email {
	const EMAIL_SENT_META_KEY = 'bh_wc_shipment_tracking_updates_customer_packed_order_email_sent';

	/**
	 * Customer_Packed_Order_Email constructor.
	 */
	public function __construct() {
		$this->id             = 'customer_packed_order';
		$this->customer_email = true;
		$this->title          = __( 'Packed order', 'woocommerce' );
		$this->description    = __( 'Packed order emails are sent to customers when their orders are marked \'packed\' to indicate that their orders have been shipped.', 'woocommerce' );
		$this->template_base  = WP_PLUGIN_DIR . '/bh-wc-shipment-tracking-updates/templates/';
		$this->template_html  = 'emails/customer-packed-order.php';
		$this->template_plain = 'emails/plain/customer-packed-order.php';
		$this->placeholders   = array(
			'{order_date}'   => '',
			'{order_number}' => '',
		);

		// Triggers for this email.
		add_action( 'woocommerce_order_status_packed', array( $this, 'trigger' ), 10, 2 );
		add_action( 'bh_wc_shipment_tracking_updates_packed_email', array( $this, 'trigger' ), 10, 2 );

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

		if ( ! ( $order instanceof WC_Order ) ) {
			$this->restore_locale();
			return;
		}

		$email_already_sent = $order->get_meta( self::EMAIL_SENT_META_KEY );
		if ( wc_string_to_bool( $email_already_sent ) ) {
			$this->restore_locale();
			return;
		}

		$dispatched_email_already_sent = $order->get_meta( Customer_Dispatched_Order_Email::EMAIL_SENT_META_KEY );
		if ( wc_string_to_bool( $dispatched_email_already_sent ) ) {
			$this->restore_locale();
			return;
		}

		$this->object                         = $order;
		$date_created                         = $order->get_date_created();
		$this->recipient                      = $order->get_billing_email();
		$this->placeholders['{order_date}']   = is_null( $date_created ) ? '' : wc_format_datetime( $date_created );
		$this->placeholders['{order_number}'] = $order->get_order_number();

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$success = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			if ( $success ) {
				$order->add_meta_data( self::EMAIL_SENT_META_KEY, 'yes', true );
				$order->save();
			}
		}

		$this->restore_locale();
	}

	/**
	 * Get email subject.
	 *
	 * @return string
	 */
	public function get_default_subject(): string {
		return __( 'Your {site_title} order is packed and ready to send', 'woocommerce' );
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
			),
			'',
			$this->template_base
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
			),
			'',
			$this->template_base
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
