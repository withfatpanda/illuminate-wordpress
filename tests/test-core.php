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
		$this->assertEquals( $this->plugin['name'], 'Test Plugin' );
		$this->assertEquals( $this->plugin['version'], '1.0.0' );
		$this->assertEquals( $this->plugin['authorURI'], 'https://github.com/withfatpanda' );
	}

}