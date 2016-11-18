<?php
namespace FatPanda\Illuminate\Support\Exceptions;

use Illuminate\Validation\Validator;
use Illuminate\Validation\Factory;
use Illuminate\Translation\Translator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Exception;

class ValidationException extends Exception {

	protected $validator;

	function __construct(Validator $validator)
	{
		$this->validator = $validator;
		$message = 'The given data failed to pass validation.';
		if ($error = $this->messages()->first()) {
			$message = $error;
		}

		parent::__construct($message);
		
	}

	function messages()
	{
		return $this->validator->messages();
	}

	static function assertValid($params, $rules, $messages = [], $customize = null)
	{
		$validator = null;
		if (class_exists('Illuminate\Support\Facades\Validator')) {
			try {
				$validator = \Illuminate\Support\Facades\Validator::make($params, $rules, $messages);
			} catch (\Exception $e) {
				// we ignore this one, and fall back on a factory-made validator below
			}
		}

		if (!$validator) {
			$file = new Filesystem;
			// this looks stupid, but it works...
			// TODO: figure out how to add paths from the host site, e.g., ABSPATH.'/resources/lang'
			$path = realpath(dirname(__FILE__).'/../../../../../resources/lang');
      $loader = new FileLoader($file, $path);
      $factory = new Factory(new Translator($loader, 'en'));
			$validator = $factory->make($params, $rules, $messages);
		}

		if (is_callable($customize)) {
			$customize($validator);
		}

		if ($validator->fails()) {
			throw new self($validator);
		}

		return $validator;
	}

}