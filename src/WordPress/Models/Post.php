<?php 
namespace FatPanda\Illuminate\WordPress\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class Post extends Eloquent {

	protected $table = 'posts';

	protected $primaryKey = 'ID';

	protected $fillable = [ 'id' ];

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

	static function boot()
	{
		// TODO: setup events for integrating CRUD operation back into WordPress
	}

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

	function setUpdatedAtAttribute($value)
	{
		$this->attributes['post_modified_gmt'] = Carbon::parse($value);
		// TODO: update post_modified accordingly
	}

	function setCreatedAtAttribute($value)
	{
		$this->attributes['post_date_gmt'] = Carbon::parse($value);
		// TODO: update post_date accordingly
	}

	function getUpdatedAtAttribute()
	{
		return !empty($this->attributes['post_modified_gmt']) ? Carbon::parse($this->attributes['post_modified_gmt']) : null;
	}

	function getCreatedAtAttribute()
	{
		return !empty($this->attributes['post_date_gmt']) ? Carbon::parse($this->attributes['post_date_gmt']) : null;
	}
	
	function meta()
	{
		return $this->hasMany('FatPanda\Illuminate\WordPress\Models\PostMeta');
	}

	function comments()
	{
		return $this->hasMany('FatPanda\Illuminate\WordPress\Models\Comment', 'ID', 'comment_post_ID');
	}

	function toArray()
	{
		$array = [
			'id' => $this->id,
			'date' => Carbon::parse($this->post_date)->format('c'),
			'date_gmt' => Carbon::parse($this->post_date_gmt)->format('c'),
			'guid' => $this->guid,
			'modified' => Carbon::parse($this->post_modified)->format('c'),
			'modified_gmt' => Carbon::parse($this->post_modified_gmt)->format('c'),
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