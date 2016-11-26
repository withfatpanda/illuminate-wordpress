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

	function __construct($mainFile)
	{
		if (!file_exists($mainFile)) {
			throw new \Exception("Main file $mainFile does not exist!");
		}

		$this->mainFile = $mainFile;
		$this->pluginData = get_plugin_data($this->mainFile);
		
		foreach($this->pluginData as $key => $value) {
			$propertyName = Str::camel($key);
			$this->{$propertyName} = $value;
		}

		$this->setRouterNamespace( Str::slug($this->pluginName) );

		// just use the major version number
		$routerVersion = 'v1';
		if (!preg_match('/(\d+).*?/', $this->version, $matches)) {
			$routerVersion = $matches[1];
		}

		$this->setRouterVersion($routerVersion);

		register_activation_hook($this->mainFile, [ $this, 'activate' ]);
		register_deactivation_hook($this->mainFile, [ $this, 'deactivate' ]);
		add_action('plugins_loaded', [ $this, 'loadTextDomain' ]);
	}

	function loadTextDomain()
	{
		load_plugin_textdomain( $this->textDomain, false, 
			dirname( plugin_basename($this->mainFile) ) . rtrim($this->domainPath, '/') . '/' );
	}

	function getPluginBasePath()
	{
		return dirname($this->mainFile);
	}

	function getPluginData()
	{
		return $this->pluginData;
	}

	function make()
	{
		$this->router = $router = new Router($this->routerNamespace, $this->routerVersion);
		
		if (!empty($this->registeredDataTypes)) {

		}

		$plugin = $this;

		require $this->getPluginBasePath() . '/src/Http/routes.php';
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

	abstract function activate();

	abstract function deactivate();

	function register($customDataType)
	{
		$this->registeredDataTypes[] = $customDataType;
	}

}