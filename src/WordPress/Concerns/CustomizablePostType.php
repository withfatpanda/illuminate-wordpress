<?php
namespace FatPanda\Illuminate\WordPress\Concerns;

/**
 * Adds functions for customzing which UI and data schema features
 * a Post Type supports.
 */
trait CustomizablePostType {

	protected function supports()
	{
		$supports = [];

		if ($this->supportsTitle()) {
			$supports[] = 'title';
		}

		if ($this->supportsEditor()) {
			$supports[] = 'editor';
		}

		if ($this->supportsExcerpt()) {
			$supports[] = 'excerpt';
		}

		if ($this->supportsAuthor()) {
			$supports[] = 'author';
		}

		if ($this->supportsThumbnail()) {
			$supports[] = 'thumbnail';
		}

		if ($this->supportsComments()) {
			$supports[] = 'comments';
		}		

		if ($this->supportsTrackbacks()) {
			$supports[] = 'trackbacks';
		}

		if ($this->supportsRevisions()) {
			$supports[] = 'revisions';
		}

		if ($this->supportsCustomFields()) {
			$supports[] = 'custom-fields';
		}

		if ($this->supportsCustomFields()) {
			$supports[] = 'custom-fields';
		}

		if ($this->supportsPageAttributes()) {
			$supports[] = 'page-attributes';
		}

		if ($this->supportsPageFormats()) {
			$supports[] = 'page-formats';
		}

		return $supports;
	}

	protected function supportsTitle()
	{
		return true;
	}

	protected function supportsEditor()
	{
		return true;
	}

	protected function supportsExcerpt()
	{
		return true;
	}

	protected function supportsAuthor()
	{
		return true;
	}

	protected function supportsThumbnail()
	{
		return false;
	}

	protected function supportsComments()
	{
		return true;
	}

	protected function supportsTrackbacks()
	{
		return true;
	}

	protected function supportsRevisions()
	{
		return true;
	}

	protected function supportsCustomFields()
	{
		return true;	
	}

	protected function supportsPageAttributes()
	{
		return true;
	}

	protected function supportsPageFormats()
	{
		return true;
	}

}