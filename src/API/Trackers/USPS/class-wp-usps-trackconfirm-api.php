<?php
/**
 * Overrides the USPSBase::do_request() function in order to use WordPress HTTP functions.
 *
 * TODO: Rewrite the parent library to use PSR-7/Guzzle.
 *
 * @package     brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\API\Trackers\USPS;

use BrianHenryIE\WC_Shipment_Tracking_Updates\USPS\TrackConfirm;
use BrianHenryIE\WC_Shipment_Tracking_Updates\USPS\USPSBase;
use Psr\Log\LoggerAwareTrait;

/**
 * Use WordPress's HTTP functions – allows filtering (testing). Required by WooCommerce.com store.
 *
 * @see wp_remote_post()
 *
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
 */
class WP_USPS_TrackConfirm_API extends TrackConfirm {

	use LoggerAwareTrait;

	/**
	 * The superclass initializes this array to a string, then queries its `http_code` entry.
	 *
	 * @see USPSBase::$headers
	 * @see USPSBase::isError()
	 * @var array<string,mixed>
	 */
	protected $headers = array( 'http_code' => 0 );

	/**
	 * Make the HTTP request.
	 *
	 * Overrides the USPS library's doRequest function in order to use WordPress's HTTP functions.
	 *
	 * @see USPSBase::doRequest()
	 *
	 * @param ?resource $_ch In the superclass, this is the optional initialized cURL handle. Unused here.
	 *
	 * @return string The response text (presumably the XML).
	 */
	protected function doRequest( $_ch = null ) {
		$url = $this->getEndpoint();

		/**
		 * The request options, as copied from the library/superclass defaults.
		 *
		 * @see self::$CURL_OPTS
		 */
		$args = array(
			'timeout'    => 60,
			'user-agent' => 'usps-php',
			'body'       => $this->getPostData(),
		);

		// Execute.
		$remote_response = wp_remote_post( $url, $args );

		if ( is_wp_error( $remote_response ) ) {

			$this->setErrorCode( intval( $remote_response->get_error_code() ) );
			$this->setErrorMessage( $remote_response->get_error_message() );

		} elseif ( is_wp_error( $remote_response['body'] ) ) {

			$remote_response_body = $remote_response['body'];
			$this->setErrorCode( intval( $remote_response_body->get_error_code() ) );
			$this->setErrorMessage( $remote_response_body->get_error_message() );

		} else {

			$remote_response_body = $remote_response['body'];

			$this->setResponse( $remote_response_body );

			$headers = $remote_response['headers']->getAll();

			/**
			 * The superclass uses `http_code` header to determine was there an error.
			 *
			 * @see USPSBase::isError()
			 */
			$headers['http_code'] = wp_remote_retrieve_response_code( $remote_response );
			$this->setHeaders( $headers );

			// Convert XML response to array.
			$this->convertResponseToArray();
		}

		// If the request succeeded but returned an error, set error code and message from the response body.
		if ( $this->isError() ) {
			$arrayResponse = $this->getArrayResponse();

			// Find the error number.
			$errorInfo = $this->getValueByKey( $arrayResponse, 'Error' );

			if ( $errorInfo ) {
				$this->setErrorCode( $errorInfo['Number'] );
				$this->setErrorMessage( $errorInfo['Description'] );
			}
		}

		return $this->getResponse();
	}
}
