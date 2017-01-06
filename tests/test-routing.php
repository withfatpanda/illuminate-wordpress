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
		$this->post = $this->factory->post->create(['post_name' => 'widget-name', 'post_type' => 'widget']);

		// gotta set a default permalink structure;
		// otherwise WP_UnitTestCase::go_to doesn't do anything
		$this->set_permalink_structure('/%postname%/');
	}

	/**
	 * Test basic rewriting
	 */
	function testBasicRewriting()
	{
		$this->assertFalse($this->plugin->hasTriggeredBasicRewriteRule);
		$this->go_to( home_url('test/basic') );
		$this->assertTrue($this->plugin->hasTriggeredBasicRewriteRule);
	}

	/**
	 * Test basic overriding of built-in query vars
	 */
	function testBuiltInQueryVars()
	{
		$this->go_to( home_url() );
		$this->assertEquals('', get_query_var('post_type'));
		$this->assertTrue(is_front_page());

		$this->go_to( home_url('type/widget') );
		$this->assertEquals('widget', get_query_var('post_type'));
		$this->assertFalse(is_post_type_archive('foo'));
		$this->assertTrue(is_post_type_archive('widget'));
		$this->assertFalse(is_single());

		$this->go_to( home_url('type/widget/widget-name') );
		$this->assertEquals('widget', get_query_var('post_type'));
		$this->assertEquals('widget-name', get_query_var('name'));
		$this->assertFalse(is_404());
		$this->assertTrue(is_single('widget-name'));
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
	 * Test an unautheticated GET request.
	 */
	function testGetUnauthenticated()
	{
		// by default, all REST requests require authentication
		// so this request should give us a 403
		$this->get('/test-plugin/v1/plugin-data/name')->seeStatusCode(403);
	}

	/**
	 * Test an authenticated GET request.
	 */
	function testGetAuthenticated()
	{
		$this->login($this->administrator);
		$this->get('/test-plugin/v1/plugin-data/name')->seeStatusCode(200);	
		$this->assertEquals("Test Plugin", $this->response->get_data());
	}

	/**
	 * Test a missing GET router.
	 */
	function testGet404()
	{
		$this->get('/test-plugin/v1/route-that-does-not-exist')->seeStatusCode(404);
	}

	/**
	 * Assert a JSON structure.
	 */
	function testJsonResponseStructure()
	{
		$this->login($this->administrator)
			->get('/test-plugin/v1/plugin-data')
			->seeStatusCode(200)
			->seeJson([ 'Name' => 'Test Plugin' ])
			->seeJson([ 'TextDomain' => 'test-plugin' ]);
	}

}