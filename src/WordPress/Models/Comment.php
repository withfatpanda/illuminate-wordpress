<?php
namespace FatPanda\Illuminate\WordPress;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Comment extends Eloquent {

	protected $table = 'comments';

	protected $primaryKey = 'comment_ID';

	function user()
	{
		return $this->belongsTo('FatPanda\Illuminate\WordPress\Models\User', 'ID', 'user_id');
	}

	function post()
	{
		return $this->belongsTo('FatPanda\Illuminate\WordPress\Models\Post', 'ID', 'user_id');
	}

	function parent()
	{
		return $this->belongsTo('FatPanda\Illuminate\WordPress\Models\Comment', 'comment_ID', 'comment_parent');
	}

}