<?php
namespace FatPanda\Illuminate\WordPress\Concerns;

trait CustomizablePostMeta {

  /**
   * @var array The stored config
   */
  protected $config;

  /**
   * Set the configuration for this ACF field group;
   * this function is fluent.
   * @param array
   * @return FieldGroup
   */
  public function setConfig($config)
  {
    $this->config = (array) $config;
    return $this;
  }

  /**
   * Get the config inside this FieldGroup
   * @return array
   */
  public function getConfig()
  {
    return $this->config;
  }

}