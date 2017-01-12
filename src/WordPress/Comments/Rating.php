<?php
namespace FatPanda\Illuminate\WordPress\Comments;

use FatPanda\Illuminate\WordPress\Models\Post;
use FatPanda\Illuminate\WordPress\Models\Comment;

class Rating extends Comment {

  protected $type = 'rating';

  static function getRating(Post $post, User $user = null) 
  {
    if (empty($user)) {
      if (!$user = User::current()) {
        return false;
      }
    }

    return static::where(['comment_post_ID' => $post->id, 'user_id' => $user->id])->first();
  }

  static function setRating(Post $post, int $rating, User $user = null)
  {
    if (empty($user)) {
      if (!$user = User::current()) {
        return false;
      }
    }

    // return \DB::transaction(function() use ($post, $rating, $user) {

    static::deleteRating($post, $user);
    
    $now = \Carbon\Carbon::now();
    
    $rating = new static();
    $rating->post_id = $post->id;
    $rating->user_id = $user->id;
    $rating->number = $rating;
    $rating->save();

    return $rating;
  }

  static function deleteRating(Post $post, User $user = null) {
    
    if (empty($user)) {
      if (!$user = User::current()) {
        return false;
      }
    }

    return static::where([
      'comment_post_ID' => $post->id,
      'user_id' => $user->id
    ])->delete();

  }
  


}