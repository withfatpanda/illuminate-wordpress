<?php
namespace FatPanda\Illuminate\WordPress\Session;

use Illuminate\Support\ServiceProvider;

class WordPressSessionServiceProvider extends ServiceProvider
{

	/**
	 * @return void
	 */
	public function register()
	{
		$this->app['session']->extend('wordpress', function() {
			return new WordPressSessionHandler($this->app['config']['session.lifetime']);
		});
	}

}