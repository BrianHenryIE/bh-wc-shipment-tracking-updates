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

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Settings_Interface;
use Psr\Log\LoggerInterface;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 */
class Admin {

	/** @var LoggerInterface  */
	protected $logger;

	/** @var Settings_Interface  */
	protected $settings;

	/**
	 *
	 *
	 * @param Settings_Interface $settings
	 * @param LoggerInterface    $logger
	 */
	public function __construct( $settings, $logger ) {

		$this->logger   = $logger;
		$this->settings = $settings;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles(): void {

		wp_enqueue_style( $this->settings->get_plugin_slug(), plugin_dir_url( __FILE__ ) . 'css/bh-wc-shipment-tracking-updates-admin.css', array(), $this->settings->get_plugin_version(), 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts(): void {

		$handle = $this->settings->get_plugin_slug();

		wp_enqueue_script( $handle, plugin_dir_url( __FILE__ ) . 'js/bh-wc-shipment-tracking-updates-admin.js', array( 'jquery' ), $this->settings->get_plugin_version(), false );

	}

}
