[![WordPress tested 5.8](https://img.shields.io/badge/WordPress-v5.8%20tested-0073aa.svg)](https://wordpress.org/plugins/bh-wc-shipment-tracking-updates) [![PHPUnit ](./.github/coverage.svg)](https://brianhenryie.github.io/bh-wc-shipment-tracking-updates/)

# Shipment Tracking Updates

Polls the USPS API for delivery updates.

Adds additional order statuses: `packed`, `in-transit`, `returning`.

Automatically changes order status when: 
* USPS picks up the order
* USPS delivers the order
* USPS is returning the order


![Settings](./assets/screenshot-1.png "Enable auto-purchase, order status after auto-purchase, order status after printing, log level")


### TODO:

* Dispatch email
* Add tracking detail to orders list page
* Add tracking detail to my-account
* Other carriers