<?php
namespace FatPanda\Illuminate\WordPress\Http;

use ArrayAccess;

use Illuminate\Support\Collection;

class RouteGroup implements ArrayAccess {

	protected $routes;

	function __construct()
	{
		$this->routes = new Collection;
	}

	function when($callback)
	{
		$this->routes->each(function($route) use ($callback) {
			$route->when($callback);
		});
		return $this;
	}

	function where($arg, $regex)
	{
		$this->routes->each(function($route) use ($arg, $regex) {
			$route->when($arg, $regex);
		});
		return $this;
	}

	function offsetExists($offset) 
	{
		return $this->routes->offsetExists($offset);
	}

	function offsetGet($offset)
	{
		return $this->routes->offsetGet($offset);
	}

	function offsetSet($offset, $value)
	{
		return $this->routes->offsetSet($offset, $value);
	}

	function offsetUnset($offset)
	{
		return $this->routes->offsetUnset($offset);
	}

}