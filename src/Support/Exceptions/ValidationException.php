<?php
namespace FatPanda\Illuminate\Support\Exceptions;

use Illuminate\Validation\Validator;
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

	static function assertValid($factory, $params, $rules, $messages = [], $customize = null)
	{	
		$validator = $factory->make($params, $rules, $messages);

		if (is_callable($customize)) {
			$customize($validator);
		}

		if ($validator->fails()) {
			throw new self($validator);
		}

		return $validator;
	}

}