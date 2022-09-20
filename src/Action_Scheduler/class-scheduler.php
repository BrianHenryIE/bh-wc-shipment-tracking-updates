<?php

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\Action_Scheduler;

use ActionScheduler;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API_Interface;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Settings_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Shipment_Tracking_Actions;

class Scheduler {

	use LoggerAwareTrait;

	const ACTION_SCHEDULER_GROUP = 'bh_wc_shipment_tracking_updates';

	const SCHEDULED_UPDATE_HOOK              = 'bh_wc_shipment_tracking_updates_scheduled_update';
	const SINGLE_UPDATE_HOOK                 = 'bh_wc_shipment_tracking_updates_single_update';
	const SCHEDULED_CHECK_PACKED_ORDERS_HOOK = 'bh_wc_shipment_tracking_updates_check_packed_orders';

	/**
	 * @see Settings::is_configured()
	 *
	 * @var Settings_Interface
	 */
	protected Settings_Interface $settings;

	protected API_Interface $api;

	public function __construct( API_Interface $api, Settings_Interface $settings, LoggerInterface $logger ) {
		$this->setLogger( $logger );
		$this->settings = $settings;
		$this->api      = $api;
	}

	/**
	 * Register a job to run every 30 minutes to check for tracking updates.
	 *
	 * @hooked init
	 * @see https://github.com/woocommerce/action-scheduler/issues/749
	 */
	public function register(): void {

		// TODO: Maybe check for two configured trackers here -- the default "no tracking number" one, plus one real
		// tracker to show the shop has configured this plugin for use.

		if ( ! class_exists( ActionScheduler::class ) || ! ActionScheduler::is_initialized() ) {
			return;
		}

		// If the shipment tracking plugin has been disabled, remove upcoming jobs.
		if ( ! class_exists( WC_Shipment_Tracking_Actions::class ) ) {
			$this->logger->warning( 'WC_Shipment_Tracking_Actions class not present. Shipment Tracking plugin presumably not active' );
			as_unschedule_all_actions( self::SCHEDULED_UPDATE_HOOK );
			as_unschedule_all_actions( self::SINGLE_UPDATE_HOOK );
			as_unschedule_all_actions( self::SCHEDULED_CHECK_PACKED_ORDERS_HOOK );
			return;
		}

		if ( false === as_next_scheduled_action( self::SCHEDULED_UPDATE_HOOK ) ) {
			as_schedule_recurring_action( time(), MINUTE_IN_SECONDS * 60, self::SCHEDULED_UPDATE_HOOK, array(), self::ACTION_SCHEDULER_GROUP );
		}

		if ( false === as_next_scheduled_action( self::SCHEDULED_CHECK_PACKED_ORDERS_HOOK ) ) {
			as_schedule_recurring_action( time(), DAY_IN_SECONDS, self::SCHEDULED_CHECK_PACKED_ORDERS_HOOK, array(), self::ACTION_SCHEDULER_GROUP );
		}
	}

	/**
	 * Every 15 minutes, this hook is called in order to update all orders.
	 *
	 * @hooked self::SCHEDULED_UPDATE_HOOK
	 */
	public function execute(): void {
		$this->api->start_background_update_jobs();
	}

	/**
	 * The previous hook pulls all order data, splits it into 'jobs', and schedules this background job.
	 *
	 * @param int[] $order_ids
	 */
	public function execute_batch( array $order_ids ): void {

		$this->api->update_orders( $order_ids );
	}

	/**
	 * Periodically check packed orders for those without tracking numbers or with unsupported tracking numbers.
	 *
	 * @see Scheduler::SCHEDULED_CHECK_PACKED_ORDERS_HOOK
	 * @hooked bh_wc_shipment_tracking_updates_check_packed_orders
	 */
	public function check_packed_orders(): void {
		$this->api->check_packed_orders();
	}
}
