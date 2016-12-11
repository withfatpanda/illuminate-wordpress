<?php
use FatPanda\Illuminate\WordPress\TestCase;
use FatPanda\Illuminate\WordPress\Models\Post;

class TestPosts extends TestCase {

	protected $plugin = 'test-plugin';

	function setUp()
	{
		$this->plugin()->withEloquent();
		parent::setUp();
	}
	
	function testPostCrud()
	{
		$this->plugin()->withEloquent();

		$post = new Post();

		$this->assertNull( $post->id );
		$post->title = 'Foo';
		$post->save();

		$this->assertNotNull( $post->id );
		$this->assertEquals( 1, Post::wherePostTitle('Foo')->count() );
	}

	/**
	 * Plugins should be able to specify models with alternative connections
	 */
	function testAltConnectionName()
	{

	}

}