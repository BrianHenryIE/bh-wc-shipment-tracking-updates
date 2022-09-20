<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://BrianHenryIE.com
 * @since      2.0.0
 *
 * @package    brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\Admin;

use BrianHenryIE\WC_Shipment_Tracking_Updates\Settings_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 */
class Admin {
	use LoggerAwareTrait;

	/**
	 * The plugin basename is used to find the plugin assets URL.
	 */
	protected Settings_Interface $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings_Interface $settings The plugin settings.
	 * @param LoggerInterface    $logger A PSR logger.
	 */
	public function __construct( $settings, $logger ) {

		$this->setLogger( $logger );
		$this->settings = $settings;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles(): void {

		$plugin_dir = plugin_dir_url( $this->settings->get_plugin_basename() );

		wp_enqueue_style( $this->settings->get_plugin_slug(), $plugin_dir . 'assets/bh-wc-shipment-tracking-updates-admin.css', array(), $this->settings->get_plugin_version(), 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts(): void {

		$handle = $this->settings->get_plugin_slug();

		$plugin_dir = plugin_dir_url( $this->settings->get_plugin_basename() );

		wp_enqueue_script( $handle, $plugin_dir . 'assets/bh-wc-shipment-tracking-updates-admin.js', array( 'jquery' ), $this->settings->get_plugin_version(), false );

	}

}
