<?php
/**
 * After the plugin has been installed/updated, beside "Go to plugin installer", add links to the plugin settings
 * and logs.
 *
 * @package brianhenryie/bh-wc-shipment-tracking-updates
 * @author  BrianHenryIE <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Shipment_Tracking_Updates\Admin;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Shipment_Tracking_Updates\Settings_Interface;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Shipment_Tracking_Updates\Admin\Plugin_Installer
 */
class Plugin_Installer_Unit_Test extends \Codeception\Test\Unit {

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

	/**
	 * @covers ::add_settings_link
	 */
	public function test_settings_link_added(): void {

		\WP_Mock::userFunction(
			'admin_url',
			array(
				'return_arg' => 0,
			)
		);

		\WP_Mock::userFunction(
			'is_plugin_active',
			array(
				'args'   => array( 'woocommerce/woocommerce.php' ),
				'times'  => 1,
				'return' => true,
			)
		);

		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_basename' => 'bh-wc-shipment-tracking-updates/bh-wc-shipment-tracking-updates.php',
				'get_plugin_slug'     => 'bh-wc-shipment-tracking-updates',
			)
		);
		$logger   = new ColorLogger();

		$sut = new Plugin_Installer( $settings, $logger );

		$result = $sut->add_settings_link( array(), null, 'bh-wc-shipment-tracking-updates/bh-wc-shipment-tracking-updates.php' );

		$this->assertIsArray( $result );

		$link_html = $result[1];

		$this->assertStringContainsString( 'Go to Shipment Tracking Updates settings', $link_html );

		$this->assertStringContainsString( 'href="/admin.php?page=wc-settings&tab=shipping&section=bh-wc-shipment-tracking-updates', $link_html );
	}

	/**
	 * @covers ::add_settings_link
	 */
	public function test_no_settings_link_added_when_woocommerce_inactive(): void {

		\WP_Mock::userFunction(
			'is_plugin_active',
			array(
				'args'   => array( 'woocommerce/woocommerce.php' ),
				'times'  => 1,
				'return' => false,
			)
		);

		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array( 'get_plugin_basename' => 'bh-wc-shipment-tracking-updates/bh-wc-shipment-tracking-updates.php' )
		);
		$logger   = new ColorLogger();

		$sut = new Plugin_Installer( $settings, $logger );

		$result = $sut->add_settings_link( array(), null, 'bh-wc-shipment-tracking-updates/bh-wc-shipment-tracking-updates.php' );

		$this->assertIsArray( $result );

		$this->assertEmpty( $result );
	}

	/**
	 * @covers ::add_settings_link
	 */
	public function test_logs_link_added(): void {

		\WP_Mock::userFunction(
			'admin_url',
			array(
				'return_arg' => 0,
			)
		);

		\WP_Mock::userFunction(
			'is_plugin_active',
			array(
				'args'   => array( 'woocommerce/woocommerce.php' ),
				'times'  => 1,
				'return' => true,
			)
		);

		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_basename' => 'bh-wc-shipment-tracking-updates/bh-wc-shipment-tracking-updates.php',
				'get_plugin_slug'     => 'bh-wc-shipment-tracking-updates',
			)
		);
		$logger   = new ColorLogger();

		$sut = new Plugin_Installer( $settings, $logger );

		$result = $sut->add_settings_link( array(), null, 'bh-wc-shipment-tracking-updates/bh-wc-shipment-tracking-updates.php' );

		$this->assertIsArray( $result );

		$link_html = $result[3];

		$this->assertStringContainsString( 'Go to Shipment Tracking Updates logs', $link_html );

		$this->assertStringContainsString( 'href="/admin.php?page=bh-wc-shipment-tracking-updates', $link_html );
	}


	/**
	 * @covers ::add_settings_link
	 */
	public function test_return_early_for_other_plugins(): void {

		\WP_Mock::userFunction(
			'is_plugin_active',
			array(
				'args'   => array( 'woocommerce/woocommerce.php' ),
				'times'  => 0,
				'return' => true,
			)
		);

		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_basename' => 'bh-wc-shipment-tracking-updates/bh-wc-shipment-tracking-updates.php',
				'get_plugin_slug'     => 'bh-wc-shipment-tracking-updates',
			)
		);
		$logger   = new ColorLogger();

		$sut = new Plugin_Installer( $settings, $logger );

		$result = $sut->add_settings_link( array(), null, 'any-other-plugin/any-other-plugin.php' );

		$this->assertIsArray( $result );

		$this->assertEmpty( $result );
	}

	/**
	 * @covers ::__construct
	 */
	public function test_cover_constructor(): void {

		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();

		$sut = new Plugin_Installer( $settings, $logger );

		$this->assertInstanceOf( Plugin_Installer::class, $sut );
	}
}
