<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Common format of the tracking details.
 *
 * Eventually saved in order meta in an array keyed by tracking number.
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers;

use DateTime;

/**
 * Data object for tracking number updates.
 */
abstract class Tracking_Details_Abstract {

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
	 * The WooCommerce order id.
	 *
	 * @var int
	 */
	protected ?int $order_id;

	/**
	 * To mark and filter when fetching new data.
	 *
	 * TODO: Should be outside this object so it is not saved to meta.
	 *
	 * @var ?bool
	 */
	protected ?bool $is_updated;

	/**
	 * Has the carrier scanned in the package?
	 *
	 * @return bool
	 */
	abstract public function is_dispatched(): bool;

	/**
	 * Given the tracking status, what is the corresponding WooCommerce status.
	 *
	 * @see wc_get_order_statuses()
	 *
	 * @return string
	 */
	abstract public function get_order_status(): ?string;

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

	/**
	 * Get the order id this tracking number is linked to.
	 *
	 * @return int
	 */
	public function get_order_id(): ?int {
		return $this->order_id;
	}

	/**
	 * Set the WooCommerce order id.
	 *
	 * @param int $order_id WooCommerce order id (WordPress post id).
	 * @throws \Exception To prevent the order id inadvertently being changed.
	 */
	public function set_order_id( ?int $order_id ): void {
		if ( isset( $this->order_id ) && $this->order_id !== $order_id ) {
			throw new \Exception( 'Order id is already set to another value.' );
		}
		$this->order_id = $order_id;
	}

	/**
	 * Has the updated flag been set?
	 * TODO: This should not be saved in the meta (or it will always appear updated).
	 *
	 * @return bool|null
	 */
	public function get_is_updated(): ?bool {
		return $this->is_updated;
	}

	/**
	 * Set the is_updated flag when processing
	 *
	 * @param ?bool $is_updated Flag.
	 */
	public function set_is_updated( ?bool $is_updated ): void {
		$this->is_updated = $is_updated;
	}

}
