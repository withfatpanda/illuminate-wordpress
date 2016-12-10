<?php
namespace FatPanda\Illuminate\WordPress;

use FatPanda\Illuminate\Support\Exceptions\ValidationException;

trait CanBeSavedToProfile {

	function getFromProfile($data)
	{
		if (empty($data['user_id'])) {
			$data['user_id'] = get_current_user_id();
		}

		ValidationException::assertValid($data, [ 'user_id' => 'required|can_edit' ]);

		$meta_type = $this->getMetaType($data);

		$sections = [];

		if (!empty($data['id'])) {
			$meta_key = $meta_type . '_' . $data['id'];
			$sections[] = get_user_meta($data['user_id'], $meta_key, true);

		} else {
			$count = get_user_meta($data['user_id'], $meta_type, true);
			if (!$count) {
				return $sections;
			}
			for ($id = 1; $id <= $count; $id++) {
				$meta_key = $meta_type . '_' . $id;
				if ($section = get_user_meta($data['user_id'], $meta_key, true)) {
					$sections[] = $section;
				}
			}
			return $sections;

		}
	}

	function saveToProfile($data)
	{
		global $wpdb;

		if (empty($data['user_id'])) {
			$data['user_id'] = get_current_user_id();
		}

		$this->validate($data, $this->rules($data), $this->messages($data));

		$wpdb->query('START TRANSACTION');

		$meta_type = $this->getMetaType($data);

		if (empty($data['id'])) {
			$id = get_user_meta($data['user_id'], $meta_type, true);
			$newId = empty($id) ? 1 : $id + 1;
			if (!update_user_meta($data['user_id'], $meta_type, $newId, $id)) {
				throw new \Exception("Failed to create new ID for Profile Section {$data['type']}");
			}
			$data['id'] = $newId;
		} else {
			$data['id'] = (int) $data['id'];
		}

		$meta_key = $meta_type . '_' . $data['id'];

		$result = update_user_meta($data['user_id'], $meta_key, $data);
		if ($result) {
			$wpdb->query('COMMIT');	
		}

		return $data;
	}

	function getMetaType($data)
	{
		return 'profile_section_'.strtolower(str_replace('\\', '_', $data['type']));
	}

	function deleteFromProfile($data)
	{
		if (empty($data['user_id'])) {
			$data['user_id'] = get_current_user_id();
		}

		$this->validate($data, $this->rules($data), $this->messages($data));

		$meta_type = $this->getMetaType($data);

		if (empty($data['id'])) {
			$count = get_user_meta($data['user_id'], $meta_type, true);
			for ($id = 1; $id <= $count; $id++) {
				$meta_key = $meta_type . '_' . $id;
				delete_user_meta($data['user_id'], $meta_key);
			}
			delete_user_meta($data['user_id'], $meta_type);
		} else {
			$meta_key = $meta_type . '_' . $data['id'];
			delete_user_meta($data['user_id'], $meta_key);
		}
	}

	function validate($data, $rules, $messages)
	{
		ValidationException::assertValid($data, $rules, $messages);
	}

	function rules($data)
	{
		return [
			'user_id' => 'required|can_edit',
			'type' => 'required'
		];
	}

	function messages($data)
	{
		return [
			'user_id.can_edit' => __( 'You are not allowed to edit this Profile Section' )
		];
	}
	
}