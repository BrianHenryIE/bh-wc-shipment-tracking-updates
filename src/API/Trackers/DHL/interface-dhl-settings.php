<?php
/**
 *
 *
 * @package     brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\DHL;

interface DHL_Settings_Interface {

	public function get_consumer_api_key(): ?string;
}
