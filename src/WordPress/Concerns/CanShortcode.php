<?php
namespace FatPanda\Illuminate\WordPress\Concerns;

use FatPanda\Illuminate\WordPress\Plugin;

/**
 * Any class implementing CanShortcode can be registered,
 * and function as a WordPress shortcode.
 */
interface CanShortcode {

  function render($atts, $content = null);

  static function register(Plugin $plugin);

}