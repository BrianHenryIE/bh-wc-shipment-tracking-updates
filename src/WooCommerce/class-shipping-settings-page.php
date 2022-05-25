<?php
/**
 * Settings UI.
 *
 * Add configuration options in WooCommere/Settings/Shipping/Shipment Tracking Updates.
 *
 * @link       https://BrianHenryIE.com
 * @since      2.0.0
 *
 * @package    brianhenryie/bh-wc-shipment-tracking-updates
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Settings;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\DHL\DHL_Settings;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\UPS\UPS_Settings;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\USPS\USPS_Settings;
use Psr\Log\LogLevel;

/**
 * Register and output the settings page.
 *
 * @see \WC_Settings_Page
 * @see \WC_Settings_API
 */
class Shipping_Settings_Page {

	/**
	 * Add the 'Shipment Tracking Updates' section to WooCommerce / Settings / Shipping.
	 *
	 * @hooked woocommerce_get_sections_shipping
	 *
	 * @param array<string, string> $sections The existing sections.
	 * @return array<string, string>
	 */
	public function shipment_tracking_updates_section( array $sections ): array {

		$sections['bh-wc-shipment-tracking-updates'] = __( 'Shipment Tracking Updates', 'bh-wc-shipment-tracking-updates' );
		return $sections;
	}

	/**
	 * Check the current section is what we want, then add the settings.
	 *
	 * @hooked woocommerce_get_settings_shipping
	 * @see \WC_Settings_Page::get_settings_for_section()
	 *
	 * @param array<int|string, array> $settings Already defined settings.
	 * @param string                   $current_section The section slug.
	 * @return array<int|string, array>
	 */
	public function shipment_tracking_updates_settings( array $settings, string $current_section ) {

		if ( 'bh-wc-shipment-tracking-updates' !== $current_section ) {
			return $settings;
		}

		// Add Title to the Settings.
		$settings['bh_wc_shipment_tracking_updates_title'] = array(
			'name' => __( 'Shipment Tracking Updates', 'bh-wc-shipment-tracking-updates' ),
			'type' => 'title',
			'desc' => __( 'Get a free USPS API key at ', 'bh-wc-shipment-tracking-updates' ) . '<a target="_blank" href="https://registration.shippingapis.com/">registration.shippingapis.com</a>.',
			'id'   => 'bh-wc-shipment-tracking-updates',
		);

		$settings[ USPS_Settings::USPS_USER_ID_OPTION ] = array(
			'title'   => __( 'USPS API User Id', 'bh-wc-shipment-tracking-updates' ),
			'type'    => 'text',
			'desc'    => __( 'Enter your API Key. You can find this in "User Profile" drop-down (top right corner) > API Keys.', 'bh-wc-shipment-tracking-updates' ),
			'default' => '',
			'id'      => USPS_Settings::USPS_USER_ID_OPTION,
		);

		$settings[ USPS_Settings::USPS_SOURCE_ID_OPTION ] = array(
			'title'   => __( 'USPS Source Id', 'bh-wc-shipment-tracking-updates' ),
			'type'    => 'text',
			'desc'    => __( 'USPS requires the company name (not necessarily email address).', 'bh-wc-shipment-tracking-updates' ),
			'default' => get_option( 'admin_email' ),
			'id'      => USPS_Settings::USPS_SOURCE_ID_OPTION,
		);

		$settings[ DHL_Settings::DHL_CONSUMER_API_KEY_OPTION_NAME ] = array(
			'title' => __( 'DHL Consumer API Key', 'bh-wc-shipment-tracking-updates' ),
			'type'  => 'text',
			'desc'  => __( 'https://developer.dhl.com/', 'bh-wc-shipment-tracking-updates' ),
			'id'    => DHL_Settings::DHL_CONSUMER_API_KEY_OPTION_NAME,
		);

		$settings[ UPS_Settings::UPS_USER_ID_OPTION_NAME ] = array(
			'title' => __( 'UPS User Id', 'bh-wc-shipment-tracking-updates' ),
			'type'  => 'text',
			'desc'  => __( 'https://www.ups.com/upsdeveloperkit?loc=en_US', 'bh-wc-shipment-tracking-updates' ),
			'id'    => UPS_Settings::UPS_USER_ID_OPTION_NAME,
		);

		$settings[ UPS_Settings::UPS_PASSWORD_OPTION_NAME ] = array(
			'title' => __( 'UPS User Password', 'bh-wc-shipment-tracking-updates' ),
			'type'  => 'password',
			'id'    => UPS_Settings::UPS_PASSWORD_OPTION_NAME,
		);

		$settings[ UPS_Settings::UPS_ACCESS_KEY_OPTION_NAME ] = array(
			'title' => __( 'UPS Access Key', 'bh-wc-shipment-tracking-updates' ),
			'type'  => 'text',
			'id'    => UPS_Settings::UPS_ACCESS_KEY_OPTION_NAME,
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

		$settings['bh_wc_shipment_tracking_updates_emails_link'] = array(
			'title' => __( 'Emails', 'bh-wc-shipment-tracking-updates' ),
			'desc'  => __( 'Configure emails for dispatched orders on the ', 'bh-wc-shipment-tracking-updates' ) . '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=email' ) . '">' . __( 'WooCommerce / Settings / Emails tab', 'bh-wc-shipment-tracking-updates' ) . '</a>.',
			'type'  => 'bh_wc_shipment_tracking_updates_text_html',
		);

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

		$settings['bh_wc_shipment_tracking_updates_log_level'] = array(
			'title'   => __( 'Log Level', 'bh-wc-shipment-tracking-updates' ),
			'label'   => __( 'Enable Logging', 'bh-wc-shipment-tracking-updates' ),
			'type'    => 'select',
			'options' => $log_levels_option,
			'desc'    => __( 'Increasingly detailed levels of logs. ', 'bh-wc-shipment-tracking-updates' ) . '<a href="' . admin_url( 'admin.php?page=bh-wc-shipment-tracking-updates-logs' ) . '">View Logs</a>',
			'default' => LogLevel::INFO,
			'id'      => Settings::LOG_LEVEL_OPTION,
		);

		// This is needed so the "Save changes" button goes to the bottom.
		$settings[] = array(
			'type' => 'sectionend',
		);

		return $settings;
	}

	/**
	 * Print plain text output (i.e. no input).
	 *
	 * Used to link to the Emails settings page.
	 *
	 * @see WC_Settings_API::generate_text_html()
	 * @hooked woocommerce_admin_field_bh_wc_shipment_tracking_updates_text_html
	 *
	 * @param array<string, mixed> $data Field data.
	 * @since  2.1.0
	 */
	public function print_text_output( array $data ): void {

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<?php echo wp_kses_post( $data['title'] ); ?>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<?php echo wp_kses_post( $data['desc'] ); ?>
				</fieldset>
			</td>
		</tr>
		<?php

		$output = ob_get_clean();

		if ( false === $output ) {
			// TODO: Log error.
			return;
		}

		$allowed_html = wp_kses_allowed_html( 'post' );

		echo wp_kses( $output, $allowed_html );
	}
}
