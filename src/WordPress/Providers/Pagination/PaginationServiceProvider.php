<?php
namespace FatPanda\Illuminate\WordPress\Providers\Pagination;

class PaginationServiceProvider extends \Illuminate\Pagination\PaginationServiceProvider {

	/**
   * Bootstrap any application services.
   *
   * @return void
   */
  public function boot()
  {
      $this->loadViewsFrom(__DIR__.'/resources/views', 'pagination');

      if ($this->app->runningInConsole()) {
          $this->publishes([
              __DIR__.'/resources/views' => $this->app->basePath('resources/views/vendor/pagination'),
          ], 'laravel-pagination');
      }
  }

}