<?php
namespace FatPanda\Illuminate\WordPress;

use WP_REST_Request;
use WP_REST_Server;
use WP_REST_Response;
use WP_UnitTestCase;

use PHPUnit_Framework_Assert as PHPUnit;
use PHPUnit_Framework_ExpectationFailedException;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;


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

	/**
   * Assert that the response contains JSON.
   *
   * @param  array|null  $data
   * @param  bool  $negate
   * @return $this
   */
  public function seeJson(array $data = null, $negate = false)
  {
    if (is_null($data)) {
      $content = $this->response->get_data();

      if (is_string($content)) {
      	$this->assertTrue(json_decode($content) !== false);
      } 
      return $this;
    }

    try {
      return $this->seeJsonEquals($data);
    } catch (PHPUnit_Framework_ExpectationFailedException $e) {
      return $this->seeJsonContains($data, $negate);
    }
  }

  /**
   * Assert that the response contains an exact JSON array.
   *
   * @param  array  $data
   * @return $this
   */
  public function seeJsonEquals(array $data)
  {
    $actual = json_encode(Arr::sortRecursive(
      (array) $this->decodeResponseJson()
    ));

    $this->assertEquals(json_encode(Arr::sortRecursive($data)), $actual);

    return $this;
  }

  /**
   * Assert that the response contains the given JSON.
   *
   * @param  array  $data
   * @param  bool  $negate
   * @return $this
   */
  protected function seeJsonContains(array $data, $negate = false)
  {
    $method = $negate ? 'assertFalse' : 'assertTrue';

    $actual = json_encode(Arr::sortRecursive(
        (array) $this->decodeResponseJson()
    ));

    foreach (Arr::sortRecursive($data) as $key => $value) {
      $expected = $this->formatToExpectedJson($key, $value);

      $this->{$method}(
        Str::contains($actual, $expected),
        ($negate ? 'Found unexpected' : 'Unable to find').' JSON fragment'.PHP_EOL."[{$expected}]".PHP_EOL.'within'.PHP_EOL."[{$actual}]."
      );
    }

    return $this;
  }

  /**
   * Assert that the JSON response has a given structure.
   *
   * @param  array|null  $structure
   * @param  array|null  $responseData
   * @return $this
   */
  public function seeJsonStructure(array $structure = null, $responseData = null)
  {
    if (is_null($structure)) {
      return $this->seeJson();
    }

    if (! $responseData) {
      $responseData = $this->decodeResponseJson();
    }

    foreach ($structure as $key => $value) {
      if (is_array($value) && $key === '*') {
        $this->assertInternalType('array', $responseData);

        foreach ($responseData as $responseDataItem) {
          $this->seeJsonStructure($structure['*'], $responseDataItem);
        }
      } elseif (is_array($value)) {
        $this->assertArrayHasKey($key, $responseData);
        $this->seeJsonStructure($structure[$key], $responseData[$key]);
      } else {
        $this->assertArrayHasKey($value, $responseData);
      }
    }

    return $this;
}

  /**
   * Format the given key and value into a JSON string for expectation checks.
   *
   * @param  string  $key
   * @param  mixed  $value
   * @return string
   */
  protected function formatToExpectedJson($key, $value)
  {
    $expected = json_encode([$key => $value]);

    if (Str::startsWith($expected, '{')) {
      $expected = substr($expected, 1);
    }

    if (Str::endsWith($expected, '}')) {
      $expected = substr($expected, 0, -1);
    }

    return trim($expected);
  }


  /**
   * Validate and return the decoded response JSON.
   *
   * @return array
   */
  protected function decodeResponseJson()
	{
    $decodedResponse = $this->response->get_data();
    if (is_string($decodedResponse)) {
    	$decodedResponse = json_decode($decodedResponse, true);
    }

    if (is_null($decodedResponse) || $decodedResponse === false) {
      $this->fail('Invalid JSON was returned from the route. Perhaps an exception was thrown?');
    }

    return $decodedResponse;
  }

}