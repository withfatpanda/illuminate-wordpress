<?php
namespace FatPanda\Illuminate\WordPress\Providers\Database;

class DatabaseServiceProvider extends Illuminate\Database\DatabaseServiceProvider {

	/**
   * Register the Eloquent factory instance in the container.
   *
   * @return void
   */
  protected function registerEloquentFactory()
  {
      $this->app->singleton(FakerGenerator::class, function () {
          return FakerFactory::create();
      });

      $this->app->singleton(EloquentFactory::class, function ($app) {
          $faker = $app->make(FakerGenerator::class);

          return EloquentFactory::construct($faker, $this->app->basePath('database/factories'));
      });
  }

}