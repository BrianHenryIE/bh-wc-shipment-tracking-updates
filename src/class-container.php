<?php
/**
 * Create and return objects for fetching and processing data.
 *
 * @package     brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Settings_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\Tracker_Settings_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\USPS\USPS_Settings;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\USPS\USPS_Settings_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\USPS\USPS_Tracker;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\USPS\WP_USPS_TrackConfirm_API;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Geolocation;

/**
 * Is a container a factory?
 */
class Container implements ContainerInterface {

	use LoggerAwareTrait;

	/**
	 * The settings that are passed to objects instantiated by this container.
	 *
	 * @var Settings_Interface
	 */
	protected Settings_Interface $settings;

	const USPS_SHIPMENT_TRACKER  = 'usps_shipment_tracker';
	const USPS_TRACK_CONFIRM_API = 'usps_track_confirm_api';

	/**
	 * The types of items this container can return.
	 *
	 * @var string[]
	 */
	protected array $has_items = array( self::USPS_SHIPMENT_TRACKER, self::USPS_TRACK_CONFIRM_API );

	/**
	 * Constructor.
	 *
	 * @param Settings_Interface $settings The plugin settings required by some of the created classes.
	 * @param LoggerInterface    $logger PSR logger for the plugin.
	 */
	public function __construct( Settings_Interface $settings, LoggerInterface $logger ) {
		$this->setLogger( $logger );
		$this->settings = $settings;
	}

	/**
	 * Finds an entry of the container by its identifier and returns it.
	 *
	 * phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber
	 *
	 * @param string $id Identifier of the entry to look for.
	 * @return mixed Entry.
	 * @throws ContainerExceptionInterface Error while retrieving the entry.
	 * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
	 */
	public function get( $id ) {

		switch ( $id ) {
			case self::USPS_SHIPMENT_TRACKER:
				return new USPS_Tracker( $this, $this->logger );

			case self::USPS_TRACK_CONFIRM_API:
				/** @var ?USPS_Settings $usps_settings */
				$usps_settings = $this->settings->get_tracker_settings( 'USPS' );

				if ( is_null( $usps_settings ) || ! $usps_settings->is_configured() ) {
					throw new class() extends Exception implements ContainerExceptionInterface{
						/**
						 * @var string $message
						 */
						protected $message = 'USPS settings not configured';
					};
				}
				/** @var string $username Tracker_Settings_Interface::is_configured() has shown this is not null. */
				$username          = $usps_settings->get_usps_username();
				$track_confirm_api = new WP_USPS_TrackConfirm_API( $username );
				/** @var string $source_id Tracker_Settings_Interface::is_configured() has shown this is not null. */
				$source_id = $usps_settings->get_usps_source_id();
				$track_confirm_api->setSourceId( $source_id );
				$track_confirm_api->setClientIp( WC_Geolocation::get_external_ip_address() );
				$track_confirm_api->setLogger( $this->logger );

				return $track_confirm_api;

			default:
				throw new class() extends Exception implements NotFoundExceptionInterface{};
		}

	}

	/**
	 * Returns true if the container can return an entry for the given identifier.
	 * Returns false otherwise.
	 *
	 * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
	 * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @return bool
	 */
	public function has( $id ) {
		return in_array( $id, $this->has_items, true );
	}
}
