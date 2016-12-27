<?php
namespace FatPanda\Illuminate\WordPress\Http;

class RewriteRule extends Routable {

	/**
	 * @var string
	 */
	protected $route;

	/**
	 * @var mixed
	 */
	protected $action;

	/**
	 * @var string
	 */
	protected $after;

	function __construct(Router $router, $route, $action = 'index.php', $after = 'top')
	{
		if (did_action('init') || did_action('parse_request')) {
			throw new \Exception("Too late to create new rewrite rules");
		}

		$this->router = $router;
		$this->route = $route;
		$this->action = $action;
		$this->after = $after;

		if (!empty($options['permission_callback'])) {
			$this->permissionCallback = $options['permission_callback'];
		}

		add_action('init', [$this, '_init'], 1);
	}

	function _init()
	{
		global $wp;
		$query = null;

		$route = $this->route;

		// if redirect is a callable, just tell the rewrite rule
		// to send requests to index.php; then setup a callback in
		// parse_request to invoke $this->action
		if (is_callable($this->action)) {
			$query = 'index.php';

		// otherwise, parse $this->action as a URL string, and then make
		// sure that WP is aware of all of the query vars in the 
		// redirect string
		} else if ($parts = parse_url($this->action)) {
			$query = $this->action;

			if (!empty($parts['query'])) {
				$args = wp_parse_args($parts['query']);
				foreach(array_keys($args) as $name) {
					$wp->add_query_var($name);
				}
			}
		}

		// remove any preceeding forward slashes—the underlying 
		// rewrite engine doesn't like them
		$route = ltrim($route, '/');

		list($regex, $tokens) = Router::substituteUrlArgTokens($route);
		
		add_rewrite_rule($regex, $query, $this->after);

		foreach($tokens as $token) {
			$wp->add_query_var($token);
		}
		
		if (is_callable($this->action)) {

			add_action('parse_request', function($wp) use ($regex, $tokens) {
				if ($regex === $wp->matched_rule) {
					$request = $this->router->getApp()->request;
					
					preg_match('#'.$wp->matched_rule.'#', $wp->request, $matches);

					foreach($tokens as $token) {
						if (!empty($matches[$token])) {
							$request[$token] = $matches[$token];
						}
					}

					$result = call_user_func_array($this->action, [$request]);

					if (is_array($result)) {
						if (!empty($result['query'])) {
							$wp->query_vars = $result['query'];
						}
					}
				}
			});

		}

		if (defined('WP_DEBUG') && WP_DEBUG) {
			flush_rewrite_rules();
		}
	}

}