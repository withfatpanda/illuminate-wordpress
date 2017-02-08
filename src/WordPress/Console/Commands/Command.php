<?php
namespace FatPanda\Illuminate\WordPress\Console\Commands;

use FatPanda\Illuminate\WordPress\Plugin;
use Illuminate\Console\Command as BaseCommand;

/**
 * Allow for Illuminated WordPress plugins to
 * access Laravel via the "plugin" property and accessor,
 * so as to keep the nomenclature consistent.
 */
abstract class Command extends BaseCommand {

  protected $plugin;

  function setLaravel($laravel)
  {
    parent::setLaravel($laravel);
    $this->plugin = $laravel;
  }

  function setPlugin(Plugin $plugin)
  {
    return $this->setLaravel($plugin);
  }

  function getPlugin()
  {
    return $this->plugin;
  }

}