<?php
namespace FatPanda\Illuminate\WordPress\Http;

use FatPanda\Illuminate\WordPress\Plugin;
use Illuminate\Contracts\Routing\UrlGenerator as UrlGeneratorContract;

class UrlGenerator implements UrlGeneratorContract
{
  /**
   * @var Plugin
   */
  protected $plugin;

  /**
   * @var string
   */
  protected $cachedScheme;

  /**
   * Create a new URL redirector instance.
   *
   * @param  Application  $application
   * @return void
   */
  public function __construct(Plugin $plugin)
  {
    $this->plugin = $plugin;
  }

  /**
   * Get the current URL for the request.
   *
   * @return string
   */
  public function current() 
  {
    return $this->to($this->plugin->make('request')->getPathInfo());
  }

  /**
   * Generate an absolute URL to the given path.
   *
   * @param  string  $path
   * @param  mixed  $extra
   * @param  bool  $secure
   * @return string
   */
  public function to($path, $extra = [], $secure = null) 
  {
    $url = $baseUrl = home_url($path, $this->getSchemeForUrl($secure));

    if (!empty($extra)) {
      $url = $baseUrl . '?' . http_build_query($extra);
    }

    return $url;
  }

  /**
   * Get the scheme for a raw URL.
   *
   * @param  bool|null  $secure
   * @return string
   */
  protected function getScheme($secure)
  {
    if (is_null($secure)) {
        return $this->forceSchema ?: $this->plugin->make('request')->getScheme().'://';
    }

    return $secure ? 'https://' : 'http://';
  }

  /**
   * Get the scheme for a raw URL.
   *
   * @param  bool|null  $secure
   * @return string
   */
  protected function getSchemeForUrl($secure)
  {
    if (is_null($secure)) {
      if (is_null($this->cachedScheme)) {
          $this->cachedScheme = $this->plugin->make('request')->getScheme().'://';
      }

      return $this->cachedScheme;
    }

    return $secure ? 'https://' : 'http://';
  }

  /**
   * Generate a secure, absolute URL to the given path.
   *
   * @param  string  $path
   * @param  array   $parameters
   * @return string
   */
  public function secure($path, $parameters = []) 
  {
    return $this->to($path, $parameters, true);
  }

  /**
   * Generate the URL to an application asset.
   *
   * @param  string  $path
   * @param  bool    $secure
   * @return string
   */
  public function asset($path, $secure = null)
  {
    throw new \Exception("Not yet implemented");
  }

  /**
   * Get the URL to a named route.
   *
   * @param  string  $name
   * @param  mixed   $parameters
   * @param  bool  $absolute
   * @return string
   *
   * @throws \InvalidArgumentException
   */
  public function route($name, $parameters = [], $absolute = true)
  {
    throw new \Exception("Not yet implemented");
  }

  /**
   * Get the URL to a controller action.
   *
   * @param  string  $action
   * @param  mixed $parameters
   * @param  bool $absolute
   * @return string
   */
  public function action($action, $parameters = [], $absolute = true)
  {
    throw new \Exception("Not yet implemented");
  }

  /**
   * Set the root controller namespace.
   *
   * @param  string  $rootNamespace
   * @return $this
   */
  public function setRootControllerNamespace($rootNamespace)
  {
    throw new \Exception("Not yet implemented");
  }
}
