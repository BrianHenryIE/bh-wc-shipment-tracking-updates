<?php
/**
 * Common format of the tracking details.
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers;

use DateTime;

abstract class Tracking_Details_Abstract {

	protected string $tracking_number;

	protected string $carrier;

	protected ?string $carrier_status;

	protected ?string $carrier_summary;

	protected ?DateTime $last_updated_time = null;

	/**
	 * @var array The history
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
	 */
	protected ?bool $is_updated;

	/**
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
	 *
	 * Null when unavailable.
	 *
	 * @return DateTime|null
	 */
	abstract public function get_expected_delivery_time(): ?DateTime;


	/**
	 * @return string
	 */
	public function get_tracking_number(): string {
		return $this->tracking_number;
	}

	/**
	 * @return string
	 */
	public function get_carrier(): string {
		return $this->carrier;
	}

	/**
	 * Null when not yet updated.
	 *
	 * @return ?string
	 */
	public function get_carrier_status(): ?string {
		return $this->carrier_status;
	}

	/**
	 * @return string
	 */
	public function get_carrier_summary(): ?string {
		return $this->carrier_summary;
	}

	/**
	 * @return array
	 */
	public function get_details(): array {
		return $this->details;
	}

	/**
	 * @return int
	 */
	public function get_order_id(): ?int {
		return $this->order_id;
	}

	/**
	 * @param int $order_id
	 */
	public function set_order_id( ?int $order_id ): void {
		$this->order_id = $order_id;
	}

	/**
	 * @return bool|null
	 */
	public function get_is_updated(): ?bool {
		return $this->is_updated;
	}

	/**
	 * @param bool|null $is_updated
	 */
	public function set_is_updated( ?bool $is_updated ): void {
		$this->is_updated = $is_updated;
	}


}
