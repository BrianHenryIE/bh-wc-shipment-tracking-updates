# Changelog

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

