<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\Frontend;

use BrianHenryIE\WC_Shipment_Tracking_Updates\Settings_Interface;

/**
 * The public-facing functionality of the plugin.
 */
class Frontend {

	/**
	 * The plugin basename is used to find the plugin assets URL.
	 *
	 * @var Settings_Interface
	 */
	protected Settings_Interface $settings;

	public function __construct( Settings_Interface $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Register the stylesheets for the frontend-facing side of the site.
	 *
	 * @hooked wp_enqueue_scripts
	 */
	public function enqueue_styles(): void {
		$version = $this->settings->get_plugin_version();

		$plugin_dir = plugin_dir_url( $this->settings->get_plugin_basename() );

		wp_enqueue_style( 'bh-wc-shipment-tracking-updates', $plugin_dir . 'assets/bh-wc-shipment-tracking-updates-frontend.css', array(), $version, 'all' );

	}

	/**
	 * Register the JavaScript for the frontend-facing side of the site.
	 *
	 * @hooked wp_enqueue_scripts
	 */
	public function enqueue_scripts(): void {
		$version = $this->settings->get_plugin_version();

		$plugin_dir = plugin_dir_url( $this->settings->get_plugin_basename() );

		wp_enqueue_script( 'bh-wc-shipment-tracking-updates', $plugin_dir . 'assets/bh-wc-shipment-tracking-updates-frontend.js', array( 'jquery' ), $version, false );

	}

}
