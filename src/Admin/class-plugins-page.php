<?php
/**
 * The plugin page output of the plugin.
 *
 * @since      2.0.0
 *
 * @package    BrianHenryIE\WC_Shipment_Tracking_Updates
 * @subpackage BrianHenryIE\WC_Shipment_Tracking_Updates/admin
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\Admin;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Settings_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * This class adds a `Settings` link on the plugins.php page.
 *
 * @package    BrianHenryIE\WC_Shipment_Tracking_Updates
 * @subpackage BrianHenryIE\WC_Shipment_Tracking_Updates/admin
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */
class Plugins_Page {

	use LoggerAwareTrait;

	/**
	 * The plugin's settings.
	 *
	 * @see Settings_Interface::get_plugin_slug()
	 * @var Settings_Interface
	 */
	protected $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings_Interface $settings The plugin's settings.
	 * @param LoggerInterface    $logger PRS logger.
	 */
	public function __construct( Settings_Interface $settings, LoggerInterface $logger ) {

		$this->setLogger( $logger );
		$this->settings = $settings;
	}

	/**
	 * Add link to Settings page in plugins.php list.
	 *
	 * @hooked plugin_action_links_{basename}
	 *
	 * @param array<int|string, string> $links_array The existing plugin links (usually "Deactivate"). May or may not be indexed with a string.
	 *
	 * @return array<int|string, string> The links to display below the plugin name on plugins.php.
	 */
	public function action_links( array $links_array ): array {

		$settings_url = admin_url( '/admin.php?page=wc-settings&tab=shipping&section=' . $this->settings->get_plugin_slug() );
		array_unshift( $links_array, '<a href="' . $settings_url . '">Settings</a>' );

		return $links_array;
	}

	// TODO: Add external link to USPS tracking... if USPS is configured.

}
