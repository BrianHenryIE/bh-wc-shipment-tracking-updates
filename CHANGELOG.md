# Changelog

## 2.11.0

* Add hyperlinks to logs table: to user profile, to order ui
* Show last updated time as tooltip on each tracking number on admin order list ui
* Fix: metabox on admin order ui

## 2.10.3

* On deactivation, when changing order statuses to complete do not send order complete emails when status was "returning"

## 2.10.2

* Improved USPS error logging

## 2.10.1

* Add logging when customer manually changes order status
* Refactor code to mute USPS errors

## 2.10.0

* Add: function to mark orders complete without sending the order-complete email (CLI and admin order UI)

## 2.9.1

* Fix: "packed" email was not being sent until "in-transit" status
* Add: mute common USPS error
* Add: USPS status, 'In Transit from Origin Processing'

## 2.9.0

* Add: allow customer to set their order status to completed in my-account
* Language: auto-generate .pot file on releases
* Update project structure

## 2.8.0

* Add: Email when order is packed: "Your order is packed and ready to go"

## 2.7.3

* Add USPS + UPS carrier statuses

## 2.7.2

* Add: DHL
* Add: UPS
* Add: "Change status to Packed" admin UI orders list page bulk action
* Fix: Remove `$class_map_file` global
* Improve: Wording when order status is changed on plugin deactivation
* Change: Polls APIs hourly (previously every 30 minutes) because of DHL rate limits

## 2.6.0

* Add: Activator checks for USPS username from Address Validation plugin
* Add: Richer data on admin order ui Shipment Tracking metabox
* Add: wp cli command check_order_ids
* Fix: Order status was sometimes not updating
* Fix: Silence LicenseServer errors
* Fix: Link to logs on plugin-installer installation complete page
* Change: No longer changes order status after it has changed to "returning"
* Change: Silence some known intermittent USPS errors

## 2.5.2

* Fix: deserialization bug where old logger was remembered.

# 2.5.0 2022-March-14

* Add: updated/delivered date to admin order view

# 2.4.2 2022-Feb-25

* Update: bh-wp-logger

# 2.4.0

* Add: Tracking information on admin order page
* Fix: Add context to WC_Logger was comparing a string to an int and always returning early

# 2.3.2

* Add: check "packed" orders daily, and those without tracking numbers, mark complete after two days
* Add: CLI command `check_packed_orders`
* Add: Settings and Logs links on plugin installed page
* Add: USPS order statuses: "Rescheduled to Next Delivery Day", "Intercepted", "Processed through Facility"

## 2.2.0

* Add: display order stats when viewing the list of packed orders
* Add: display link to settings on plugin update admin page
* Fix: do not display settings link when WooCommerce is not active

## 2.1.3 2021-Nov-29

* Fix: email templates were not loading correctly

## 2.1.2  2021-Nov-28

* Added USPS delivered statuses 'Available for Pickup', 'Collect for Pick Up'
* Added USPS in-transit statuses 'Arrival at Post Office', 'Arrived at USPS Destination Facility', 'Arrived at USPS Destination Facility'
* Logger library updated to fix bug when deleting all logs.
* Don't log "updating order status" when it is staying the same.
* Fix: Add tracking information to admin order list table
* Change custom order statuses to completed on deactivation
* Only sends customer order dispatched email once

## 2.1.1 2021-Oct-28

* Fix: Typed property must not be accessed before initialization
* Use WordPress HTTP methods when querying USPS API
* Fix: Do not mark orders complete when one of many tracking numbers are undelivered

## 2.1 2021-Oct-16

* Add order dispatched email

