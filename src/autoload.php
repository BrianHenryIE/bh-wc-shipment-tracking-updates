<?php
/**
 * Loads all required classes
 *
 * Uses classmap, PSR4 & wp-namespace-autoloader.
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           BrianHenryIE\WC_Shipment_Tracking_Updates
 *
 * @see https://github.com/pablo-sg-pacheco/wp-namespace-autoloader/
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates;

use BrianHenryIE\WC_Shipment_Tracking_Updates\Pablo_Pacheco\WP_Namespace_Autoloader\WP_Namespace_Autoloader;

$class_map_file = __DIR__ . '/autoload-classmap.php';
if ( file_exists( $class_map_file ) ) {

	$class_map = include $class_map_file;

	if ( is_array( $class_map ) ) {
		spl_autoload_register(
			function ( $classname ) use ( $class_map ) {

				if ( array_key_exists( $classname, $class_map ) && file_exists( $class_map[ $classname ] ) ) {
					require_once $class_map[ $classname ];
				}
			}
		);
	}
	unset( $class_map_file );
}

require_once __DIR__ . '/strauss/autoload.php';

$wpcs_autoloader = new WP_Namespace_Autoloader();
$wpcs_autoloader->init();

// After refactoring, deserializing from post meta was causing errors.
class_alias( \BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\USPS\USPS_Tracking_Details::class, 'BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\USPS_Tracking_Details' );
