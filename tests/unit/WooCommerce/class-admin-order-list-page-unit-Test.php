<?php

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce;

use BrianHenryIE\WC_Shipment_Tracking_Updates\API_Interface;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Shipment_Tracking_Updates\WooCommerce\Admin_Order_List_Page
 */
class Admin_Order_List_Page_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		\WP_Mock::setUp();
	}

	/**
	 * Without this, WP_Mock userFunctions might stick around for the next test.
	 */
	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * Happy path.
	 *
	 * @covers ::print_packed_stats
	 * @covers ::__construct
	 */
	public function test_print_list(): void {

		/** @var array<string, array<int>> $api_response */
		$api_response = array(
			'1' => array( 123, 125, 127 ), // three orders were packed one day ago.
			'3' => array( 101, 102 ),     // two orders were packed three days ago.
			'9' => array( 87 ),          // one order was packed nine days ago.
		);

		$api = $this->makeEmpty(
			API_Interface::class,
			array(
				'get_order_ids_by_number_of_days_since_packed' => $api_response,
			)
		);

		// Set up so it appears we're on the correct page.
		$GLOBALS['post_type']   = 'shop_order';
		$GLOBALS['post_status'] = 'wc-packed';

		$current_screen     = new \stdClass();
		$current_screen->id = 'edit-shop_order';

		\WP_Mock::userFunction(
			'get_current_screen',
			array(
				'return' => $current_screen,
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'admin_url',
			array(
				'return_arg' => 0,
				'times'      => 3,
			)
		);

		\WP_Mock::userFunction(
			'wp_kses',
			array(
				'return_arg' => 0,
				'times'      => 3,
			)
		);

		$sut = new Admin_Order_List_Page( $api );

		ob_start();
		$sut->print_packed_stats();

		/** @var string $result */
		$result = ob_get_clean();

		$this->assertStringContainsString( '2 orders waiting 3 days since being packed.', $result );
		$this->assertStringContainsString( '1 order waiting 9 days since being packed.', $result );

	}

	/**
	 * @covers ::print_packed_stats
	 */
	public function test_print_nothing_on_wrong_page(): void {

		$api = $this->makeEmpty( API_Interface::class );

		$GLOBALS['post_type']   = 'shop_order';
		$GLOBALS['post_status'] = 'wc-packed';

		$current_screen     = new \stdClass();
		$current_screen->id = 'NOT-edit-shop_order';

		\WP_Mock::userFunction(
			'get_current_screen',
			array(
				'return' => $current_screen,
				'times'  => 1,
			)
		);

		$sut = new Admin_Order_List_Page( $api );

		ob_start();
		$sut->print_packed_stats();
		$result = ob_get_clean();

		$this->assertEmpty( $result );
	}

	/**
	 * @covers ::print_packed_stats
	 */
	public function test_print_nothing_on_wrong_order_status(): void {

		$api = $this->makeEmpty( API_Interface::class );

		$GLOBALS['post_type']   = 'shop_order';
		$GLOBALS['post_status'] = 'wc-not-packed';

		$current_screen     = new \stdClass();
		$current_screen->id = 'edit-shop_order';

		\WP_Mock::userFunction(
			'get_current_screen',
			array(
				'return' => $current_screen,
				'times'  => 1,
			)
		);

		$sut = new Admin_Order_List_Page( $api );

		ob_start();
		$sut->print_packed_stats();
		$result = ob_get_clean();

		$this->assertEmpty( $result );
	}


	/**
	 * @covers ::print_packed_stats
	 */
	public function test_print_nothing_when_nothing_packed(): void {

		$api = $this->makeEmpty(
			API_Interface::class,
			array(
				'get_order_ids_by_number_of_days_since_packed' => array(),
			)
		);

		// Set up so it appears we're on the correct page.
		$GLOBALS['post_type']   = 'shop_order';
		$GLOBALS['post_status'] = 'wc-packed';

		$current_screen     = new \stdClass();
		$current_screen->id = 'edit-shop_order';

		\WP_Mock::userFunction(
			'get_current_screen',
			array(
				'return' => $current_screen,
				'times'  => 1,
			)
		);

		$sut = new Admin_Order_List_Page( $api );

		ob_start();
		$sut->print_packed_stats();
		$result = ob_get_clean();

		$this->assertEmpty( $result );
	}

}
