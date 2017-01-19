<?php
namespace FatPanda\Illuminate\WordPress\Models;

use FatPanda\Illuminate\WordPress\Plugin;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\Validator;
use FatPanda\Illuminate\Support\Exceptions\ValidationException;
use FatPanda\Illuminate\WordPress\Concerns\ProfileSectionContract;
use FatPanda\Illuminate\WordPress\Concerns\CanBeSavedToProfile;

class ProfileSection extends Eloquent implements ProfileSectionContract {

	use CanBeSavedToProfile;

	/**
	 * Create a profile section of the given type, and associate it with the
	 * given Plugin
	 * @param Plugin
	 * @param String A classname of some class that implements ProfileSectionContract
	 * @return PluginSectionContract implementation
	 * @throws Exception If given class does not implement ProfileSectionContract
	 */
	static function factory(Plugin $plugin, $type)
	{
		$plugin->validator->extend('can_edit', function($attribute, $value, $parameters, $validator) {
			if (empty($value)) {
				return false;
			}

			$current_user = wp_get_current_user();
			if (empty($current_user)) {
				return false;
			}

			if ($attribute === 'user_id') {
				$target_user = get_user_by('ID', $value);
			} else {
				$target_user = get_user_by($attribute, $value);
			}

			if ($current_user->ID === $target_user->ID) {
				return true;
			} else if ($current_user->can('administrator')) {
				return true;
			} else {
				return false;
			}
		});

		if (empty($type)) {
			throw new \Exception("Missing required argument: type");
		}

		$type = urldecode($type);

		if (class_exists($type)) {
			$implements = class_implements($type);
			if (!isset($implements['FatPanda\Illuminate\WordPress\Concerns\ProfileSectionContract'])) {
				throw new \Exception("Profile Section type {$type} does not implement ProfileSectionContract");
			}
			$section = new $type();
			$section->setPlugin($plugin);
			return $section;
		
		} else {
			return new SimpleProfileSection($type);

		}
	}

	function user()
	{
		return $this->belongsTo('FatPanda\Illuminate\WordPress\Models\User', 'user_id', 'ID');
	}

	function saveToProfile($data)
	{
		if (empty($data['user_id'])) {
			$data['user_id'] = get_current_user_id();
		}

		if (empty($data['type'])) {
			$data['type'] = get_class($this);
		}

		$this->validate($data, $this->rules($data), $this->messages($data));

		if (!empty($data['id'])) {
			$model = static::findOrFail($data['id']);
		} else {
			$model = new static();
		}

		$disallowed = [ 'type', 'created_at', 'updated_at', 'deleted_at' ];

		$filtered = array_filter($data, function($key) use ($disallowed) {
			return 
				substr($key, 0, 1) !== '_'
				&& !in_array($key, $disallowed) 
				&& !preg_match('/^\d+$/', $key);
		}, ARRAY_FILTER_USE_KEY);

		$model->user_id = $data['user_id'];
		$model->fill($filtered);

		$model->save();

		return $model;
	}

	function getFromProfile($data)
	{
		if (empty($data['user_id'])) {
			$data['user_id'] = get_current_user_id();
		}

		ValidationException::assertValid($this->getPlugin()->validator, $data, [ 'user_id' => 'required|can_edit' ]);

		if (!empty($data['id'])) {
			return [ static::findOrfail($data['id']) ];
		} else {
			return static::where('user_id', $data['user_id'])->get();
		}
	}

	function deleteFromProfile($data)
	{
		if (empty($data['user_id'])) {
			$data['user_id'] = get_current_user_id();
		}

		ValidationException::assertValid($this->getPlugin()->validator, $data, [ 'user_id' => 'required|can_edit' ]);

		if (!empty($data['id'])) {
			return static::where('id', $data['id'])->delete();
		} else {
			return static::where('user_id', $data['user_id'])->delete();
		}
	}


	function toArray()
	{
		$array = parent::toArray();
		if (!empty($array['created_at'])) {
			$array['created_at'] = $this->created_at->format('c');
		}
		if (!empty($array['updated_at'])) {
			$array['updated_at'] = $this->updated_at->format('c');
		}
		if (!empty($array['deleted_at'])) {
			$array['deleted_at'] = $this->deleted_at->format('c');
		}
		return $array;
	}

}