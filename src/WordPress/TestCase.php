<?php
namespace FatPanda\Illuminate\WordPress;

/**
 * Baseclass for writing unit tests for plugins built with illuminate-wordpress
 */
abstract class TestCase extends \WP_UnitTestCase {

	/** 
	 * If testing a specific, plugin, all a TestCase needs to do is set the
	 * class name of the plugin here
	 * @type string
	 */
	protected $plugin;

	protected static $rest_api_functions = [ 'get', 'post', 'put', 'delete' ];

	/**
	 * Use the global function plugin to get a bootstrapped plugin instance
	 * @param String The plugin name or class 
	 */
	protected function plugin($name = null)
	{
		if (empty($name)) {
			$name = $this->plugin;
		}
		return plugin($name);
	}

	/** 
	 * Make sure we have a REST server available
	 */
	public function setUp() {
	  parent::setUp();
	 
	  global $wp_rest_server;
	  $this->server = $wp_rest_server = new \WP_REST_Server;
	  do_action( 'rest_api_init' );
	}

	/**
	 * Cleanup our REST server after each test
	 */
	public function tearDown() {
	  parent::tearDown();
	 
	  global $wp_rest_server;
	  $wp_rest_server = null;
	}

	function __call($name, $args = [])
	{
		if (in_array($name, static::$rest_api_functions)) {
			array_unshift($args, strtoupper($name));
			return call_user_func_array([ $this, 'api' ], $args);
		} 
	}

	protected function api($method, $url, $data = '')
	{
		$request = new WP_REST_Request($method, $url);
		$data = wp_parse_args($data);
		foreach($data as $key => $val) {
			$request->set_param($key, $val);
		}
		$response = $this->server->dispatch($request);
		return $response;
	}

}