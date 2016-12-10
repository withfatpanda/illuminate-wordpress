<?php
namespace FatPanda\Illuminate\WordPress;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Comment extends Eloquent {

	protected $table = 'comments';

	protected $primaryKey = 'comment_ID';

	protected $wp_error;

	function user()
	{
		return $this->belongsTo('FatPanda\Illuminate\WordPress\User', 'ID', 'user_id');
	}

	function post()
	{
		return $this->belongsTo('FatPanda\Illuminate\WordPress\Post', 'ID', 'user_id');
	}

	function parent()
	{
		return $this->belongsTo('FatPanda\Illuminate\WordPress\Comment', 'comment_ID', 'comment_parent');
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

      $result = wp_update_comment($attributes, array_key_exists('wp_error', $options) ? (bool) $options['wp_error'] : null);

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

    $result = wp_insert_comment($attributes, array_key_exists('wp_error', $options) ? (bool) $options['wp_error'] : null);

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

}