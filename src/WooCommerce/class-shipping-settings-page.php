<?php
/**
 * Settings UI.
 *
 * Add configuration options in WooCommere/Settings/Shipping/Shipment Tracking Updates.
 *
 * @link       https://BrianHenryIE.com
 * @since      2.0.0
 *
 * @package    BrianHenryIE\WC_Shipment_Tracking_Updates
 *
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Settings;
use Psr\Log\LogLevel;

/**
 * @see \WC_Settings_Page
 */
class Shipping_Settings_Page {

	/**
	 * @hooked woocommerce_get_sections_shipping
	 *
	 * @param array<string, string> $sections
	 * @return array<string, string>
	 */
	public function shipment_tracking_updates_section( array $sections ): array {

		$sections['bh-wc-shipment-tracking-updates'] = __( 'Shipment Tracking Updates', 'bh-wc-shipment-tracking-updates' );
		return $sections;
	}

	/**
	 *
	 * @hooked woocommerce_get_settings_shipping
	 * @see \WC_Settings_Page::get_settings_for_section()
	 *
	 * @param array<string, array> $settings
	 * @param string               $current_section
	 * @return array<string, array>
	 */
	public function shipment_tracking_updates_settings( array $settings, string $current_section ) {

		/**
		 * Check the current section is what we want.
		 */
		if ( 'bh-wc-shipment-tracking-updates' !== $current_section ) {
			return $settings;
		}

		// Add Title to the Settings.
		$settings['bh-wc-shipment-tracking-updates'] = array(
			'name' => __( 'Shipment Tracking Updates', 'bh-wc-shipment-tracking-updates' ),
			'type' => 'title',
			'desc' => __( 'Get a free USPS API key at <a target="_blank" href="https://registration.shippingapis.com/">registration.shippingapis.com</a>.', 'bh-wc-shipment-tracking-updates' ),
			'id'   => 'bh-wc-shipment-tracking-updates',
		);

		$settings[ Settings::USPS_USER_ID_OPTION ] = array(
			'title'   => __( 'USPS API User Id', 'bh-wc-shipment-tracking-updates' ),
			'type'    => 'text',
			'desc'    => __( 'Enter your API Key. You can find this in "User Profile" drop-down (top right corner) > API Keys.', 'bh-wc-shipment-tracking-updates' ),
			'default' => '',
			'id'      => Settings::USPS_USER_ID_OPTION,
		);

		$settings[ Settings::USPS_SOURCE_ID_OPTION ] = array(
			'title'   => __( 'USPS Source Id', 'bh-wc-shipment-tracking-updates' ),
			'type'    => 'text',
			'desc'    => __( 'USPS requires the company name (not necessarily email address).', 'bh-wc-shipment-tracking-updates' ),
			'default' => get_option( 'admin_email' ),
			'id'      => Settings::USPS_SOURCE_ID_OPTION,
		);

		$paid_statuses      = array();
		$order_status_names = wc_get_order_statuses();
		foreach ( wc_get_is_paid_statuses() as $status ) {
			if ( isset( $order_status_names[ "wc-{$status}" ] ) ) {
				$paid_statuses[ $status ] = $order_status_names[ "wc-{$status}" ];
			}
		}

		// If 'shippingpurchased', 'printed' exist on the site, they will be chosen by default.
		$settings[ Settings::ORDER_STATUSES_TO_WATCH_OPTION ] = array(
			'name'    => __( 'Order statuses to watch for tracking updates', 'bh-wc-shipment-tracking-updates' ),
			'desc'    => __( 'Order statuses to watch', 'bh-wc-shipment-tracking-updates' ),
			'id'      => Settings::ORDER_STATUSES_TO_WATCH_OPTION,
			'type'    => 'multiselect',
			'class'   => 'chosen_select',
			'default' => array( 'shippingpurchased', 'printed', 'packing', 'packed', 'in-transit', 'returning' ),
			'options' => $paid_statuses,
		);

		// $settings['mark_overseas_two_weeks_complete'] = array(
		// 'title'       => __( 'Auto-completion', 'bh-wc-shipment-tracking-updates' ),
		// 'type'        => 'checkbox',
		// 'label'       => __( 'Mark overseas orders with no shipping updates for two weeks as complete', 'bh-wc-shipment-tracking-updates' ),
		// 'default'     => 'yes',
		// 'description' => __( "The tracking numbers can be manually searched in the local country's postal service's website.", 'bh-wc-shipment-tracking-updates' ),
		// );

		$log_levels        = array(
			'none',
			LogLevel::ERROR,
			LogLevel::WARNING,
			LogLevel::NOTICE,
			LogLevel::INFO,
			LogLevel::DEBUG,
		);
		$log_levels_option = array();
		foreach ( $log_levels as $log_level ) {
			$log_levels_option[ $log_level ] = ucfirst( $log_level );
		}

		$settings[] = array(
			'title'   => __( 'Log Level', 'text-domain' ),
			'label'   => __( 'Enable Logging', 'text-domain' ),
			'type'    => 'select',
			'options' => $log_levels_option,
			'desc'    => __( 'Increasingly detailed levels of logs. ', 'text-domain' ) . '<a href="' . admin_url( 'admin.php?page=bh-wc-shipment-tracking-updates-logs' ) . '">View Logs</a>',
			'default' => LogLevel::INFO,
			'id'      => Settings::LOG_LEVEL_OPTION,
		);

		// This is needed so the "Save changes" button goes to the bottom.
		$settings[] = array(
			'type' => 'sectionend',
			'id'   => 'bh-wc-auto-print-shipping-labels-receipts',
		);

		return $settings;

	}
}
