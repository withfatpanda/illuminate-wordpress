<?php
namespace FatPanda\Illuminate\WordPress\Models;

use FatPanda\Illuminate\WordPress\Plugin;
use FatPanda\Illuminate\WordPress\Concerns\CustomSchema;

abstract class Taxonomy implements CustomSchema {

	protected $tax_type = 'custom_taxonomy';

	protected $text_domain = null;

	protected $object_types = [ 'post' ];

	protected $tax_singular_name = 'Taxonomy';

	protected $tax_plural_name = 'Taxonomies';

	protected $hierarchical = false;

	protected $public = true;

	protected $publicly_queryable = null;

	protected $show_ui = null;

	protected $show_admin_column = null;

	protected $show_in_nav_menus = null;

	protected $show_tagcloud = null;

	protected $meta_box_cb = null;

	public function buildConfig(Plugin $plugin)
	{
		$labels = array(
			'name'                       => _x( $this->tax_plural_name, 'Taxonomy General Name', $this->text_domain ),
			'singular_name'              => _x( $this->tax_singular_name, 'Taxonomy Singular Name', $this->text_domain ),
			'menu_name'                  => __( $this->tax_plural_name, $this->text_domain ),
			'all_items'                  => __( 'All Items', $this->text_domain ),
			'parent_item'                => __( 'Parent Item', $this->text_domain ),
			'parent_item_colon'          => __( 'Parent Item:', $this->text_domain ),
			'new_item_name'              => __( 'New Item Name', $this->text_domain ),
			'add_new_item'               => __( 'Add New Item', $this->text_domain ),
			'edit_item'                  => __( 'Edit Item', $this->text_domain ),
			'update_item'                => __( 'Update Item', $this->text_domain ),
			'view_item'                  => __( 'View Item', $this->text_domain ),
			'separate_items_with_commas' => __( 'Separate items with commas', $this->text_domain ),
			'add_or_remove_items'        => __( 'Add or remove items', $this->text_domain ),
			'choose_from_most_used'      => __( 'Choose from the most used', $this->text_domain ),
			'popular_items'              => __( 'Popular Items', $this->text_domain ),
			'search_items'               => __( 'Search Items', $this->text_domain ),
			'not_found'                  => __( 'Not Found', $this->text_domain ),
			'no_terms'                   => __( 'No items', $this->text_domain ),
			'items_list'                 => __( 'Items list', $this->text_domain ),
			'items_list_navigation'      => __( 'Items list navigation', $this->text_domain ),
		);

		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => $this->hierarchical,
			'public'                     => $this->public,
			'publicly_queryable'				 => is_null($this->publicly_queryable) ? $this->public : $this->publicly_queryable,
			'show_ui'                    => is_null($this->show_ui) ? $this->public : $this->show_ui,
			'show_admin_column'          => is_null($this->show_admin_column) ? $this->public : $this->show_admin_column,
			'show_in_nav_menus'          => is_null($this->show_in_nav_menus) ? $this->public : $this->show_in_nav_menus,
			'show_tagcloud'              => is_null($this->show_ui) ? $this->public : $this->show_tagcloud,
			'meta_box_cb'								 => $this->meta_box_cb,
		);

		return $args;
	}

	protected function getObjectTypes()
	{
		return $this->object_types;
	}

	static function register(Plugin $plugin)
	{
		$instance = new static();

		register_taxonomy($instance->tax_type, $instance->getObjectTypes(), $instance->buildConfig($plugin));
	}

}