<?php
namespace FatPanda\Illuminate\WordPress;

use FatPanda\Illuminate\WordPress\Plugin;
use FatPanda\Illuminate\WordPress\Concerns\CanShortcode;

/**
 * Subclass this function to create a WordPress Shortcode.
 */ 
abstract class Shortcode implements CanShortcode {

  /**
   * @var String The shortcode tag name
   */
  protected $tag = '';

  /**
   * @var Plugin
   */
  protected $plugin;

  abstract public function render($atts, $content = null);

  protected function setPlugin(Plugin $plugin)
  {
    $this->plugin = $plugin;
  }
  
  static function register(Plugin $plugin)
  {
    $instance = new static();
    if (empty($instance->tag)) {
      throw new \Exception("Shortcode tag cannot be empty");
    }
    
    add_shortcode($instance->tag, function($atts, $content = '') use ($plugin) {
      $instance = new static();
      $instance->setPlugin($plugin);
      return $instance->render($atts, $content);
    });
  }


}