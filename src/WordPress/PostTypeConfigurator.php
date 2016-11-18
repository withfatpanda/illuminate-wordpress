<?php
namespace FatPanda\Illuminate\WordPress;

use FatPanda\Illuminate\WordPress\Bridge;
use FatPanda\Illuminate\WordPress\Models\SearchablePost;

/**
 * This Configurator establishes a relationship between WordPress
 * post events and our own Post model, thereby allowing for WordPress
 * state change events to trigger events within Laravel.
 */
class Configurator {

	protected static $instances;

	protected $postType;
	
	protected $bridge;

	protected $searchableAsCallback;
	
	protected $toSearchableArrayCallback;

	static function make(Bridge $bridge, $postType)
	{
		$namespace = $bridge->getNamespace();
		if (empty(self::$instances[$postType][$namespace])) {
			self::$instances[$postType][$namespace] = new self($bridge, $postType);
		}
		return self::$instances[$postType][$namespace];
	}	

	private function __construct($bridge, $postType)
	{
		$this->bridge = $bridge;
		$this->postType = $postType;

		$baseSearchableAsCallback = function(SearchablePost $post) {
			return 'posts_'.$post->post_type;
		};

		$baseToSearchableArrayCallback = function(SearchablePost $post) {
			$postdata = (object) $post->toArray();
			$rest = new \WP_REST_Posts_Controller($post->post_type);
			$response = $rest->prepare_item_for_response($postdata, []);
			$postdata = $response->data;

			if (function_exists('get_fields')) {
				$postdata['fields'] = get_fields($post->ID);
			}

			return $postdata;
		};

		add_action('save_post', function($post_ID, $post) use ($baseSearchableAsCallback, $baseToSearchableArrayCallback) {
			if ($post->post_type === $this->postType) {
				// now is when we have to startup Laravel container
				// doing it anytime before now incurs unnecessary load
				$this->bridge->app();
				
				$instance = SearchablePost::find($post_ID);
				
				$instance->setSearchableAs(function(SearchablePost $post) use ($baseSearchableAsCallback) {
					$searchableAs = $baseSearchableAsCallback($post);
					if ($this->searchableAsCallback) {
						$searchableAs = call_user_func_array($this->searchableAsCallback, [$post, $searchableAs]);
					}
					return $searchableAs;
				});
				
				$instance->setToSearchableArray(function(SearchablePost $post) use ($baseToSearchableArrayCallback) {
					$array = $baseToSearchableArrayCallback($post);
					if ($this->toSearchableArrayCallback) {
						$array = call_user_func_array($this->toSearchableArrayCallback, [$post, $array]);
					}
					return $array;
				});
				
				$instance->publicFireModelEvent('saved', false);
				$instance->searchable();
			}
		}, 10, 2);
	}	

	function searchableAs($callback)
	{
		$this->searchableAsCallback = $callback;
	}

	function toSearchableArray($callback)
	{
		$this->toSearchableArrayCallback = $callback;
	}

}