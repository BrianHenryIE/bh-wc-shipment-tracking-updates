<?php
/**
 * PHPUnit bootstrap file for WP_Mock.
 *
 * @package           BrianHenryIE\WC_Shipment_Tracking_Updates
 */

WP_Mock::setUsePatchwork( true );
WP_Mock::bootstrap();

global $plugin_root_dir;
require_once $plugin_root_dir . '/autoload.php';
