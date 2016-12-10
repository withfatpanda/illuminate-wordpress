<?php
namespace FatPanda\Illuminate\WordPress\Http\Controllers;

use FatPanda\Illuminate\WordPress\User;
use FatPanda\Illuminate\WordPress\Post;
use FatPanda\Illuminate\WordPress\ProfileSection;

class ProfileController extends Controller {

	function postRating($request)
	{
		return Post::setRating($request['post_id'], $request['rating']);
	}

	function getRating($request)
	{
		return Post::getRating($request['post_id']);
	}

	function deleteRating($request)
	{
		return Post::deleteRating($request['post_id']);
	}

	function postVote($request)
	{
		return Post::setVote($request['post_id'], $request['vote']);
	}

	function getVote($request)
	{
		return Post::getVote($request['post_id']);
	}

	function deleteVote($request)
	{
		return Post::deleteVote($request['post_id']);
	}

	function getSection($request)
	{
		$section = ProfileSection::factory($request['type']);
		return $section->getFromProfile($request->get_params());	
	}

	function postSection($request)
	{
		$section = ProfileSection::factory($request['type']);
		return $section->saveToProfile($request->get_params());
	}

	function putSection($request)
	{
		return $this->postSection($request);
	}

	function deleteSection($request)
	{
		$section = ProfileSection::factory($request['type']);
		return $section->deleteFromProfile($request->get_params());
	}

	function postSettings($request)
	{
		return User::current()->addProfileSetting($request['name'], $request['value'], $request['unique']);
	}

	function putSettings($request)
	{
		return User::current()->updateProfileSetting($request['name'], $request['value']);
	}

	function getSettings($request)
	{
		return User::current()->getProfileSettings($request['name']);
	}

	function deleteSettings($request)
	{
		return User::current()->deleteProfileSetting($request['name']);
	}

	function getLastRead($request)
	{
		return Post::lastRead($request['post_type']);
	}

	function postRead($request)
	{
		$post = Post::findOrFail($request['post_id']);
		return $post->markAsRead();
	}

	function deleteRead($request)
	{
		$post = Post::findOrFail($request['post_id']);
		return $post->markAsUnread();
	}

}