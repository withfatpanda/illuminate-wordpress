<?php
use FatPanda\Illuminate\WordPress\TestCase;

/**
 * Test all bootstrapping
 */
class TestBootstrap extends TestCase {

	/**
	 * Make sure that our test framework is in place
	 */
	function testBasicAssertions()
	{
		$this->assertTrue( true );
		$this->assertFalse( false );
	}

}