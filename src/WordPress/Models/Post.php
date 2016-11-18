<?php 
namespace FatPanda\Illuminate\WordPress\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Collection;

class Post extends Eloquent {

	protected $table = 'posts';

	protected $primaryKey = 'ID';

	protected $fillable = ['id'];

	function getIdAttribute()
	{
		return !empty($this->attributes['ID']) ? $this->attributes['ID'] : null;
	}

	function setIdAttribute($value)
	{
		return $this->attributes['ID'] = $value;
	}

	function getTitleAttribute()
	{
		return (object) [ 'rendered' => apply_filters('the_title', $this->post_title) ];
	}

	static function getPostByIdOrName($value)
	{
		if ($value instanceof static) {
			return $value;
		}

		return static::whereRaw('ID = ? OR post_name = ?', [ $value, $value ])
			->firstOrFail();
	}

	static function countVotes($post_id)
	{
		$post = static::getPostByIdOrName($post_id);

		return \Cache::tags(['posts', 'post-votes'])->rememberForever("counts-{$post->id}", function() use ($post) {
		
			$tallies = \DB::table('posts_votes')
				->selectRaw('count(*) as vote_count, vote')
				->where('post_id', $post->id)
				->groupBy('vote')
				->get();

			$results = [
				'votes' => [
					'-1' => 0,
					'0' => 0,
					'1' => 0
				]
			];

			foreach($tallies as $tally) {
				$results['votes'][$tally->vote] = $tally->vote_count;
			}

			$results['score'] = (-1 * $results['votes']['-1']) + $results['votes']['1'];

			return $results;

		});
	}

	static function getVote($post_id, User $user = null)
	{
		$post = static::getPostByIdOrName($post_id);
		
		if (empty($user)) {
			if (!$user = User::current()) {
				return false;
			}
		}

		return \Cache::tags(['posts', 'post-votes'])->rememberForever("get-{$post->id}", function() use ($post, $user) {
		
			$vote = \DB::table('posts_votes')
				->where([
					'post_id' => $post->id, 
					'user_id' => $user->id
				])
				->first();

			if (!$vote) {
				$vote = (object) [
					'id' => null,
					'vote' => false,
					'post_id' => $post->id,
					'user_id' => (int) $user->id,
					'created_at' => false,
					'updated_at' => false
				];
			}

			$vote->global = static::countVotes($post);

			return $vote;

		});
	}

	static function setVote($post_id, $vote, User $user = null)
	{
		$post = static::getPostByIdOrName($post_id);
		if (empty($user)) {
			if (!$user = User::current()) {
				return false;
			}
		}
		return \DB::transaction(function() use ($post, $vote, $user) {
			static::deleteVote($post->id, $user);
			
			$now = \Carbon\Carbon::now();

			$vote = (int) $vote;

			if ($vote !== -1 && $vote !== 0 && $vote !== 1) {
				throw new \Exception("Invalid vote value: must be one of -1, 0, or 1");
			}

			\DB::table('posts_votes')->insert([
				'post_id' => $post->id,
				'user_id' => $user->id,
				'vote' => $vote,
				'created_at' => $now,
				'updated_at' => $now,
			]);

			\Cache::tags(['posts', 'post-votes'])->forget("get-{$post->id}");
			\Cache::tags(['posts', 'post-votes'])->forget("counts-{$post->id}");

			return static::getVote($post->id, $user);
		});
	}

	static function deleteVote($post_id, User $user = null) {
		$post = static::getPostByIdOrName($post_id);
		
		if (empty($user)) {
			if (!$user = User::current()) {
				return false;
			}
		}

		$result = \DB::table('posts_votes')->where([
			'post_id' => $post->id,
			'user_id' => $user->id
		])->limit(1)->delete();

		\Cache::tags(['posts', 'post-votes'])->forget("get-{$post->id}");
		\Cache::tags(['posts', 'post-votes'])->forget("counts-{$post->id}");

		return $result;
	}

	static function getRating($post_id, User $user = null) 
	{
		$post = static::getPostByIdOrName($post_id);
		if (empty($user)) {
			if (!$user = User::current()) {
				return false;
			}
		}
		return \DB::table('posts_ratings')
			->where([
				'post_id' => $post->id, 
				'user_id' => $user->id
			])
			->first();
	}

