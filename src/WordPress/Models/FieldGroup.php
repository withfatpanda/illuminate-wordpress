<?php
namespace FatPanda\Illuminate\WordPress\Models;

use FatPanda\Illuminate\WordPress\Plugin;
use FatPanda\Illuminate\WordPress\Concerns\CustomSchema;
use FatPanda\Illuminate\WordPress\Concerns\CustomizablePostMeta;

/**
 * Simple encapsulation for ACF field groups.
 */
class FieldGroup implements CustomSchema {

  use CustomizablePostMeta;

  /**
   * Build field group config; by default this just
   * returns the config attached to this model.
   */
  public function buildConfig(Plugin $plugin)
  {
    return $this->config;
  }

  static function register(Plugin $plugin)
  {
    $instance = new static();

    if (function_exists('register_field_group')) {
      register_field_group($instance->buildConfig($plugin));
    }
  }

}