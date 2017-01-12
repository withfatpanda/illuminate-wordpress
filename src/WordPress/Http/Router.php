<?php
namespace FatPanda\Illuminate\WordPress\Http;

use FatPanda\Illuminate\WordPress\Plugin;
use Illuminate\Support\ServiceProvider;
use FatPanda\Illuminate\Support\Exceptions\Handler as ExceptionHandler;

/**
 * A class for simplifying the creation of routes within the WP REST API,
 * patterned on the way Laravel routing is designed.
 */
class Router extends ServiceProvider {

	protected $namespace;

	protected $version;

	protected $controllerClasspath;

	protected $defaultPermissionCallback;

	protected $queryVarName;

	protected $finalized = false;

	private static $resourcesActions;

	private static $requestMethods = ['get', 'post', 'put', 'patch', 'delete'];

	private static $restServerConventions = [
		'readable' => 'GET',

		'creatable' => 'POST',

		'editable' => 'POST, PUT, PATCH',

		'deletable' => 'DELETABLE',

		'all' => 'GET, POST, PUT, PATCH, DELETE',

		'allMethods' => 'GET, POST, PUT, PATCH, DELETE',

		'any' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD',

		'anyMethods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD'
	];

	/**
	 * @return void
	 */
	function __construct(Plugin $plugin) {
		$this->plugin = $plugin;

		static::setupResourceActions();
		
		$this->defaultPermissionCallback = function(\WP_REST_Request $request) {
			return is_user_logged_in();
		};

		add_action('init', function() {
			global $wp;
			$wp->add_query_var($this->namespace);
			$this->finalized = true;
		});

		add_action('template_redirect', function() {
			global $wp_query;
			$wp_query->set($this->queryVarName, $this);
			$this->finalized = true;
		});
	}

	/**
	 * @deprecated Use Router::getPlugin instead
	 */
	function getApp()
	{
		return $this->getPlugin();
	}

	/**
	 * Get the plugin to which this Router is attached
	 */
	function getPlugin()
	{
		return $this->plugin;
	}

	function setNamespace($namespace)
	{
		$this->namespace = $this->queryVarName = $namespace;
		return $this;
	}

	function setVersion($version)
	{
		$this->version = $version;
		return $this;
	}

	/**
	 * When WordPress loads a template, this Router instance will be 
	 * placed into WP_Query such that it is extracted into the variables
	 * available while rendering the template file. By default, the
	 * variable is named the same as the namespace for this Router, 
	 * which is assumed to be unique among all the Apps at runtime.
	 * To use a different variable, set it here.
	 */
	function setQueryVarName($queryVarName)
	{
		$this->queryVarName = $queryVarName;
		return $this;
	}

	/**
	 * This static property has to be setup in a function because
	 * PHP doesn't allow for us to use functions to create property values.
	 */
	private static function setupResourceActions()
	{
		if (empty(self::$resourcesActions)) {

			 self::$resourcesActions = [
				'index' => [
					'methods' => 'GET',
					'route' => '',
					'args' => [
						'order' => [
							// TODO: add description
							'type' => 'string',
							'enum' => [
								'asc', 'desc'
							],
							'default' => 'asc'
						],
						'orderby' => [
							// TODO: add description
							'type' => 'string',
							'default' => 'title'
						],
						'page' => [
							// TODO: add description
							'type' => 'integer',
							'default' => 1
						],
						'per_page' => [
							// TODO: add description
							'type' => 'integer',
							'default' => 10
						]
					]
				],

				'create' => [
					'methods' => 'GET',
					'route' => '/create',
				],

				'store' => [
					'methods' => 'POST',
					'route' => '',
				],

				'show' => [
					'methods' => 'GET',
					'route' => '/%s',
					'args' => [
						'fields' => [
							'type' => 'string',
							'description' => _( 'A comma-separated list of the fields that should be included' ),
							'sanitize_callback' => function($value) {
								return !is_array($value) ? preg_split('/,\s*/', $value) : $value;
							}
						]
					]
				],

				'edit' => [
					'methods' => 'GET',
					'route' => '/%s/edit',
				],

				'update' => [
					'methods' => 'PUT, PATCH',
					'route' => '/%s',
				],

				'destroy' => [
					'methods' => 'DELETE',
					'route' => '/%s',
				]
			];
			
		}
	}

