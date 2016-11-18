<?php
namespace FatPanda\Illuminate\WordPress\Models;

use FatPanda\Illuminate\Support\Exceptions\ValidationException;

interface ProfileSectionContract {

	/**
	 * Load requested data for this section from the profile
	 * @return array Collected data from the profile
	 */
	function getFromProfile($data);

	/**
	 * Save some data of this type to the profile; validate
	 * it before saving it.
	 * @throws ValidationException
	 */
	function saveToProfile($data);

	/**
	 * Remove some data from the profile
	 */
	function deleteFromProfile($data);

	/**
	 * Given some data to store in this profile section, validate
	 * it before applying it to the model.
	 * @throws ValidationException
	 */
	function validate($data, $rules, $messages);

	/**
	 * Given some data to store in this profile section, generate
	 * the rules that should be used to validate it.
	 */
	function rules($data);

	/**
	 * Given some data to store in this profile section, generate
	 * custom validation error messages.
	 */
	function messages($data);

}