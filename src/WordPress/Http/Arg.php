<?php
namespace FatPanda\Illuminate\WordPress\Http;

/**
 * A builder for an argument in a WP API REST request.
 */
class Arg {

	protected $name;

	protected	$validate_callback = 'rest_validate_request_arg';

	protected $sanitize_callback = 'rest_sanitize_request_arg';

	protected $description;

	protected $default = null;

	protected $format = null;

	protected $minimum = null;

	protected $maximum = null;

	protected $exclusiveMinimum = null;

	protected $exclusiveMaximum = null;

	protected $type = 'string';

	protected $enum = [];

	protected $rules = '';

	protected $messages = '';

	/**
	 * Create a new Arg
	 * @param String The name of the argument as it appears in the request, e.g., "id"
	 * @param mixed Either an array of config arguments, or the default value for this argument
	 * @see http://v2.wp-api.org/extending/adding/
	 */
	function __construct($name, $config = []) 
	{
		if (!empty($config) && !is_array($config)) {
			$config = ['default' => $config];
		}
		$this->name = $name;
		foreach($config as $setting => $value) {
			$this->{$setting} = $value;
		}
	}

	/**
	 * Alias for Arg::validate_callback
	 * @param Callable
	 * @return Arg
	 */
	function where($validate_callback) 
	{
		return $this->validate_callback($validate_callback);
	}

	/** 
	 * Set the validation callback function for this argument; 
	 * the default is a global function named rest_validate_request_arg()
	 * @param Callable the callable function; receives three arguments: 
	 * the value, the instance of the WP_REST_Request, and the parameter name
	 * @return Arg
	 */
	function validate_callback($validate_callback) 
	{
		if (!is_callable($validate_callback)) {
			throw new \Exception("Validation callback is not callable: {$validate_callback}");
		}
		$this->validate_callback = $validate_callback;
		return $this;
	}

	/**
	 * Alias for Arg::sanitize_callback
	 * @param Callable
	 * @return Arg
	 */
	function sanitize($callback)
	{
		return $this->sanitize_callback($callback);
	}

	/** 
	 * Set the sanitization callback function for this argument; the sanitization
	 * callback is used to filter user input; the default is a global function
	 * named rest_sanitize_request_arg()
	 * @param Callable the callable function;
	 * @return Arg
	 */
	function sanitize_callback($sanitize_callback) 
	{
		if (!is_callable($sanitize_callback)) {
			throw new \Exception("Sanitize callback is not callable: {$sanitize_callback}");
		}
		$this->sanitize_callback = $sanitize_callback;
		return $this;
	}

	/**
	 * Set the description for this argument
	 * @param String The description
	 * @return Arg
	 */
	function description($description)
	{
		$this->description = $description;
		return $this;
	}

	/**
	 * Set the default value for this argument; the default default is null
	 * @param mixed The default value
	 * @return Arg
	 */
	function default($value = null)
	{
		$this->default = $default;
		return $this;
	}

	/** 
	 * Sets the type description for this field; the default is "string"
	 * @param String The type, e.g., "string" or "integer" or "array" or "boolean"
	 */
	function type($type = "string")
	{
		$this->type = $type;
		return $this;
	}

	/** 
	 * Sets the format for this argument
	 * @param String The format, e.g., "date-time" or "email" or "uri" or "ipv4"
	 */
	function format($format)
	{
		$this->format = $format;
		return $this;
	}
	
	/** 
	 * Set the minimum and maximum
	 * @param numeric minimum
	 * @param numeric maximum
	 * @return Arg
	 */
	function range($min = null, $max = null)
	{
		$this->min($min);
		$this->max($max);
		return $this;
	}

	/** 
	 * Set the exclusive minimum and maximum
	 * @param numeric minimum
	 * @param numeric maximum
	 * @return Arg
	 */
	function exclusiveRange($min = null, $max = null)
	{
		$this->exclusiveMin($min);
		$this->exclusiveMax($max);
		return $this;
	}

	/**
	 * Set the minimum value for this argument
	 * @param numeric minimum
	 * @return Arg
	 */
	function min($min) 
	{
		$this->minimum = $min;
		return $this;
	}

	/**
	 * Set the maximum value for this argument
	 * @param numeric minimum
	 * @return Arg
	 */
	function max($max)
	{
		$this->maximum = $max;
		return $this;
	}

	/**
	 * Set the exclusive minimum value for this argument
	 * @param numeric minimum
	 * @return Arg
	 */
	function exclusiveMin($min) 
	{
		$this->exclusiveMinimum = $min;
		return $this;
	}

	/**
	 * Set the exclusive maximum value for this argument
	 * @param numeric minimum
	 * @return Arg
	 */
	function exclusiveMax($max)
	{
		$this->exclusiveMaximum = $max;
		return $this;
	}

	/**
	 * Sets the range of possible values for this argument
	 * @param array
	 * @return Arg
	 */
	function enum($values)
	{
		$this->enum = $enum;
		return $this;
	}

	function rules($rules)
	{
		$this->rules = $rules;
		return $this;
	}

	function messages($messages)
	{
		$this->messages = $messages;
		return $this;
	}

	/**
	 * Get this Arg's name
	 * @return String
	 */
	function getName()
	{
		return $this->name;
	}

	function getValidationRules()
	{
		return $this->rules;
	}

	function getValidationMessages()
	{
		return $this->messages;
	}

	function getConfig()
	{
		$config = [];
		if (!empty($this->validate_callback)) {
			$config['validate_callback'] = $this->validate_callback;
		}
		if (!empty($this->sanitize_callback)) {
			$config['sanitize_callback'] = $this->sanitize_callback;
		}
		if (!empty($this->description)) {
			$config['description'] = $this->description;
		}
		if (!empty($this->type)) {
			$config['type'] = $this->type;
		}
		if (!empty($this->enum)) {
			$config['enum'] = $this->enum;
		}
		if (!empty($this->format)) {
			$config['format'] = $this->format;
		}
		if (!is_null($this->minimum)) {
			$config['minimum'] = $this->minimum;
		}
		if (!is_null($this->maximum)) {
			$config['maximum'] = $this->maximum;
		}
		if (!is_null($this->exclusiveMinimum)) {
			$config['exclusiveMinimum'] = $this->exclusiveMinimum;
		}
		if (!is_null($this->exclusiveMaximum)) {
			$config['exclusiveMaximum'] = $this->exclusiveMaximum;
		}
		$config['default'] = $this->default;
		
		return $config;
	}

}