	/**
	 * Create a new Rewrite rule, either by mapping the matching
	 * components of a regular expression onto the query args submitted
	 * to index.php (the default rewrite mechanism), or by invoking
	 * a callable in response to a matching URL.
	 * @param String The route for matching URLs; can include regex, but we'll also
	 * substitute any {arg} we find to create named components in the regex
	 * @param mixed What to do when this URL is a match; Strings are passed straight through,
	 * and are assumed to take the form index.php?whatever=whatever, and may include $matches
	 * references to your URL regex; you can also specify a callable, which will
	 * be invoked when the request matches this rule; the return result of that function
	 * should be an array, and supports the following elements:
	 * @param mixed Options to influence the rewrite rule and/or the response
	 *   after - String if "top", takes precedence over existing rules; if "bottom", all other
	 * 	 existing rules take precedence; default is "top"
	 * @see add_rewrite_rule
	 * @return RewriteRule
	 */
	function rewrite($route, $action = 'index.php', $options = [])
	{
		if (is_string($options)) {
			$options = [ 'after' => $options ];
		
		} else if (!is_array($options)) {
			$options = [];
		}

		$defaults = [
			'permission_callback' => $this->defaultPermissionCallback,
		];

		$options = array_merge($defaults, $options);

		return new RewriteRule($this, $route, $action, $options);
	}

	/**
	 * Create aliases for Router::route that include specific 
	 * HTTP methods (e.g., "get()" and "post()"), as well as
	 * WP_REST_SERVER conventions like "readable" and "creatable"
	 * and the Laravel convention of "any"
	 */
	function __call($name, $args) 
	{
		if (in_array($name, self::$requestMethods)) {
			$methods = $name;
			$route_args = array_merge([ $methods ], $args);
			return call_user_func_array([$this, 'route'], $route_args);
		}

		if (!empty(self::$restServerConventions[$name])) {
			$methods = self::$restServerConventions[$name];
			$route_args = array_merge([ $methods ], $args);
			return call_user_func_array([$this, 'route'], $route_args);	
		}

    throw new \BadMethodCallException($name);
	}

	/**
	 * Give Routers direct access to the ServiceContainer.
	 */
	function &__get($name)
	{
		// deprecated support for $this->app
		if ('app' === $name) {
			return $this->plugin;
		} else {
			return $this->plugin->{$name};
		}
	}

	/**
	 * Given the classname of a controller, seek out a proper
	 * namespace; start with the set controller classpath, then
	 * move on to the core default, then try looking for the
	 * class without any classpath at all.
	 * @return The fully-qualified classpath, or, if not found, a
	 * WP_Error object
	 */
	protected function getControllerClass($name)
	{
		$controllerClass = $name;
		if (!class_exists($name)) {
			$controllerClass = $this->controllerClasspath . '\\' . $name;
			if (!class_exists($controllerClass)) {
				$controllerClass = '\\FatPanda\\Illuminate\\WordPress\\Http\\Controllers\\' . $name;
				if (!class_exists($controllerClass)) {
					$controllerClass = new \WP_Error('class_not_found', "Class Not Found: {$name}");
				}
			}
		}

		return $controllerClass;
	}

	/**
	 * Invoke the given action on the given class, after resolving
	 * an existing Class that matches the given class as named; map
	 * arguments to controller action by dependency injection.
	 * @param String The name of a controller to invoke; presumed to be taken from routes config
	 * @param String The name of the action to invoke; must be a callable method on the controller
	 * @param array Arguments to pass into the invocation of the method
	 * @return mixed The result of the action invocation
	 * @throws Exception For various reasons, generally internal to the Controller's invocation
	 */
	private function invokeControllerAction($controllerClassNamed, $actionNamed, array $givenArgs = [])
	{
		if (is_wp_error($controllerClass = $this->getControllerClass($controllerClassNamed))) {
			return $controllerClass;
		}
		$reflection = new \ReflectionClass($controllerClass);
		$controller = new $controllerClass($this);
		if (!$reflection->hasMethod($actionNamed)) {
			return new \WP_Error('method_not_found', "Method Not Found: {$controllerClassNamed}@{$actionNamed}", ['status' => 404]);
		}
		$method = $reflection->getMethod($actionNamed);
	
		$args = [];
		if (!empty($givenArgs)) {
			$args[] = $givenArgs[0];
		}

		if (count($givenArgs) > 1) {
			$params = $method->getParameters();

			// loop over method params, ignoring the first
			for ($i=1; $i<count($params); $i++) {
				$param = $params[$i];

				// if the param has no type, just map the matching
				// argument by numeric order (if it exists)
				if (!$param->hasType()) {
					if (!empty($givenArgs[$i])) {
						$args[] = $givenArgs[$i];
					}
				} else {
					$class = $param->getClass();
					// map in plugin
					if ($class->isSubclassOf(\Illuminate\Container\Container::class)) {
						$args[] = $this->getPlugin();
					// otherwise, use the service container to build whatever
					} else {
						$args[] = $this->getPlugin()->make((string) $class);
					}
				}
			}
		}

		return $method->invokeArgs($controller, $args);
	}

