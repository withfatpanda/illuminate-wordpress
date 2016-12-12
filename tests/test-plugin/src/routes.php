<?php
$router->get('/plugin-data/{property?}', function($request) use ($plugin) {
	return !empty($request['property']) ? $plugin[$request['property']] : $plugin->pluginData;
});