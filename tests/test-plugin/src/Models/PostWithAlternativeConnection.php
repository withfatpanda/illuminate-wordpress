<?php
namespace FatPanda\WordPress\Models;

use FatPanda\Illuminate\WordPress\Models\Post;

class PostWithAlternativeConnection extends Post {

	protected $connection = 'alternative';

}