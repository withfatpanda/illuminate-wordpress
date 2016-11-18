<?php 
namespace FatPanda\Illuminate\WordPress\Models;

use Laravel\Scout\Searchable;

class SearchablePost extends Post {

	use Searchable;

	protected $table = 'posts';

	protected $primaryKey = 'ID';

	protected $searchableAsCallback;

	protected $toSearchableArrayCallback;

	function searchableAs()
	{
		if ($this->searchableAsCallback) {
			$args = func_get_args();
			array_unshift($args, $this);
			$searchableAs = config('scout.prefix').call_user_func_array($this->searchableAsCallback, $args);
		} else {
			$searchableAs = config('scout.prefix').$this->getTable();
		}

		// print_r($searchableAs);	 exit;

		return apply_filters('searchable_as', $searchableAs, $this->post_type, $this);
	}

	function setSearchableAs($callback)
	{
		$this->searchableAsCallback = $callback;
	}

	function toSearchableArray()
	{
		if ($this->toSearchableArrayCallback) {
			$args = func_get_args();
			array_unshift($args, $this);
			$array = call_user_func_array($this->toSearchableArrayCallback, $args);
		} else {
			$array = $this->toArray();
		}

		// print_r($array); exit;

		return apply_filters('searchable_array', $array, $this->post_type, $this);
	}

	function setToSearchableArray($callback)
	{
		$this->toSearchableArrayCallback = $callback;
	}

	function publicFireModelEvent($event, $halt = false)
	{
		return $this->fireModelEvent($event, $halt);
	}


}