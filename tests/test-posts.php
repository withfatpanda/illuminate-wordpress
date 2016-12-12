<?php
use FatPanda\Illuminate\WordPress\TestCase;
use FatPanda\Illuminate\WordPress\Models\Post;
use FatPanda\Illuminate\WordPress\Models\PostMeta;
use FatPanda\WordPress\Models\PostWithAlternativeConnection;
use FatPanda\WordPress\Models\Widget;

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
		$post = new Post();
		$post->setConnection('alternative');

		$this->assertNotEquals( $this->plugin->config->get('db.default'), $post->getConnectionName() );

		$this->assertNull( $post->id );
		$post->title = 'Foo Bar Alternative';
		$post->save();

		$this->assertNotNull( $post->id );
		$this->assertEquals( 1, Post::wherePostTitle('Foo Bar Alternative')->count() );
	}

	/**
	 * Test our Custom Post Type system
	 */
	function testCustomPostTypes()
	{
		$widget = new Widget();

		$this->assertEquals( 'widget', $widget->getPostType(), "Default post type is generated from class name: Widget class should be 'widget'" );
		$this->assertEquals( 'Widget', $widget->getCalled() );
		$this->assertEquals( 'Widgets', $widget->getCalled(2) );

		$widget->title = 'My Widget';
		$widget->save();

		$this->assertNotNull( $widget->created_at );
		$this->assertNotNull( $widget->updated_at );
		$this->assertEquals( 'publish', $widget->status );
		$this->assertEquals( 'my-widget', $widget->slug );
		$this->assertEquals( 1, Post::wherePostTitle('My Widget')->wherePostType($widget->getPostType())->count() );
		$this->assertEquals( 0, Post::find($widget->id)->parent );
		$this->assertContains( $widget->getPostType(), get_post_types() );
		$this->assertEquals( 'My Widget', Widget::find($widget->id)->title->content );

		sleep(1);

		$widget->content = 'blah blah blah';
		$widget->save();

		$this->assertGreaterThan($widget->created_at->timestamp, $widget->updated_at->timestamp);

		$this->assertEquals( 'my-widget', $widget->slug );
		$this->assertEquals( 1, Post::wherePostTitle('My Widget')->wherePostType($widget->getPostType())->count() );
		$this->assertEquals( 0, Post::find($widget->id)->parent );

		// TODO: test specific aspects of configuration model
	}

	/**
	 * Test our meta data API.
	 */
	function testMetaData()
	{
		$widget = new Widget();
		$widget->special_property = [ 'foo' => 'bar' ];
		$widget->save();

		$this->assertEquals( 1, PostMeta::wherePostId($widget->id)->whereMetaKey('_special_property')->count() );
		fwrite(STDERR, print_r($this->plugin->db->table('postmeta')->get(), true));
		$this->assertEquals( [ 'foo' => 'bar' ], get_post_meta($widget->id, '_special_property', true) );
		$this->assertEquals( [ 'foo' => 'bar' ], Widget::find($widget->id)->special_property );

		$widget->special_property = [ 'wing' => 'ding' ];
		$widget->save();

		$this->assertEquals( [ 'wing' => 'ding' ], Widget::find($widget->id)->special_property );

		$widget->special_property = null;
		$widget->save();

		$this->assertEquals( null, Widget::find($widget->id)->special_property );
	}

}