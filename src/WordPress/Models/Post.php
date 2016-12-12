<?php 
namespace FatPanda\Illuminate\WordPress\Models;

use FatPanda\Illuminate\WordPress\Plugin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as Eloquent;
use FatPanda\Illuminate\WordPress\Concerns\CustomSchema;
use FatPanda\Illuminate\WordPress\Concerns\CustomizablePostType;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Carbon\Carbon;
use FatPanda\Illuminate\WordPress\Models\PostMeta;

class Post extends Eloquent implements Customschema {

	use CustomizablePostType;

	protected $table = 'posts';

	protected $primaryKey = 'ID';

	protected $fillable = [ 'id' ];

	const CREATED_AT = 'post_date_gmt';

	const UPDATED_AT = 'post_modified_gmt';

	protected $dates = [ 'post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt' ];

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

	protected $type = null;

	protected $text_domain = null;

	protected $called = null;

	protected $called_plural = null;

	protected $description = null;

	protected $taxonomies = [ 'category', 'post_tag '];

	protected $menu_position = 5;

	protected $public = false;

	protected $hierarchical = false;

	protected $show_ui = null;

	protected $show_in_menu = null;

	protected $show_in_admin_bar = null;

	protected $show_in_nav_menus = null;

	protected $can_export = null;

	protected $has_archive = true;

	protected $exclude_from_search = null;

	protected $publicly_queryable = null;

	protected $capability_type = 'post';

	protected $show_in_rest = false;

	protected $dirtyMetaData = [];	

	static function boot()
	{
		static::saving(function($post) {
			// make sure this is legit; prevents hierarchy loops:
			$post->parent = $post->getParentAttribute();

			// make sure this is unique:
			$post->slug = $post->getSlugAttribute(); 

			if ($post->id) {
				do_action( 'pre_post_update', $post->id, $post->getAttributes() );
			}
		});

		// persist meta data
		static::saved(function($post) {
			foreach($post->dirtyMetaData as $key => $value) {
				$meta = PostMeta::firstOrNew([ 
					'post_id' => $post->id, 
					'meta_key' => '_' . $key
				]);
					
				$meta->key = '_' . $key;
				$meta->value = $value;
				$meta->save();
				
				unset($post->dirtyMetaData[$key]);
			}
		});

		// TODO: setup events for integrating CRUD operation back into WordPress
	}

	public function __construct($attributes = [])
	{
		parent::__construct($attributes);

		$this->setAttribute('post_type', $this->getPostType());
	}

	public function getParentAttribute()
	{
		if (!empty($this->attributes['post_parent'])) {
			$post_parent = (int) $this->attributes['post_parent'];
		} else {
			$post_parent = 0;
		}

		return apply_filters( 'wp_insert_post_parent', $post_parent, $this->id, compact( array_keys( $this->attributes ) ), $this->attributes );
	}

	public function setParentAttribute($value)
	{
		$post_parent = (int) $value;
		if (empty($post_parent)) {
			$post_parent = 0;
		}

		$this->attributes['post_parent'] = apply_filters( 'wp_insert_post_parent', $post_parent, $this->id, compact( array_keys( $this->attributes ) ), $this->attributes );
	}

