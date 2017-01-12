<?php
namespace FatPanda\Illuminate\WordPress\Comments;

use FatPanda\Illuminate\WordPress\Models\Post;
use FatPanda\Illuminate\WordPress\Models\Comment;

class ReadStatus extends Comment {

  protected $type = 'read_status';

  static function isRead($post_id, User $user = null) 
  {
    $post = Post::getPostByIdOrName($post_id);
    
    if (empty($user)) {
      if (!$user = User::current()) {
        return false;
      }
    }

    return static::where(['comment_post_ID' => $post->id, 'user_id' => $user->id])->first();
  }

  static function areRead($post_ids = [], User $user = null) 
  {
    if (!is_array($post_ids)) {
      throw new \Exception("Post IDs arguments must be an array");
    }
    
    if (empty($user)) {
      if (!$user = User::current()) {
        return false;
      }
    }

    return static::whereIn('comment_post_ID', $post_ids)
      ->where('user_id', $user->id)
      ->count() === count($post_ids);
  }

  static function lastRead($post_type = 'post', $limit = 10, User $user = null)
  {
    $posts = [];

    if (empty($user)) {
      if (!$user = User::current()) {
        return false;
      }
    }

    $builder = static::select('posts.*', 'comments.comment_date_gmt AS last_read_at')
      ->join('posts', 'comments.comment_post_ID', '=', 'posts.ID')
      ->where('user_id', $user->id)
      ->whereIn('post_type', is_array($post_type) ? $post_type : preg_split('/,\s*/', $post_type))
      ->orderBy('comments.comment_date_gmt', 'desc');
          
    if ($limit > -1) {
      $builder->limit($limit);
    }

    $read = $builder->get();

    foreach($read as $post) {
      $posts[] = [
        'ID' => $post->ID,
        'type' => $post->post_type,
        'slug' => $post->post_name,
        'title' => [
          'rendered' => apply_filters('the_title', $post->post_title, $post)
        ],
        'excerpt' => [
          'rendered' => @get_the_excerpt($post)
        ],
        'last_read_at' => \Carbon\Carbon::parse($post->last_read_at)->format('c')
      ];
    }

    return $posts;
  }

  function markAsRead(Post $post, User $user = null)
  {
    if (empty($user)) {
      if (!$user = User::current()) {
        return false;
      }
    }

    $read = new static();
    $read->post_id = $post->id;
    $read->user_id = $user->id;
    $read->save();

    return $read;
  }


  function markAsUnread(Post $post, User $user = null)
  {
    if (empty($user)) {
      if (!$user = User::current()) {
        return false;
      }
    }

    return static::where([ 'user_id' => $user->id, 'comment_post_ID' => $post->id ])->delete();
  }


}