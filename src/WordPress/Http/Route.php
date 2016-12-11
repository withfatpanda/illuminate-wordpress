<?php
namespace FatPanda\Illuminate\WordPress\Http;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use FatPanda\Illuminate\Support\Exceptions\ValidationException;
use Exception;
use WP_REST_Response;

class Route {

	protected $router;
	protected $baseRoute;
	protected $namespace;
	protected $version;
	protected $attributes;
	protected $baseRestOptions;
	protected $permissionCallback;
	protected $args = [];

	function __construct(Router $router, $route, $callback, $options)
	{
		$this->router = $router;
		$this->baseRoute = $route;
		$this->namespace = $router->getNamespace();
		$this->version = $router->getVersion();
		$this->callback = $callback;

		if (!empty($options['permission_callback'])) {
			$this->permissionCallback = $options['permission_callback'];
		}

		if (!empty($options['args'])) {
			foreach($options['args'] as $name => $config) {
				$this->args[$name] = new Arg($name, $config);
			}
			unset($options['args']);
		}

		$this->baseRestOptions = array_merge($options, [
			'callback' => [$this, 'invoke'],
			'permission_callback' => [$this, 'hasPermission']
		]);

		add_action('rest_api_init', [$this, '_rest_api_init']);		
	}

	function _rest_api_init()
	{
		$options = $this->baseRestOptions;

		$args = [];
		foreach($this->args as $arg) {
			$args[$arg->getName()] = $arg->getConfig();
		}
		$options['args'] = $args;

		list($route, $vars) = Router::substituteUrlArgTokens($this->baseRoute);

		register_rest_route("{$this->namespace}/{$this->version}", $route, $options);
	}

	function __get($name)
	{
		if ($name === 'router') {
			return $this->router;
		} else if (!empty($this->attributes[$name])) {
			return $this->attributes[$name];
		}
	}

	function __set($name, $value)
	{
		$this->attributes[$name] = $value;
	}

	function hasPermission($request)
	{
		if (!empty($this->permissionCallback)) {
			return call_user_func_array($this->permissionCallback, [$request]);
		}
		return true;
	}

	function when($callback)
	{
		$this->permissionCallback = $callback;
		return $this;
	}

	function where($arg, $regex)
	{
		$args = $name;
		if (!is_array($name)) {
			$args = [];
			$args[$name] = $regex;
		}

		foreach($args as $name => $regex) {
			// if this arg is already known, load the reference
			$arg = new Arg($name);
			if (!empty($this->args[$name])) {
				$arg = $this->args[$name];
			}

			if (is_callable($regex)) {
				$validate_callback = $regex;
			} else {
				$validate_callback = function($value, $request, $param) {
					return preg_match('#'.$regex.'#', $value);
				};
			}

			$arg->where($validate_callback);

			$args[$name] = $arg;
		}

		$this->args = array_merge($this->args, $args);
		return $this;
	}

	function &arg($name, $config = null)
	{
		if (!empty($this->args[$name])) {
			$arg =& $this->args[$name];
		} else {
			$arg = new Arg($name, $config);
			$this->args[$name] = $arg;
		}

		return $arg;
	}

	function args($name, $config = null)
	{
		$args = $name;
		if (!is_array($args)) {
			$args = [];
			$args[$name] = $config;
		}

		foreach($args as $name => $config) {
			$this->arg($name, $config);
		}	

		return $this;
	}

	function invoke(\WP_REST_Request $request)
	{
		// validate using 
		$validation_rules = [];
		$validation_messages = [];
		foreach($this->args as $arg) {
			if ($rules = $arg->getValidationRules()) {
				$validation_rules[$arg->getName()] = $rules;
				if ($messages = $arg->getValidationMessages()) {
					foreach($messages as $validator => $message) {
						$validation_messages[$arg->getName().'.'.$validator] = $message;
					}
				}
			}
		}

		$args = [ $request, $this ];
	
		if (!empty($validation_rules)) {
			try {
				ValidationException::assertValid($request->get_params(), $validation_rules, $validation_messages);

			} catch (Exception $e) {
				// TODO: consider bubbling up to Plugin...

				$response = static::buildErrorResponse($e);
				return new WP_REST_Response($response, $response['data']['status']);
			}
		}
				
		try {
			return call_user_func_array($this->callback, $args);
		} catch (Exception $e) {
			$response = static::buildErrorResponse($e);
			return new WP_REST_Response($response, $response['data']['status']);
		}

	}

	private static function buildErrorResponse(Exception $e)
  {
		$response = [ 
      'type' => get_class($e),
      'code' => $e->getCode(),
      'message' => $e->getMessage(),
      'data' => [
        'status' => 500
      ]
    ];

    if ($e instanceof ModelNotFoundException) {
      $response['data']['status'] = 404;
    }

    if ($e instanceof HttpException) {
      $response['data']['status'] = $e->getStatusCode();
    }

    if ($e instanceof \FatPanda\Illuminate\Support\Exceptions\ValidationException) {
      $response['data']['errors'] = $e->messages();
    }

    if (static::isDebugMode()) {
      $response['line'] = $e->getLine();
      $response['file'] = $e->getFile();
      $response['trace'] = $e->getTraceAsString();
    }

    return $response;
  }

	static public function isDebugMode()
  {
    if (current_user_can('administrator')) {
    	return true;
    } else {
      return constant('WP_DEBUG') && WP_DEBUG;
    }
  }

}