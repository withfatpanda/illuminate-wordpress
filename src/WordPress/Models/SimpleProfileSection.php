<?php
namespace FatPanda\Illuminate\WordPress;

class SimpleProfileSection {

	use CanBeSavedToProfile;

	function __construct($type)
	{
		$this->type = $type;
	}

	function getMetaType($data) {
		return 'profile_section_'.strtolower(str_replace('\\', '_', $this->type));
	}

}