<?php
namespace FatPanda\Illuminate\WordPress\Concerns;

use FatPanda\Illuminate\WordPress\Plugin;

/**
 * WordPress has two types of custom schema: PostTypes and Taxonomies.
 * CustomSchema must know how to register themselves when asked to, by
 * first building a config data set, and then by being able to register 
 * that data set.
 */
interface CustomSchema {

	function buildConfig(Plugin $plugin);

	static function register(Plugin $plugin);

}