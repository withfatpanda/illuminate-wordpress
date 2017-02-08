<?php
namespace FatPanda\Illuminate\WordPress\Models;

use FatPanda\Illuminate\WordPress\Plugin;
use FatPanda\Illuminate\WordPress\Concerns\CustomSchema;
use FatPanda\Illuminate\WordPress\Concerns\CustomizablePostMeta;

/**
 * Simple builder for ACF field groups.
 */
class FieldGroup implements CustomSchema {

  use CustomizablePostMeta;

  protected $id = null;

  protected $config = [];

  protected $fields = [];

  function config($config)
  {
    $this->config = $config;
    return $this;
  }

  function id($id)
  {
    $this->id = $id;
    return $this;
  }

  function title($title)
  {
    $this->config['title'] = $title;
    return $this;
  }

  function instructions($text)
  {
    $this->config['instructions'] = $text;
    return $this;
  }

  function field($config = null)
  {
    $fieldGroup = new FieldGroup();
    if ($config) {
      $fieldGroup->config($config);
    }
    $this->fields[] = $fieldGroup;
    return $this;
  }

  function repeater($config = null)
  {
    
  }

  function afterTitle()
  {
    if (empty($this->config['options'])) {
      $this->config['options'] = [];
    }
    $this->config['options']['position'] = 'acf_after_title';
    return $this;
  }

  function afterContent()
  {
    if (empty($this->config['options'])) {
      $this->config['options'] = [];
    }
    $this->config['options']['position'] = 'normal';
    return $this;
  }

  function sidebar()
  {
    if (empty($this->config['options'])) {
      $this->config['options'] = [];
    }
    $this->config['options']['position'] = 'side';
    return $this;
  }

  function seamless()
  {
    if (empty($this->config['options'])) {
      $this->config['options'] = [];
    }
    $this->config['options']['layout'] = 'no_box';
    return $this;
  }

  function metabox()
  {
    if (empty($this->config['options'])) {
      $this->config['options'] = [];
    }
    $this->config['options']['layout'] = 'default';
    return $this;
  }

  protected function hide($what)
  {
    if (empty($this->config['options'])) {
      $this->config['options'] = [];
    }
    if (empty($this->config['options']['hide_on_screen'])) {
      $this->config['options']['hide_on_screen'] = [];
    }
    $this->config['options']['hide_on_screen'][] = $what;
    return $this;
  }

  function hidePermalink()
  {
    return $this->hide('permalink');
  }

  function hideContent()
  {
    return $this->hide('the_content');
  }

  function hideExcerpt()
  {
    return $this->hide('excerpt');
  }

  function hideCustomFields()
  {
    return $this->hide('custom_fields');
  }

  function hideDiscussion()
  {
    return $this->hide('discussion');
  }

  function hideComments()
  {
    return $this->hide('comments');
  }

  function hideRevisions()
  {
    return $this->hide('revisions');
  }

  function hideSlug()
  {
    return $this->hide('slug');
  }

  function hideAuthor()
  {
    return $this->hide('author');
  }

  function hideFormat()
  {
    return $this->hide('format');
  }

  function hideFeaturedImage()
  {
    return $this->hide('featured_image');
  }

  function hideCategories()
  {
    return $this->hide('categories');
  }

  function hideTags()
  {
    return $this->hide('tags');
  }

  function hideTrackbacks()
  {
    return $this->hide('send-trackbacks');
  }

  function order(int $order) {
    $this->config['menu_order'] = $order;
    return $this;
  }

  /**
   * Build field group config
   * @return array
   */
  function buildConfig(Plugin $plugin)
  {
    if (!empty($this->fields)) {
      $this->config['fields'] = [];
      foreach($this->fields as $field) {
        $this->config['fields'][] = $field->buildConfig($plugin);
      }
    }

    if (!is_null($this->id)) {
      $this->config['id'] = $this->id;
    }

    return $this->config;
  }

  static function register(Plugin $plugin)
  {
    $instance = new static();

    if (function_exists('register_field_group')) {
      $class = get_called_class();
      $config = $instance->buildConfig($plugin);

      if (empty($config['id'])) {
        throw new \Exception("FieldGroup {$class} cannot have an empty ID");
      }

      register_field_group($config);
    }
  }

}