<?php
use FatPanda\Illuminate\WordPress\TestCase;

/**
 * Test all core features of the framework
 */
class TestCore extends TestCase {

	function setUp()
	{
		parent::setUp();
		$this->plugin = plugin('test-plugin');
	}

	/**
	 * Make sure our access to plugin metadata is working
	 */
	function testPluginData()
	{	
		$data = $this->plugin->getPluginData();
		$this->assertNotEmpty( $data );
		$this->assertEquals( $data['Name'], 'Test Plugin' );
		$this->assertEquals( $this->plugin->getPluginData('Name'), 'Test Plugin' );
	}

}