	static function setRating($post_id, $rating, User $user = null)
	{
		$post = static::getPostByIdOrName($post_id);
		if (empty($user)) {
			if (!$user = User::current()) {
				return false;
			}
		}
		return \DB::transaction(function() use ($post, $rating, $user) {
			static::deleteRating($post->id, $user);
			
			$now = \Carbon\Carbon::now();
			
			\DB::table('posts_ratings')->insert([
				'post_id' => $post->id,
				'user_id' => $user->id,
				'rating' => (int) $rating,
				'created_at' => $now,
				'updated_at' => $now,
			]);

			return static::getRating($post->id, $user);
		});
	}

	static function deleteRating($post_id, User $user = null) {
		$post = static::getPostByIdOrName($post_id);
		if (empty($user)) {
			if (!$user = User::current()) {
				return false;
			}
		}
		return \DB::table('posts_ratings')->where([
			'post_id' => $post->id,
			'user_id' => $user->id
		])->limit(1)->delete();
	}
	
	static function isRead($post_id, User $user = null) 
	{
		$post = static::getPostByIdOrName($post_id);
		if (empty($user)) {
			if (!$user = User::current()) {
				return false;
			}
		}
		return \DB::table('posts_read')
			->where(['post_id' => $post->id, 'user_id' => $user->id])
			->first();
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
		return \DB::table('posts_read')
			->whereIn('post_id', $post_ids)
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

		$builder = \DB::table('posts_read')
			->select('posts.*', 'posts_read.updated_at AS last_read_at')
			->join('posts', 'posts_read.post_id', '=', 'posts.ID')
			->where('user_id', $user->id)
			->whereIn('post_type', is_array($post_type) ? $post_type : preg_split('/,\s*/', $post_type))
			->orderBy('posts_read.updated_at', 'desc');
					
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

	function markAsRead(User $user = null)
	{
		if (empty($user)) {
			if (!$user = User::current()) {
				return false;
			}
		}

		$read = [ 'user_id' => $user->id, 'post_id' => $this->id];

		try {
			\DB::table('posts_read')->insert(array_merge($read, ['created_at' => \Carbon\Carbon::now()]));
		} catch (\Exception $e) {

		} finally {
			\DB::table('posts_read')->where($read)->update([ 'updated_at' => \Carbon\Carbon::now() ]);
		}

		return \DB::table('posts_read')->where($read)->first();
	}

	function meta($name = null)
  {
      return new Collection(get_post_meta($this->id, $name));
  }

  function updateMeta($name, $value) {
      return update_post_meta($this->id, $name, $value);
  }

  function addMeta($name, $value) {
      return add_post_meta($this->id, $name, $value);
  }


	function markAsUnread(User $user = null)
	{
		if (empty($user)) {
			if (!$user = User::current()) {
				return false;
			}
		}

		$read = [ 'user_id' => $user->id, 'post_id' => $this->id];

		return \DB::table('posts_read')->where($read)->limit(1)->delete();
	}

	function toArray()
	{
		$array = [
			'id' => $this->id,
			'date' => \Carbon\Carbon::parse($this->post_date)->format('c'),
			'date_gmt' => \Carbon\Carbon::parse($this->post_date_gmt)->format('c'),
			'guid' => [
				'rendered' => site_url('?'.esc_attr(http_build_query(['p' => $this->id, 'post_type' => $this->post_type])))
			],
			'modified' => \Carbon\Carbon::parse($this->post_modified)->format('c'),
			'modified_gmt' => \Carbon\Carbon::parse($this->post_modified_gmt)->format('c'),
			'slug' => $this->post_name,
			'type' => $this->post_type,
			'link' => get_the_permalink($this->id),
			'title' => $this->title,
			'content' => [
				'rendered' => apply_filters('the_content', $this->post_content),
				'protected' => $this->post_status === 'private'
			],
			'excerpt' => [
				'rendered' => get_the_excerpt($this->id),
				'protected' => $this->post_status === 'private'
			],
			'author' => $this->post_author,
			'parent' => $this->post_parent,
		];

		// TODO: featured_meda, parent, menu_order, categories, tags

		if (function_exists('get_fields')) {
			$array['fields'] = get_fields($this->id);
		}

		return $array;
	}

}