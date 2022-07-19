<?php
/**
 * The plugin page output of the plugin.
 *
 * @since      2.0.0
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 *
 * @package    brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\Admin;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Settings_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * This class adds a `Settings` link on the plugins.php page.
 *
 * A logs link is separately added by the bh-wp-logger library.
 *
 * @see \BrianHenryIE\WP_Logger\Admin\Plugins_Page
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
	 * @param array<int|string, string> $action_links The existing plugin links (usually "Deactivate").
	 * @param ?string                   $_plugin_basename The plugin's directory/filename.php.
	 * @param ?array<int|string, mixed> $_plugin_data An array of plugin data. See `get_plugin_data()`.
	 * @param ?string                   $_context     The plugin context. 'all'|'active'|'inactive'|'recently_activated'
	 *                                               |'upgrade'|'mustuse'|'dropins'|'search'.
	 *
	 * @return array<int|string, string> The links to display below the plugin name on plugins.php.
	 */
	public function action_links( array $action_links, ?string $_plugin_basename, ?array $_plugin_data, ?string $_context ): array {

		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			return $action_links;
		}

		$settings_url = admin_url( '/admin.php?page=wc-settings&tab=shipping&section=' . $this->settings->get_plugin_slug() );
		array_unshift( $action_links, '<a href="' . $settings_url . '">Settings</a>' );

		return $action_links;
	}

}
