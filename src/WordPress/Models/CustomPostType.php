<?php 
namespace FatPanda\Illuminate\WordPress\Models;

abstract class CustomPostType extends Post {

	protected $primaryKey = 'ID';

	protected $post_type = 'post_type';

	protected $text_domain = 'text_domain';

	protected $post_type_singular_name = 'Post Type';

	protected $post_type_plural_name = 'Post Types';

	protected $post_type_description = 'Post Type Description';

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

	protected $capability_type = 'page';

	protected $wp_error = null;

	protected $show_in_rest = false;

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

	protected function buildConfig() {

		$labels = array(
			'name'                  => _x( $this->post_type_plural_name, 'Post Type General Name', $this->text_domain ),
			'singular_name'         => _x( $this->post_type_singular_name, 'Post Type Singular Name', $this->text_domain ),
			'menu_name'             => __( $this->post_type_plural_name, $this->text_domain ),
			'name_admin_bar'        => __( $this->post_type_singular_name, $this->text_domain ),
			'archives'              => __( "{$this->post_type_singular_name} Archives", $this->text_domain ),
			'parent_item_colon'     => __( "Parent {$this->post_type_singular_name}:", $this->text_domain ),
			'all_items'             => __( "All {$this->post_type_plural_name}", $this->text_domain ),
			'add_new_item'          => __( "Add New {$this->post_type_singular_name}", $this->text_domain ),
			'add_new'               => __( "Add New", $this->text_domain ),
			'new_item'              => __( "New {$this->post_type_singular_name}", $this->text_domain ),
			'edit_item'             => __( "Edit {$this->post_type_singular_name}", $this->text_domain ),
			'update_item'           => __( "Update {$this->post_type_singular_name}", $this->text_domain ),
			'view_item'             => __( "View {$this->post_type_singular_name}", $this->text_domain ),
			'search_items'          => __( "Search {$this->post_type_singular_name}", $this->text_domain ),
			'not_found'             => __( 'Not found', $this->text_domain ),
			'not_found_in_trash'    => __( 'Not found in Trash', $this->text_domain ),
			'featured_image'        => __( 'Featured Image', $this->text_domain ),
			'set_featured_image'    => __( 'Set featured image', $this->text_domain ),
			'remove_featured_image' => __( 'Remove featured image', $this->text_domain ),
			'use_featured_image'    => __( 'Use as featured image', $this->text_domain ),
			'insert_into_item'      => __( "Insert into {$this->post_type_singular_name}", $this->text_domain ),
			'uploaded_to_this_item' => __( "Uploaded to this {$this->post_type_singular_name}", $this->text_domain ),
			'items_list'            => __( "{$this->post_type_plural_name} list", $this->text_domain ),
			'items_list_navigation' => __( "{$this->post_type_plural_name} list navigation", $this->text_domain ),
			'filter_items_list'     => __( "Filter {$this->post_type_plural_name} list", $this->text_domain ),
		);

		$args = array(
			'label'                 => __( $this->post_type_singular_name, $this->text_domain ),
			'description'           => __( $this->post_type_description, $this->text_domain ),
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

	/**
   * Perform a model update operation.
   *
   * @param  \Illuminate\Database\Eloquent\Builder  $query
   * @param  array  $options
   * @return bool
   */
  protected function performUpdate(\Illuminate\Database\Eloquent\Builder $query, array $options = [])
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
  protected function performInsert(\Illuminate\Database\Eloquent\Builder $query, array $options = [])
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

	public function __construct($attributes = [])
	{
		parent::__construct($attributes);
		$this->setAttribute('post_type', $this->post_type);
	}

	public function newQuery($excludeDeleted = true)
	{	
		$builder = parent::newQuery($excludeDeleted);
		if ('FatPanda\WordPress\Core\Models\CustomPostType' !== get_class($this)) {
			$builder->where('post_type', $this->post_type);
		}
		return $builder;
	}

	protected function supports()
	{
		$supports = [];

		if ($this->supportsTitle()) {
			$supports[] = 'title';
		}

		if ($this->supportsEditor()) {
			$supports[] = 'editor';
		}

		if ($this->supportsExcerpt()) {
			$supports[] = 'excerpt';
		}

		if ($this->supportsAuthor()) {
			$supports[] = 'author';
		}

		if ($this->supportsThumbnail()) {
			$supports[] = 'thumbnail';
		}

		if ($this->supportsComments()) {
			$supports[] = 'comments';
		}		

		if ($this->supportsTrackbacks()) {
			$supports[] = 'trackbacks';
		}

		if ($this->supportsRevisions()) {
			$supports[] = 'revisions';
		}

		if ($this->supportsCustomFields()) {
			$supports[] = 'custom-fields';
		}

		if ($this->supportsCustomFields()) {
			$supports[] = 'custom-fields';
		}

		if ($this->supportsPageAttributes()) {
			$supports[] = 'page-attributes';
		}

		if ($this->supportsPageFormats()) {
			$supports[] = 'page-formats';
		}

		return $supports;
	}

	protected function supportsTitle()
	{
		return true;
	}

	protected function supportsEditor()
	{
		return true;
	}

	protected function supportsExcerpt()
	{
		return true;
	}

	protected function supportsAuthor()
	{
		return true;
	}

	protected function supportsThumbnail()
	{
		return false;
	}

	protected function supportsComments()
	{
		return true;
	}

	protected function supportsTrackbacks()
	{
		return true;
	}

	protected function supportsRevisions()
	{
		return true;
	}

	protected function supportsCustomFields()
	{
		return true;	
	}

	protected function supportsPageAttributes()
	{
		return true;
	}

	protected function supportsPageFormats()
	{
		return true;
	}

	protected function getTaxonomies()
	{
		return $this->taxonomies;
	}

	static function register()
	{
		$instance = new static();

		if ($instance->post_type === 'post_type') {
			$class = get_class($instance);
			throw new \Exception("Post type for {$class} has not been set; set protected property \$post_type to a valid and available post type");
		}

		register_post_type($instance->post_type, $instance->buildConfig());
	}


}
