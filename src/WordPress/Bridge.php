<?php
namespace FatPanda\Illuminate\WordPress;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use FatPanda\Illuminate\WordPress\Configurator;

/**
 * Make it possible to use Laravel or Lumen inside of WordPress.
 */
class Bridge {

	protected $laravelAppBoostrapFilePath;

	protected $pluginMainFilePath;

	protected $namespace;

	protected $app;

	protected $userClass = '\FatPanda\Illuminate\WordPress\Models\User';

	static protected $databaseManager;

	/**
	 * Create a new Bridge.
	 * @param String namespace
	 * @param String Path to the Laravel bootstrap file
	 * @param String Class The class to use when invoking Bridge::user
	 * @param String Path to the plugin main file
	 */
	function __construct($namespace, $laravelAppBoostrapFilePath = null, $userClass = null, $pluginMainFilePath = null)
	{
		$this->namespace = $namespace;
		
		if ($pluginMainFilePath) {
			$this->setPluginMainFilePath($pluginMainFilePath);
		}

		if ($laravelAppBoostrapFilePath) {
			$this->setLaravelAppBoostrapFilePath($laravelAppBoostrapFilePath);
		}

		if ($userClass) {
			$this->setUserClass($userClass);
		}
	}

	function getNamespace()
	{
		return $this->namespace;
	}

	/**
   * Alias for Bridge::app, for semantic purposes.
   */
 	function bootstrap($make = null)
 	{
 		return $this->app($make);
 	}

 	function config($path)
 	{
 		return $this->app('config')->get($path);
 	}

 	function input($name, $default = null)
	{
		return $this->app('request')->input($name, $default);
	}

	function __call($name, $args)
	{
		$this->bootstrap();
		return call_user_func_array([$this->app, $name], $args);
	}

 	/**
 	 * Set the user class to be used for producing the current
 	 * user instance.
 	 * @see Bridge::user()
 	 */
 	function setUserClass($userClass)
 	{
 		$this->userClass = $userClass;
 		return $this;
 	}

	/**
	 * Access the Laravel app on the other side of this Bridge.
	 * If the app has not been created, it will be when this function is called.
	 * @param mixed Can be null or a string
	 * @return If `$make` is a string, returns Application::make($make); 
	 * otherwise, the Application instance is returned.
	 */
	function app($make = null)
	{	
		if (!$this->hasLaravelApp()) {
			throw new \Exception("Can't create Laravel app with this router: no path to Laravel app boostrap file was provided");
		}
		if (empty($this->app)) {
			$this->app = require $this->laravelAppBoostrapFilePath;
		}
		return !empty($make) ? $this->app->make($make) : $this->app;
	}

	/**
	 * @return true when this Router was configured with a path
	 * to a Laravel bootstrap file; otherwise, false
	 */
	function hasLaravelApp()
	{
		return !empty($this->laravelAppBoostrapFilePath);
	}

	/**
	 * @param String The path to a Laravel bootstrap file
	 * @return Router
	 */
	function setLaravelAppBoostrapFilePath($filePath)
	{
		if (!file_exists($filePath)) {
			throw new \Exception("Can't find Laravel bootstrap file at {$filePath}");
		}
		$this->laravelAppBoostrapFilePath = $filePath;
		return $this;
	}

	/**
	 * @param String The path to a plugin main file
	 * @return Router
	 */
	function setPluginMainFilePath($filePath)
	{
		if (!file_exists($filePath)) {
			throw new \Exception("Can't find plugin main file at {$filePath}");
		}
		$this->pluginMainFilePath = $filePath;
		return $this;
	}

	/**
	 * Register an activation hook that executions 
	 * any database migrations.
	 * @param String Enable migration for some other plugin main file
	 * path other than the one this Bridge is already configured to
	 * look at; (this is for backwards compatability)
	 */
	function enableMigrationOnPluginActivation($pluginMainFilePath = null)
	{
		if (empty($pluginMainFilePath)) {
			if (empty($this->pluginMainFilePath)) {
				throw new \Exception("Can't enable migration with this Bridge: no path to plugin main file has been provided.");
			}
			$pluginMainFilePath = $this->pluginMainFilePath;
		}
		if (empty($this->laravelAppBoostrapFilePath)) {
			throw new \Exception("Can't enable migration with this Bridge: no path to Laravel app boostrap file was provided");
		}
		register_activation_hook($pluginMainFilePath, function() {
			$this->bootstrap();

			// TODO: I thought it was necessary to make sure
			// this had already been done, but maybe it isn't...?
			// Maybe it runs automatically...?
			// if (!Schema::hasTable('fpc_migrations')) {
			// 	Artisan::call('migrate:install');
			// }			

			Artisan::call('migrate', ['--force' => '1']);
		});
	}

