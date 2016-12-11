<?php
namespace FatPanda\Illuminate\WordPress;

use WP_REST_Request;
use WP_REST_Server;
use WP_REST_Response;
use WP_UnitTestCase;

/**
 * Baseclass for writing unit tests for plugins built with illuminate-wordpress,
 * including support for invoking and making assertions about REST API rests.
 */
abstract class TestCase extends WP_UnitTestCase {

	/**
	 * @var array
	 */
	protected static $rest_api_functions = [ 'get', 'post', 'put', 'delete' ];

	/**
	 * @var WP_REST_Response
	 */
	protected $response = null;

	/**
	 * @var WP_User The user making any HTTP requests
	 */
	protected $user = null;

	/** 
	 * Make sure we have a REST server available
	 */
	public function setUp() {
	  parent::setUp();
	 
	  global $wp_rest_server;
	  $this->server = $wp_rest_server = new WP_REST_Server;
	  do_action( 'rest_api_init' );
	}

	public function login($user)
	{
		wp_set_current_user($user);
		$this->user = $user;
		return $this;
	}

	public function getUser()
	{
		return $this->user;
	}

	public function logout()
	{
		wp_set_current_user(0);
		$this->user = null;
		return $this;
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
			return call_user_func_array([ $this, 'json' ], $args);
		} else {
			throw new \BadMethodCallException($name);
		} 
	}

	function json($method, $uri, $data = '')
	{
		$request = new WP_REST_Request($method, $uri);
		$data = wp_parse_args($data);
		foreach($data as $key => $val) {
			$request->set_param($key, $val);
		}
		$this->response = $this->server->dispatch($request);
		return $this;
	}

	/**
   * Asserts that the status code of the response matches the given code.
   *
   * @param  int  $status
   * @return $this
   */
  protected function seeStatusCode($status)
  {
    $this->assertEquals($status, $this->response->get_status());

    return $this;
  }

	function seeJson()
	{

	}

}