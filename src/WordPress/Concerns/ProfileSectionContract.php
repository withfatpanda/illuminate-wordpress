<?php
namespace FatPanda\Illuminate\WordPress\Concerns;

use FatPanda\Illuminate\WordPress\Plugin;
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

	/**
	 * Allow for making a ProfileSection aware of the plugin that's
	 * implementing it.
	 * @param Plugin
	 */
	function setPlugin(Plugin $plugin);

	/**
	 * Accessor for the plugin instance associated with this section
	 * @return Plugin
	 */
	function getPlugin();


}