	/**
	 * Enable Eloquent for manipulating data in the database.
	 * @deprecated since 1.0.7
	 * @return Illuminate\Database\Capsule\Manager
	 * @see Bridge::bootEloquent()
	 */
	function enableEloquent()
	{
		$this->bootEloquent();
	}

	/**
	 * Enable Eloquent for manipulating data in the database.
	 * @return Illuminate\Database\Capsule\Manager
	 */
	function bootEloquent()
	{
		global $table_prefix;

		// if this Bridge is configured with a Laravel App,
		// then we let the app setup Eloquent
		if ($this->hasLaravelApp()) {
			$this->bootstrap();
			return true;
		}

		$capsule = static::getDatabaseManager();

		$this->addConnection([
			'driver' => 'mysql',
			'host' => DB_HOST,
			'database' => DB_NAME,
			'username' => DB_USER,
			'password' => DB_PASSWORD,
			'charset' => DB_CHARSET,
			'collation' => DB_COLLATE,
			'prefix' => $table_prefix,
		], 'default');

		$capsule->bootEloquent();

		return $capsule;
	}

	/**
	 * @param mixed Connection arguments
	 * @param String The name to identify this connection by; defaults to 'default'
	 * @return Illuminate\Database\Capsule\Manager
	 * @see http://github.com/illuminate/database
	 */
	function addConnection($connection = '', $name = 'default')
	{
		$capsule = static::getDatabaseManager();

		try {
			if ($capsule->getConnection($name)) {
				return $capsule;
			}
		} catch (\InvalidArgumentException $e) {
			// ignore; it's just because the connection doesn't exist
		}

		$capsule->addConnection(wp_parse_args($connection), $name);
		
		return $capsule;
	}

	/**
	 * Setup the database manager.
	 * @return Illuminate\Database\Capsule\Manager
	 */
	static function getDatabaseManager()
	{
		if (empty(static::$databaseManager)) {
			static::$databaseManager = new \Illuminate\Database\Capsule\Manager;
			static::$databaseManager->setAsGlobal();
		}
		return static::$databaseManager;
	}

	/**
	 * Make it possible to run Laravel artisan within 
	 * the app associated with this Bridge.
	 */
	function enableArtisanCLI()
	{
		if (empty($this->laravelAppBoostrapFilePath)) {
			throw new \Exception("Can't cenable artisan CLI with this Bridge: no path to Laravel app boostrap file was provided");
		}
		
		add_action('init', function() {
			if (!class_exists('WP_CLI')) {
				return false;
			}
			
			/**
			 * Run Laravel artisan from within this Illuminated WordPress application
			 */
			\WP_CLI::add_command($this->namespace, function($args) {
				$this->bootstrap();

				if (empty($args))	{
					\WP_CLI::error("Unknown artisan command"); 
					exit;
				}
				
				Artisan::call(array_shift($args), array_reduce($args, function($result, $arg) {
					@list($name, $value) = explode('=', $arg);
					$result[$name] = $value ? $value : 1;
					return $result;
				}, []));

				\WP_CLI::log(Artisan::output());
			});
		});
	}

	function makePostTypeSearchable($postType)
	{
		return Configurator::make($this, $postType);
	}

	private $attributes = [];

	function __get($name)
	{
		if ($name === 'user') {
			if (empty($this->attributes['user'])) {
				$user = $this->user();
				if (!empty($user)) {
					$this->attributes['user'] = $user;
				}
				return $user;
			} else {
				return $this->attributes['user'];
			}
		}

		return null;
	}

	/**
	 * Get the current user, if any.
	 * @return User
	 */
	function user($className = null, $id = null)
	{
		$this->bootstrap();
		if (empty($className)) {
			$className = $this->userClass;
		}
		$builder = new $className;
		if (!empty($id)) {
			$user = $builder->find($id);
		} else {
			$user = $builder->current();
		}
		return $user;
	}


}