<?php
use FatPanda\Illuminate\WordPress\Plugin;

if (!function_exists('plugin')) {

	/**
	 * Helper function for getting a singleton instance of the named plugin
	 * @param string The plugin name
	 * @return FatPanda\Illuminate\WordPress\Plugin
	 */
	function plugin($name)
	{
		return Plugin::getInstance($name);
	}

}