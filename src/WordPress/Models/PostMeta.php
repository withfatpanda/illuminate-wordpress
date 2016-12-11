<?php 
namespace FatPanda\Illuminate\WordPress\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class PostMeta extends Eloquent {

	protected $table = 'postmeta';

	protected $primaryKey = 'meta_id';

	function getIdAttribute()
	{
		return $this->meta_id;
	}

	function setIdAttribute($value)
	{
		$this->attributes['meta_id'] = $value;
	}

	function getValueAttribute()
	{
		return maybe_unserialize($this->meta_value);
	}

	function meta()
	{
		return $this->belongsTo('FatPanda\Illuminate\WordPress\Post', 'post_id', 'ID');
	}

}