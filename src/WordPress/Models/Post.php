<?php 
namespace FatPanda\Illuminate\WordPress;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Collection;

class Post extends Eloquent {

	protected $table = 'posts';

	protected $primaryKey = 'ID';

	protected $fillable = ['id'];

	protected $wp_error = null;

	protected $postAttributes = [
		'ID',
		'id',
		'post_author',
		'post_date',
		'post_date_gmt',
		'post_content',
		'post_content_filtered',
		'post_title',
		'post_excerpt',
		'post_status',
		'post_type',
		'comment_status',
		'ping_status',
		'post_password',
		'post_name',
		'to_ping',
		'pinged',
		'post_modified',
		'post_modified_gmt',
		'post_parent',
		'menu_order',
		'post_mime_type',
		'guid'
	];

	function getIdAttribute()
	{
		return !empty($this->attributes['ID']) ? $this->attributes['ID'] : null;
	}

	function setIdAttribute($value)
	{
		return $this->attributes['ID'] = $value;
	}

	function getTitleAttribute()
	{
		return (object) [ 
			'content' => $this->post_title,
			'rendered' => apply_filters('the_title', $this->post_title) 
		];
	}

	/**
   * Perform a model update operation.
   *
   * @param  \Illuminate\Database\Eloquent\Builder  $query
   * @param  array  $options
   * @return bool
   */
  protected function performUpdate(Builder $query, array $options = [])
  {
    $dirty = $this->getDirty();

    if (count($dirty) > 0) {
      // If the updating event returns false, we will cancel the update operation so
      // developers can hook Validation systems into their models and cancel this
      // operation if the model does not pass validation. Otherwise, we update.
      if ($this->fireModelEvent('updating') === false) {
        return false;
      }

      $attributes = $this->attributes;
      unset($attributes['wp_error']);

      $result = wp_update_post($attributes, array_key_exists('wp_error', $options) ? (bool) $options['wp_error'] : null);

	    if ($result) {

		    if (is_wp_error($result)) {
		    	$this->wp_error = $result;
		    	return false;
		    } else {
		    	$this->fireModelEvent('updated', false);
		    }

		  } else {
		  	// unknown error state
		  	return false;
		  }


    }

    return true;
  }

  /**
   * Perform a model insert operation.
   *
   * @param  \Illuminate\Database\Eloquent\Builder  $query
   * @param  array  $options
   * @return bool
   */
  protected function performInsert(Builder $query, array $options = [])
  {
  	// clear out last error
    $this->wp_error = null;

    // fire normal Eloquent event
    if ($this->fireModelEvent('creating') === false) {
        return false;
    }

    $attributes = $this->attributes;

    unset($attributes['wp_error']);

    $result = wp_insert_post($attributes, array_key_exists('wp_error', $options) ? (bool) $options['wp_error'] : null);

    if ($result) {

	    if (is_wp_error($result)) {
	    	$this->wp_error = $result;
	    	return false;

	    } else {
	    	$this->ID = $result;

	    	// We will go ahead and set the exists property to true, so that it is set when
		    // the created event is fired, just in case the developer tries to update it
		    // during the event. This will allow them to do so and run an update here.
		    $this->exists = true;

		    $this->wasRecentlyCreated = true;

		    $this->fireModelEvent('created', false);

		    return true;
	    }

	  } else {
	  	// unknown error state
	  	return false;
	  }
    
 	}

 	public function getWpError()
 	{
 		return $this->wp_error;
 	}

	function setTitleAttribute($value)
	{
		$this->attributes['post_title'] = $value;
	}

	function getContentAttribute()
	{
		return (object) [
			'rendered' => apply_filters('the_content', $this->post_content),
			'protected' => $this->post_status === 'private'
		];
	}

	function setContentAttribute($value)
	{
		$this->attributes['post_content'] = $value;
	}

	function getGuidAttribute()
	{
		return (object) [
			'rendered' => site_url('?'.esc_attr(http_build_query(['p' => $this->id, 'post_type' => $this->post_type])))
		];
	}

	function getExcerptAttribute()
	{
		return (object) [
			'rendered' => get_the_excerpt($this->id),
			'protected' => $this->post_status === 'private'
		];
	}

	function setExcerptAttribute($value)
	{
		$this->attributes['post_excerpt'] = $value;
	}

	/**
	 * @return Builder
	 */
	static function getPostByIdOrName($value)
	{
		if ($value instanceof static) {
			return $value;
		}
		return static::whereRaw('ID = ? OR post_name = ?', [ $value, $value ]);
	}

	function reactions()
	{

	}
	
	function meta()
	{
		return $this->hasMany('FatPanda\Illuminate\WordPress\PostMeta');
	}

	function comments()
	{
		return $this->hasMany('FatPanda\Illuminate\WordPress\Comment', 'ID', 'comment_post_ID');
	}

	function toArray()
	{
		$array = [
			'id' => $this->id,
			'date' => \Carbon\Carbon::parse($this->post_date)->format('c'),
			'date_gmt' => \Carbon\Carbon::parse($this->post_date_gmt)->format('c'),
			'guid' => $this->guid,
			'modified' => \Carbon\Carbon::parse($this->post_modified)->format('c'),
			'modified_gmt' => \Carbon\Carbon::parse($this->post_modified_gmt)->format('c'),
			'slug' => $this->post_name,
			'type' => $this->post_type,
			'link' => get_the_permalink($this->id),
			'title' => $this->title,
			'content' => $this->content,
			'excerpt' => $this->excerpt,
			'author' => $this->post_author,
			'parent' => $this->post_parent,
		];

		// TODO: featured_media, parent, menu_order, categories, tags

		if (function_exists('get_fields')) {
			$array['fields'] = get_fields($this->id);
		}

		return $array;
	}

}