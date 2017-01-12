<?php
namespace FatPanda\Illuminate\WordPress;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Comment extends Eloquent {

	protected $table = 'comments';

	protected $primaryKey = 'comment_ID';

	protected $type = null;

	public function __construct($attributes = [])
	{
		parent::__construct($attributes);

		$this->setAttribute('comment_type', $this->getCommentType());
	}

	function user()
	{
		return $this->belongsTo('FatPanda\Illuminate\WordPress\Models\User', 'ID', 'user_id');
	}

	function post()
	{
		return $this->belongsTo('FatPanda\Illuminate\WordPress\Models\Post', 'ID', 'comment_post_ID');
	}

	function parent()
	{
		return $this->belongsTo('FatPanda\Illuminate\WordPress\Models\Comment', 'comment_ID', 'comment_parent');
	}

	function getIdAttribute()
	{
		return !empty($this->attributes['comment_ID']) ? $this->attributes['comment_ID'] : null;
	}

	function setIdAttribute($value)
	{
		$this->attributes['comment_ID'] = $value;
	}

	function setPostIdAttribute($value)
	{
		$this->attributes['comment_post_ID'] = $value;
	}

	function getPostIdAttribute()
	{
		return !empty($this->attributes['comment_post_ID']) ? $this->attributes['comment_post_ID'] : null;
	}

	function setNumberAttribute($value)
	{
		$this->attributes['comment_karma'] = $value;
	}

	function getNumberAttribute()
	{
		if (!empty($this->attributes['comment_karma'])) ? $this->attributes['comment_karma'] : null;
	}

	public function getCommentType()
	{
		$type = $this->type;
		if (is_null($type)) {
			$type = basename(str_replace('\\', '/', strtolower(get_class($this))));
		}
		return $type;
	}

	public function newQuery($excludeDeleted = true)
	{	
		$builder = parent::newQuery($excludeDeleted);

		if ('comment' !== ( $post_type = $this->getCommentType() )) {
			$builder->where('comment_type', $comment_type);
		}

		return $builder;
	}

}