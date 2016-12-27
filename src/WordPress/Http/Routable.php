<?php
namespace FatPanda\Illuminate\WordPress\Http;

abstract class Routable {

	protected $permissionCallback;
	
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
		$args = $arg;

		if (!is_array($args)) {
			$args = [];
			$args[$arg] = $regex;
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


}