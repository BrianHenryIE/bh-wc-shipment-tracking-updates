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
 * @package           BrianHenryIE\WC_Shipment_Tracking_Updates
 *
 * @wordpress-plugin
 * Plugin Name:       Shipment Tracking Updates
 * Plugin URI:        http://github.com/BrianHenryIE/bh-wc-shipment-tracking-updates/
 * Description:       Displays the current status of the shipments' tracking inside WooCommerce. Discovers orders whose shipping label has been printed but whose package has not been mailed.
 * Version:           2.0.3
 * Author:            BrianHenryIE
 * Author URI:        http://BrianHenry.ie
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bh-wc-shipment-tracking-updates
 * Domain Path:       /languages
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\API;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\API_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Settings;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Includes\Activator;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Includes\BH_WC_Shipment_Tracking_Updates;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Includes\Deactivator;
use BrianHenryIE\WC_Shipment_Tracking_Updates\WP_Logger\Logger;


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	throw new \Exception( 'WPINC not defined. WordPress functions will not be available.' );
}

require_once plugin_dir_path( __FILE__ ) . 'autoload.php';

/**
 * Current plugin version.
 */
define( 'BH_WC_SHIPMENT_TRACKING_UPDATES_VERSION', '2.0.3' );

register_activation_hook( __FILE__, array( Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Deactivator::class, 'deactivate' ) );


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function instantiate_bh_wc_shipment_tracking_updates() {

	$settings  = new Settings();
	$logger    = Logger::instance( $settings );
	$container = new Container( $settings, $logger );
	$api       = new API( $container, $settings, $logger );

	/**
	 * The core plugin class that is used to define internationalization,
	 * admin-specific hooks, and frontend-facing site hooks.
	 */
	new BH_WC_Shipment_Tracking_Updates( $api, $settings, $logger );

	return $api;
}

/** @var API_Interface */
$GLOBALS['bh_wc_shipment_tracking_updates'] = instantiate_bh_wc_shipment_tracking_updates();

