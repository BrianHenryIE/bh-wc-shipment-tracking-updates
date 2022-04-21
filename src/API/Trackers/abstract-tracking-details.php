<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Common format of the tracking details.
 *
 * Represents one tracking number.
 *
 * Eventually saved in order meta in an array keyed by tracking number.
 *
 * @package     brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers;

use BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Order_Statuses;
use DateTime;
use Psr\Log\LoggerAwareTrait;

/**
 * Data object for tracking number updates.
 */
abstract class Tracking_Details_Abstract {
	use LoggerAwareTrait;

	/**
	 * The tracking number.
	 *
	 * @var string The tracking number itself.
	 */
	protected string $tracking_number;

	/**
	 * The shipping company.
	 *
	 * @see WC_Shipment_Tracking_Actions::get_providers()
	 *
	 * @var string The tracking provider / carrier.
	 */
	protected string $carrier;

	/**
	 * The most recent brief status returned from the carrier.
	 *
	 * @var ?string
	 */
	protected ?string $carrier_status = null;

	/**
	 * The most recent detail returned from the carrier.
	 *
	 * @var ?string
	 */
	protected ?string $carrier_summary = null;

	/**
	 * The last time the status was updated (if available).
	 *
	 * @var ?DateTime
	 */
	protected ?DateTime $last_updated_time = null;

	/**
	 * Detailed history from the carrier API.
	 *
	 * @var array<mixed>
	 */
	protected array $details;

	/**
	 * Has the carrier scanned in the package?
	 *
	 * @used-by API::find_undispatched_orders()
	 *
	 * @return bool
	 */
	abstract public function is_dispatched(): bool;

	/**
	 * Given the carrier's nomenclature for the tracking status, what is the corresponding WooCommerce status.
	 *
	 * @see Order_Statuses
	 * @see wc_get_order_statuses()
	 *
	 * @return ?string Null when the carrier API does not return any data for this tracking number.
	 */
	abstract public function get_equivalent_order_status(): ?string;

	/**
	 * Null when there has been no update yet.
	 *
	 * @return DateTime
	 */
	public function get_last_updated_time(): ?DateTime {
		return $this->last_updated_time;
	}

	/**
	 * Null when unavailable.
	 *
	 * @return DateTime|null
	 */
	abstract public function get_expected_delivery_time(): ?DateTime;

	/**
	 * Get the tracking number this object represents.
	 *
	 * @return string
	 */
	public function get_tracking_number(): string {
		return $this->tracking_number;
	}

	/**
	 * Get the tracking provider/carrier/company this object's tracking number is for.
	 *
	 * @see WC_Shipment_Tracking_Actions::get_providers()
	 *
	 * @return string
	 */
	public function get_carrier(): string {
		return $this->carrier;
	}

	/**
	 * The status returned from the carrier API. Null when not yet updated.
	 *
	 * @return ?string
	 */
	public function get_carrier_status(): ?string {
		return $this->carrier_status;
	}

	/**
	 * The long summary for the current status, as returned from the carrier API.
	 *
	 * @return string
	 */
	public function get_carrier_summary(): ?string {
		return $this->carrier_summary;
	}

	/**
	 * The full details from the carrier API.
	 *
	 * @return array<mixed>
	 */
	public function get_details(): array {
		return $this->details;
	}

}
