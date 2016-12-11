<?php
$router->get('/plugin-data/{property?}', function($request) use ($plugin) {
	return $plugin->getPluginData($request['property']);
});