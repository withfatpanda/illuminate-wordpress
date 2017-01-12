<?php
namespace FatPanda\Illuminate\WordPress\Http;

use FatPanda\Illuminate\Support\Exceptions\ValidationException;
use Exception;
use WP_REST_Response;

class Route extends Routable {

	protected $router;
	protected $baseRoute;
	protected $namespace;
	protected $version;
	protected $attributes;
	protected $baseRestOptions;
	protected $args = [];

	function __construct(Router $router, $route, $callback, $options = [])
	{
		if (did_action('rest_api_init')) {
			throw new \Exception("Too late to create new routes");
		}

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
				ValidationException::assertValid($this->router->validator, $request->get_params(), $validation_rules, $validation_messages);

			} catch (Exception $e) {
				$response = $this->router->buildErrorResponse($e);
				return new WP_REST_Response($response, $response['data']['status']);
			}
		}

    try {
      return call_user_func_array($this->callback, $args);

		} catch (Exception $e) {
      $response = $this->router->buildErrorResponse($e);
			return new WP_REST_Response($response, $response['data']['status']);

		}

	}

}