	/**
	 * Given an Exception, build a data package suitable for reporting
	 * the error to the client.
	 * @param Exception The exception
	 * @return array
	 */
	public function buildErrorResponse(\Exception $e)
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

    if ($this->isDebugMode()) {
        $response['line'] = $e->getLine();
        $response['file'] = $e->getFile();
        $response['trace'] = $e->getTraceAsString();
    }

    return $response;
	}

	public function isDebugMode()
  {
     return ( defined('WP_DEBUG') && WP_DEBUG ) || $this->plugin->config('app.debug') || current_user_can('administrator');
  }

	/**
	 * Create a new route; note, you should probably use Router::resource()
	 * or Router::api() instead.
	 * @param String Comma-separated list of request methods, e.g., 'GET, POST'
	 * @param String The URL path
	 * @param mixed A callable
	 * @param array Options that get passed through to register_rest_route(); note
	 * that Router and Router offer semantic ways of configuring those settings
	 * @return Route
	 * @see Route::when()
	 * @see Route::where()
	 * @see Router::setDefaultPermissionCallback()
	 */
	function route($method, $route, $callback = null, $options = []) 
	{
		if (did_action('rest_api_init') || did_action('process_request')) {
			throw new \Exception("Too late to create new routes");
		}

		// treat strings, when not callable, as "Controller@method"
		if (is_string($callback) && !is_callable($callback)) {
			if (!preg_match('#(.*?)@([\\w\_]+)$#i', $callback, $matches)) {
				throw new \Exception("Improperly formed controller reference: must be ControllerClassName@methodName. Instead, found: {$callback}");
			}
			$callback = function() use ($matches) {
				// args here will include WP_REST_Request and Http\Route
				return $this->invokeControllerAction($matches[1], $matches[2], func_get_args());
			};

		// treat arrays, when not callable, as config sets that must include a "uses" argument
		} else if (is_array($callback) && !is_callable($callback)) {
			if (empty($callback['uses'])) {
				throw new \Exception("Config argument does not specify 'uses'");
			}
			return $this->route($method, $route, $callback['uses'], array_merge($options, $callback));

		// if at this point, $callback is not callable, then we have an error
		} else if (!is_callable($callback)) {
			throw new \Exception("Callback (argument #3) must be a string, Controller@method, or a config set (array), or a callable function, i.e., a lambda function");
		}

		$defaults = [
			'permission_callback' => $this->defaultPermissionCallback,
			'methods' => strtoupper($method)
		];

		$options = array_merge($defaults, $options);

		return new Route($this, $route, $callback, $options);
	}

	/**
	 * Change the default permission callback for routes defined
	 * by this router. Note: this change is retroactive.
	 * @param mixed A callable function reference
	 * @see Route::when
	 * @return Router
	 */
	function setDefaultPermissionCallback($callback) {
		$this->defaultPermissionCallback = $callback;
		return $this;
	}

	function setControllerClasspath($controllerClasspath)
	{
		$this->controllerClasspath = $controllerClasspath;
		return $this;
	}

	function getNamespace()
	{
		return $this->namespace;
	}

	function getVersion()
	{
		return $this->version;
	}

	function enableProfileController($profileControllerClass = 'ProfileController') {
		$this->get('profile/settings/{name?}', $profileControllerClass.'@getSettings');

		$this->post('profile/settings/{name}', $profileControllerClass.'@postSettings');

		$this->put('profile/settings/{name}', $profileControllerClass.'@putSettings');

		$this->delete('profile/settings/{name}', $profileControllerClass.'@deleteSettings');

		$this->post('profile/read/{post_id}', $profileControllerClass.'@postRead');

		$this->delete('profile/read/{post_id}', $profileControllerClass.'@deleteRead');
			
		$this->get('profile/read', $profileControllerClass.'@getLastRead');

		$this->post('profile/rating/{post_id}', $profileControllerClass.'@postRating')
			->args([
					'rating' => ['rules' => 'required|numeric']
				]);

		$this->delete('profile/rating/{post_id}', $profileControllerClass.'@deleteRating');
			
		$this->get('profile/rating/{post_id}', $profileControllerClass.'@getRating');

		$this->get('profile/section/{type}/{id?}', $profileControllerClass.'@getSection');

		$this->post('profile/section/{type}/{id?}', $profileControllerClass.'@postSection');

		$this->put('profile/section/{type}/{id}', $profileControllerClass.'@putSection');

		$this->delete('profile/section/{type}/{id?}', $profileControllerClass.'@deleteSection');

		$this->get('profile/vote/{post_id}', $profileControllerClass.'@getVote');

		$this->post('profile/vote/{post_id}', $profileControllerClass.'@postVote')
			->args([
					'vote' => ['rules' => 'required|numeric']
				]);

		$this->delete('profile/vote/{post_id}', $profileControllerClass.'@deleteVote');
	}

	/**
	 * Transform {arg} into regular expression garbage.
	 * @param string The route, expressed with mustache-wrapped args
	 * @return string Regular expression garbage... yay.
	 */
	static function substituteUrlArgTokens($route)
	{	
		$orig = $route;
		$tokens = [];
		// look for things like this {id} and like this {id?},
		// optionally preceeded by a forward slash
		while(preg_match('#/?({([\w\_]+)\??})#i', $route, $matches)) {
			// get the argument name:
			$arg = $matches[2];
			$tokens[] = $arg;
			// capture what we're going to replace 
			$subject = $matches[0];
			// make the replacement, e.g., (?P<id>[a-z\-\_]+)
			$replacement = '(?P<'.$arg.'>[^/]+)';
			// if the original arg was preceeded by a forward slash, we need to put it back
			if (stripos($subject, '/') === 0) {
				$replacement = '/' . $replacement;
			}
			// if the original arg has a ?, then we need to wrap our new token in the same
			if (stripos($subject, '?') !== false) {
				$replacement = '('.$replacement.')?';
			}
			// make the substitution
			$route = str_replace($subject, $replacement, $route);
		}
		
		return [ $route, $tokens ];
	}

	/**
	 * Create a resource endpoint set, bound to the given controller 
	 * for handling all requests, but with just the basic state-change
	 * operations + index: index, show, store, update and destory.
	 * @param String The name for the resource, e.g., "photo"; this becomes
	 * the base for each REST route, e.g., "photo/{photo}" 
	 * @param String The class name or class object of the Controller
   * that should be used to handle the routes; pro-tip: use the String
   * method to avoid needing to preload dependencies that might
   * not be used for the current request
   * @param array Options (see Router::resource())
	 * @return Route
	 * @see Router::resource()
	 */
	function api($name, $controllerClass, $options = [])
	{
		$options = array_merge(
			['only' => ['index', 'show', 'store', 'update', 'destroy']],
			$options
		);
		return $this->resource($name, $controllerClass, $options);
	}

	/**
	 * Create a resource endpoint set, bound to the given controller
	 * for handling all requests
	 * @param String The name for the resource, e.g., "photo"; this becomes
	 * the base for each REST route, e.g., "photo/{photo}" 
	 * @param String The class name or class object of the Controller
   * that should be used to handle the routes; pro-tip: use the String
   * method to avoid needing to preload dependencies that might
   * not be used for the current request
   * @param array options
   *   - "only" An array of the only actions that this resource allows
   *   - "except" An array of actions that should NOT be handled by this resource
   *   - "idString" Some token to use in the URL instead of "id"; make sure to
   *       wrap it in curly brackets, e.g., "{id}"
	 * @return Route
	 * @see Router::resource()
	 */
	function resource($name, $controllerClass, $options = [])
	{
		if (empty($options['idString'])) {
			$options['idString'] = '{id}'; // == (?P<id>.+?)";
		}		

		// by default, create routes for all known actions
		$actions = array_keys(self::$resourcesActions);

		if (!empty($options['only'])) {
			$actions = $options['only'];
		
		} else if (!empty($options['except'])) {
			$actions = array_diff($actions, $options['except']);

		}

		if (!is_array($actions)) {
			$actions = [$actions];
		}

		$group = new RouteGroup();

		foreach($actions as $action) {
			// make sure this action is one we know how to process
			if (empty(self::$resourcesActions[$action])) {
				throw new \Exception("Action [$action] is unrecognized; please use one of: ".implode(',', array_keys(self::$resourcesActions)));
			}
			// get the action defintion
			$def = self::$resourcesActions[$action];
			// invoke Router::route 
			$route = call_user_func_array([ $this, 'route'], [
				// the method is specified by the action definition:
				$def['methods'],
				// the route is a formatted string; drop the idString into it
				$name . sprintf($def['route'], $options['idString']),
				// inside the callback, we create our controller instance and call the proper action
				function() use ($controllerClass, $action) {
					return $this->invokeControllerAction($controllerClass, $action, func_get_args());
				},
				// pass-through options as rest config options
				$options
			]);

			if (!empty($def['args'])) {
				$route->args($def['args']);
			}

			$group[] = $route;
		}

		return $group;
	}

}