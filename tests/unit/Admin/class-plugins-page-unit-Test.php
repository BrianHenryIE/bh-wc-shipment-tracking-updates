<?php
/**
 *
 *
 * @package brianhenryie/bh-wc-shipment-tracking-updates
 * @author  BrianHenryIE <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\Admin;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Shipment_Tracking_Updates\API\Settings_Interface;

/**
 * Tests a link is added and that it contains `admin.php...section=bh-wc-shipment-tracking-updates`.
 */
class Plugins_Page_Unit_Test extends \Codeception\Test\Unit {

	protected function setUp(): void {
		\WP_Mock::setUp();
	}

	/**
	 * Without this, WP_Mock userFunctions might stick around for the next test.
	 */
	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}


	public function test_settings_link_added(): void {

		\WP_Mock::userFunction(
			'admin_url',
			array(
				'return_arg' => 0,
			)
		);

		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array( 'get_plugin_slug' => 'bh-wc-shipment-tracking-updates' )
		);
		$logger   = new ColorLogger();

		$sut = new Plugins_Page( $settings, $logger );

		$result = $sut->action_links( array(), '', array(), '' );

		$this->assertIsArray( $result );

		$link_html = $result[0];

		$this->assertStringContainsString( 'Settings', $link_html );

		$this->assertStringContainsString( 'href="/admin.php?page=wc-settings&tab=shipping&section=bh-wc-shipment-tracking-updates', $link_html );
	}
}