	public function getPostType()
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
		if ('post' !== ( $post_type = $this->getPostType() )) {
			$builder->where('post_type', $post_type);
		}
		return $builder;
	}

	/**
	 * What should this post type be called on screen?
	 * To set a specific value, override the protected property $called;
	 * note that for the base Post and Page types, this value is ingored
	 * @param int Context for the label; if $count is 1, the singular form
	 * will be returned; otherwise, the label will be plural.
	 * @see \Illuminate\Support\Str::plural
	 * @return string
	 */
	public function getCalled($count = 1)
	{
		$called = $this->called;
		if (is_null($called)) {
			$called = basename(str_replace('\\', '/', get_class($this)));
		}
		
		if ($count === 1) {
			return $called;

		} else {
			$plural = $this->called_plural;
			if (is_null($plural)) {
				$plural = Str::plural($called);
			}
			return $plural;

		}
	}

	function getIdAttribute()
	{
		return !empty($this->attributes['ID']) ? $this->attributes['ID'] : null;
	}

	function setIdAttribute($value)
	{
		return $this->attributes['ID'] = $value;
	}

	function getSlugAttribute()
	{
		$post_name = null;
		if (!empty($this->attributes['post_name'])) {
			$post_name = $this->attributes['post_name'];
		}

		if ( empty($post_name) ) {
			if ( !in_array( $this->status, array( 'draft', 'pending', 'auto-draft' ) ) ) {
				$post_name = sanitize_title($this->title->content);
			} else {
				$post_name = '';
			}
		} else {
			// On updates, we need to check to see if it's using the old, fixed sanitization context.
			$check_name = sanitize_title( $post_name, '', 'old-save' );
			if ( $this->id && strtolower( urlencode( $post_name ) ) == $check_name && get_post_field( 'post_name', $this->id ) == $check_name ) {
				$post_name = $check_name;
			} else { // new post, or slug has changed.
				$post_name = sanitize_title($post_name);
			}
		}

		return wp_unique_post_slug($post_name, $this->id, $this->status, $this->getPostType(), $this->parent);
	}

	function setSlugAttribute($value)
	{
		$this->attributes['post_name'] = $value;
	}

	function getStatusAttribute()
	{
		return !empty($this->attributes['post_status']) ? $this->attributes['post_status'] : 'publish';
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
			'rendered' => site_url('?'.esc_attr(
				http_build_query([
					'p' => $this->id, 
					'post_type' => $this->getPostType()
				]))
			)
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

	/**
   * Set a given attribute on the model.
   *
   * @param  string  $key
   * @param  mixed  $value
   * @return $this
   */
  public function setAttribute($key, $value)
  {
    // First we will check for the presence of a mutator for the set operation
    // which simply lets the developers tweak the attribute as it is set on
    // the model, such as "json_encoding" an listing of data for storage.
    if ($this->hasSetMutator($key)) {
      $method = 'set'.Str::studly($key).'Attribute';

      return $this->{$method}($value);
    }

    // If an attribute is listed as a "date", we'll convert it from a DateTime
    // instance into a form proper for storage on the database tables using
    // the connection grammar's date format. We will auto set the values.
    elseif ($value && (in_array($key, $this->getDates()) || $this->isDateCastable($key))) {
      $value = $this->fromDateTime($value);
    }

    if ($this->isJsonCastable($key) && ! is_null($value)) {
      $value = $this->asJson($value);
    }

    // If this attribute contains a JSON ->, we'll set the proper value in the
    // attribute's underlying array. This takes care of properly nesting an
    // attribute in the array's value in the case of deeply nested items.
    if (Str::contains($key, '->')) {
      return $this->fillJsonAttribute($key, $value);
    }

    if (!in_array($key, $this->postAttributes)) {
    	return $this->dirtyMetaData[$key] = $value;
    }

    $this->attributes[$key] = $value;

    return $this;
  }

  /**
   * Get a relationship.
   *
   * @param  string  $key
   * @return mixed
   */
  public function getRelationValue($key)
  {
  	// If the key already exists in the relationships array, it just means the
    // relationship has already been loaded, so we'll just return it out of
    // here because there is no need to query within the relations twice.
    if ($this->relationLoaded($key)) {
      return $this->relations[$key];
    }

    // If the "attribute" exists as a method on the model, we will just assume
    // it is a relationship and will load and return results from the query
    // and hydrate the relationship's value on the "relationships" array.
    if (method_exists($this, $key)) {
      return $this->getRelationshipFromMethod($key);
    }

    $meta = PostMeta::where([ 
    	'post_id' => $this->id, 
    	'meta_key' => '_' . $key
    ])->first();
    
    if ($meta) {
    	return $meta->value;
    }
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
			'slug' => $this->slug,
			'type' => $this->getPostType(),
			'link' => get_the_permalink($this->id),
			'title' => $this->title,
			'content' => $this->content,
			'excerpt' => $this->excerpt,
			'author' => $this->post_author,
			'parent' => $this->parent,
			'fields' => [],
			'meta' => []
		];

		// TODO: featured_media, parent, menu_order, categories, tags
		if (function_exists('get_fields')) {
			$array['fields'] = get_fields($this->id);
		}

		$fields = array_keys($array['fields']);

		$meta = get_post_meta($this->id);

		foreach($meta as $key => $value) {
			// If the meta key is prefixed with an '_', then
			// we treat it as a top-level property of the post
			if (substr($key, 0, 1) === '_') {
				$array[substr($key, 1)] = $value;	

			// If the meta data hasn't already been loaded as
		  // a field, then we append it to the meta data list
			} else if (!in_array($key, $fields)) {
				$array['meta'][$key] = $value;
			}
		}

		return $array;
	}


	protected function getTaxonomies()
	{
		return $this->taxonomies;
	}

	public function buildConfig(Plugin $plugin) 
	{

		$text_domain = $this->text_domain;
		if (is_null($text_domain)) {
			$text_domain = $plugin->textDomain;
		}

		$called = $this->getCalled();
		$plural = $this->getCalled(2);

		$labels = array(
			'name'                  => _x( $plural, 'Post Type General Name', $text_domain ),
			'singular_name'         => _x( $called, 'Post Type Singular Name', $text_domain ),
			'menu_name'             => __( $plural, $text_domain ),
			'name_admin_bar'        => __( $called, $text_domain ),
			'archives'              => __( "{$called} Archives", $text_domain ),
			'parent_item_colon'     => __( "Parent {$called}:", $text_domain ),
			'all_items'             => __( "All {$plural}", $text_domain ),
			'add_new_item'          => __( "Add New {$called}", $text_domain ),
			'add_new'               => __( "Add New", $text_domain ),
			'new_item'              => __( "New {$called}", $text_domain ),
			'edit_item'             => __( "Edit {$called}", $text_domain ),
			'update_item'           => __( "Update {$called}", $text_domain ),
			'view_item'             => __( "View {$called}", $text_domain ),
			'search_items'          => __( "Search {$called}", $text_domain ),
			'not_found'             => __( 'Not found', $text_domain ),
			'not_found_in_trash'    => __( 'Not found in Trash', $text_domain ),
			'featured_image'        => __( 'Featured Image', $text_domain ),
			'set_featured_image'    => __( 'Set featured image', $text_domain ),
			'remove_featured_image' => __( 'Remove featured image', $text_domain ),
			'use_featured_image'    => __( 'Use as featured image', $text_domain ),
			'insert_into_item'      => __( "Insert into {$called}", $text_domain ),
			'uploaded_to_this_item' => __( "Uploaded to this {$called}", $text_domain ),
			'items_list'            => __( "{$plural} list", $text_domain ),
			'items_list_navigation' => __( "{$plural} list navigation", $text_domain ),
			'filter_items_list'     => __( "Filter {$plural} list", $text_domain ),
		);

		$args = array(
			'label'                 => __( $called, $text_domain ),
			'description'           => __( $this->description, $text_domain ),
			'labels'                => $labels,
			'supports'              => $this->supports(),
			'taxonomies'            => $this->getTaxonomies(),
			'hierarchical'          => $this->hierarchical,
			'public'                => $this->public,
			'show_ui'               => is_null($this->show_ui) ? $this->public : $this->show_ui,
			'show_in_menu'          => is_null($this->show_in_menu) ? $this->public : $this->show_in_menu,
			'menu_position'         => $this->menu_position,
			'show_in_admin_bar'     => is_null($this->show_in_admin_bar) ? $this->public : $this->show_in_admin_bar,
			'show_in_nav_menus'     => is_null($this->show_in_nav_menus) ? $this->public : $this->show_in_nav_menus,
			'can_export'            => is_null($this->can_export)? $this->public : $this->can_export,
			'has_archive'           => $this->has_archive,
			'exclude_from_search'   => is_null($this->exclude_from_search) ? !$this->public : $this->exclude_from_search,
			'publicly_queryable'    => is_null($this->publicly_queryable) ? $this->public : $this->publicly_queryable,
			'capability_type'       => $this->capability_type,
			'show_in_rest'					=> $this->show_in_rest,
		);

		return $args;
	}

	static function register(Plugin $plugin)
	{
		$instance = new static();

		register_post_type($instance->getPostType(), $instance->buildConfig($plugin));
	}

}