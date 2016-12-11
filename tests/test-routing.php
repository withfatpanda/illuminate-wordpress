<?php
use FatPanda\Illuminate\WordPress\TestCase;

/**
 * Test all routing features of the framework
 */
class TestRouting extends TestCase {

	function setUp()
	{
		parent::setUp();

		$this->plugin = plugin('test-plugin');

		$this->administrator = $this->factory->user->create(['role' => 'administrator']);
	}

	/**
	 * Test basic facts about the router
	 */
	function testRouter()
	{
		$this->assertEquals( $this->plugin->getRestNamespace(), 'test-plugin' );
		$this->assertEquals( $this->plugin->getRestVersion(), 'v1' );
		$this->assertEquals( $this->plugin->getRestNamespace(), $this->plugin->router->getNamespace() );
		$this->assertEquals( $this->plugin->getRestVersion(), $this->plugin->router->getVersion() );
	}

	/**
	 * Test an unautheticated GET request
	 */
	function testGetUnauthenticated()
	{
		// by default, all REST requests require authentication
		// so this request should give us a 403
		$this->get('/test-plugin/v1/plugin-data/Name')->seeStatusCode(403);
	}

	/**
	 * Test an authenticated GET request
	 */
	function testGetAuthenticated()
	{
		$this->login($this->administrator);
		$this->get('/test-plugin/v1/plugin-data/Name')->seeStatusCode(200);	
	}

	/**
	 * Test a missing GET router
	 */
	function testGet404()
	{
		$this->get('/test-plugin/v1/route-that-does-not-exist')->seeStatusCode(404);
	}

}