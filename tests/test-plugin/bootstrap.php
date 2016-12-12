<?php
/*
Plugin Name: 		Test Plugin
Plugin URI:  		https://github.com/withfatpanda/illuminate-wordpress
Description: 		A Plugin for testing features of illuminate-wordpress
Version:     		1.0.0
Author:      		Fat Panda
Author URI:  		https://github.com/withfatpanda
License:     		GPL2
License URI: 		https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: 		test-plugin
Domain Path: 		/resources/lang
*/

// If the plugin has been packed for distribution, autoload.php will be there
@include_once __DIR__.'/vendor/autoload.php';
// Allow for overriding pluggable core functions
require_once __DIR__.'/src/functions.php';
// Initialize the plugin
FatPanda\Illuminate\WordPress\Plugin::bootstrap(__FILE__);