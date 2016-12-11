<?php
use FatPanda\Illuminate\WordPress\TestCase;
use FatPanda\Illuminate\WordPress\Models\Post;

use FatPanda\WordPress\Models\PostWithAlternativeConnection;

class TestPosts extends TestCase {

	function setUp()
	{
		parent::setUp();
		$this->plugin = plugin('test-plugin');
		$this->plugin->withEloquent();
	}
	
	function testPostCrudAndEvents()
	{
		$postTitle = 'Foo Bar';

		$post = new Post();

		$this->assertNull( $post->id );
		$post->title = 'Foo Bar';
		$post->save();

		$this->assertNotNull( $post->id );
		$this->assertEquals( 1, Post::wherePostTitle('Foo Bar')->count() );

		Post::wherePostTitle('Foo Bar')->delete();

		$this->assertEquals( 0, Post::wherePostTitle('Foo Bar')->count() );
	}

	/**
	 * Plugins should be able to specify models with alternative connections
	 */
	function testAltConnectionName()
	{
		$post = new PostWithAlternativeConnection();

		$this->assertNotEquals('mysql', $post->getConnectionName());

		$this->assertNull( $post->id );
		$post->title = 'Foo Bar Alternative';
		$post->save();

		$this->assertNotNull( $post->id );
		$this->assertEquals( 1, Post::wherePostTitle('Foo Bar Alternative')->count() );
	}

}