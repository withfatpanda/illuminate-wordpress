<?php
namespace FatPanda\Illuminate\WordPress;

use Illuminate\Support\Str;
use FatPanda\Illuminate\WordPress\Http\Router;

/**
 * Baseclass for all WordPress plugins.
 */
abstract class Plugin {

	protected $mainFile;

	protected $pluginName;

	protected $pluginUri;

	protected $description;

	protected $version;

	protected $author;

	protected $authorUri;

	protected $license;

	protected $licenseUri;

	protected $pluginData;

	protected $registeredDataTypes = [];

	protected $router;

	protected $routerNamespace;

	protected $routerVersion;

	protected $reflection;

	function __construct($mainFile)
	{
		if (!file_exists($mainFile)) {
			throw new \Exception("Main file $mainFile does not exist!");
		}

		$this->mainFile = $mainFile;
		
		$this->bindActionsAndFilters();
	}

	/**
	 * Using reflection, put together a list of all the action and filter hooks
	 * defined by this class, and then setup bindings for them.
	 * Action hooks begin with the prefix "on" and filter hooks begin with the
	 * prefix "filter". Additionally, look for the @priority doc comment, and
	 * use that to configure the priority loading order for the hook. Finally, count
	 * the number of parameters in the method signature, and use that to control
	 * the number of arguments that should be passed to the hook when it is invoked.
	 */
	protected function bindActionsAndFilters()
	{
		// setup activation and deactivation hooks
		$this->bindActivationAndDeactivationHooks();

		// reflect on the contents of this class
		$this->reflection = new \ReflectionClass($this);

		// get a list of all the methods on this class
		$methods = $this->reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

		// look for candidates for actions or filter hooks
		foreach($methods as $method) {
			// skip activation/deactivation hooks (handled above)
			if ($method->getName() === 'onActivate' || $method->getName() === 'onDeactivate') {
				continue;
			}

			// look in codedoc for @priority
			$priority = 10;
			$docComment = $method->getDocComment();
			if ($docComment !== false) {
				if (preg_match('/@priority\s+(\d+)/', $docComment, $matches)) {
					$priority = (int) $matches[1];
				}
			}

			// setup some internal event handlers
			add_action('plugins_loaded', [ $this, 'finalOnPluginsLoaded' ], 9);
			add_action('init', [ $this, 'finalOnInit' ], 9);
			
			// action methods begin with "on"
			if ('on' === strtolower(substr($method->getName(), 0, 2))) {
				$action = trim(strtolower(preg_replace('/(?<!\ )[A-Z]/', '_$0', substr($method->getName(), 2))), '_');
				$parameterCount = $method->getNumberOfParameters();
				add_action($action, [ $this, $method->getName() ], $priority, $parameterCount);
			
			// filter methods begin with "filter"
			} else if ('filter' === strtolower(substr($method->getName(), 0, 6))) {
				$filter = trim(strtolower(preg_replace('/(?<!\ )[A-Z]/', '_$0', substr($method->getName(), 6))), '_');
				$parameterCount = $method->getNumberOfParameters();
				add_action($filter, [ $this, $method->getName() ], $priority, $parameterCount);
			}
		}
	}

	/**
	 * Load plugin meta data, finish configuring various features, including
	 * the REST router and text translation.
	 * @see https://codex.wordpress.org/Plugin_API/Action_Reference/plugins_loaded
	 */
	final function finalOnPluginsLoaded()
	{
		// if we don't have the get_plugin_data() function, load it
		if (!function_exists('get_plugin_data')) {
			require_once ABSPATH.'wp-admin/includes/plugin.php';
		}

		$this->pluginData = get_plugin_data($this->mainFile);
		
		foreach($this->pluginData as $key => $value) {
			$propertyName = Str::camel($key);
			$this->{$propertyName} = $value;
		}

		$this->loadRouterAndRoutes();

		$this->loadTextDomain();
	}

	/**
	 * @see https://codex.wordpress.org/Plugin_API/Action_Reference/init
	 */
	final function finalOnInit()
	{
		$this->registerCustomDataTypes();		
	}

	/**
	 * Allow for methods of Bridge to be invoked on Plugin
	 */
	function __call($name, $args)
	{
		$callback = [ $this->router->bridge, $name ];
		if (is_callable($callback)) {
			return call_user_func_array($callback, $args);
		} else {
			throw new \BadMethodCallException($name);
		}
	}

	protected function bindActivationAndDeactivationHooks()
	{
		register_activation_hook($this->mainFile, [ $this, 'onActivate' ]);
		register_deactivation_hook($this->mainFile, [ $this, 'onDeactivate' ]);
	}

	protected function registerCustomDataTypes()
	{
		if (!empty($this->registeredDataTypes)) {
			$this->router->bridge->bootEloquent();

			foreach($this->registeredDataTypes as $class) {
				call_user_func($class . '::register');
			}
		}
	}

	protected function loadTextDomain()
	{
		load_plugin_textdomain( $this->textDomain, false, dirname( plugin_basename($this->mainFile) ) . rtrim($this->domainPath, '/') . '/' );
	}

	protected function loadRouterAndRoutes()
	{
		$this->setRouterNamespace( Str::slug($this->pluginName) );

		// just use the major version number
		$routerVersion = 'v1';
		if (!preg_match('/(\d+).*?/', $this->version, $matches)) {
			$routerVersion = $matches[1];
		}

		$this->setRouterVersion($routerVersion);

		$this->router = $router = new Router($this->routerNamespace, $this->routerVersion);

		$plugin = $this;

		require $this->getPluginBasePath() . '/src/Http/routes.php';
	}

	function getPluginBasePath()
	{
		return dirname($this->mainFile);
	}

	function getPluginData()
	{
		return $this->pluginData;
	}

	function setRouterNamespace($namespace)
	{
		$this->routerNamespace = $namespace;
		return $this;
	}

	function setRouterVersion($version)
	{
		$this->routerVersion = $version;
		return $this;
	}

	function setControllerClasspath($classpath)
	{
		$this->controllerClasspath = $classpath;
		return $this;
	}

	abstract function onActivate();

	abstract function onDeactivate();

	function register($customDataType)
	{
		$this->registeredDataTypes[(string) $customDataType] = $customDataType;
	}

	function unregister($customDataType)
	{
		unset($this->registeredDataTypes[(string) $customDataType]);
	}

}