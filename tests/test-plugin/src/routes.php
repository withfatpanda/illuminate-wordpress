<?php
$router->get('/plugin-data/{property?}', function($request) use ($plugin) {
	return !empty($request['property']) ? $plugin[$request['property']] : $plugin->pluginData;
});

$router->rewrite('test/basic', function() use ($plugin) {
	$plugin->hasTriggeredBasicRewriteRule = true;
});

$router->rewrite('type/{post_type}/{name?}', function($request) {
	return [
		'query' => [
			'post_type' => $request['post_type'],
			'name' => $request['name']
		]
	];
});