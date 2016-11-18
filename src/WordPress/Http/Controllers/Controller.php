<?php
namespace FatPanda\Illuminate\WordPress\Http\Controllers;

use FatPanda\Illuminate\WordPress\Http\Router;

/**
 * A base class for Controllers in WordPress.
 */
class Controller {

	protected $router;

	function __construct(Router $router = null)
	{
		$this->router = $router;
	}

}