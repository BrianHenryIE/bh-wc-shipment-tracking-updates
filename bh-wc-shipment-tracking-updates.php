<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           brianhenryie/bh-wc-shipment-tracking-updates
 *
 * @wordpress-plugin
 * Plugin Name:       Shipment Tracking Updates
 * Plugin URI:        http://github.com/BrianHenryIE/bh-wc-shipment-tracking-updates/
 * Description:       Displays the current status of the shipments' tracking inside WooCommerce. Discovers orders whose shipping label has been printed but whose package has not been mailed.
 * Version:           2.9.0
 * Requires PHP:      7.4
 * Author:            BrianHenryIE
 * Author URI:        http://BrianHenry.ie
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bh-wc-shipment-tracking-updates
 * Domain Path:       /languages
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates;

use BH_WC_Shipment_Tracking_Updates_SLSWC_Client;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\API;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Settings;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Includes\Activator;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Includes\Deactivator;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\API\BH_WP_PSR_Logger;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\Logger;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\Logger_Settings_Interface;


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	throw new \Exception( 'WPINC not defined. WordPress functions will not be available.' );
}

require_once plugin_dir_path( __FILE__ ) . 'autoload.php';

/**
 * Current plugin version.
 */
define( 'BH_WC_SHIPMENT_TRACKING_UPDATES_VERSION', '2.9.0' );

define( 'BH_WC_SHIPMENT_TRACKING_UPDATES_BASENAME', plugin_basename( __FILE__ ) );


register_activation_hook( __FILE__, array( Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Deactivator::class, 'deactivate' ) );


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    2.0.0
 */
function instantiate_bh_wc_shipment_tracking_updates(): API {

	$settings  = new Settings();
	$logger    = Logger::instance( $settings );
	$container = new Container( $settings, $logger );
	$api       = new API( $container, $settings, $logger );

	/**
	 * The core plugin class that is used to define internationalization,
	 * admin-specific hooks, and frontend-facing site hooks.
	 */
	new BH_WC_Shipment_Tracking_Updates( $api, $settings, $logger );

	if ( class_exists( BH_WC_Shipment_Tracking_Updates_SLSWC_Client::class ) ) {
		BH_WC_Shipment_Tracking_Updates_SLSWC_Client::get_instance( 'https://bhwp.ie/', __FILE__ );
		/**
		 * Silence Licence Server errors.
		 *
		 * @pararm array{level:string,message:string,context:array} $log_data
		 *
		 * @param Logger_Settings_Interface $settings
		 * @param BH_WP_PSR_Logger $bh_wp_psr_logger
		 */
		add_filter(
			$settings->get_plugin_slug() . '_bh_wp_logger_log',
			function ( array $log_data, Logger_Settings_Interface $settings, BH_WP_PSR_Logger $bh_wp_psr_logger ) {
				if ( isset( $log_data['context']['file'] ) && false !== strpos( $log_data['context']['file'], 'licenseserver' ) ) {
					return null;
				}

				return $log_data;
			},
			10,
			3
		);
	}

	return $api;
}

/**
 * The instantiated plugin API class, containing the main plugin functions.
 *
 * @var API_Interface $GLOBALS['bh_wc_shipment_tracking_updates']
 */
$GLOBALS['bh_wc_shipment_tracking_updates'] = instantiate_bh_wc_shipment_tracking_updates();

