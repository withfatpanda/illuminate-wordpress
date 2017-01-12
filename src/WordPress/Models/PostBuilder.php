<?php
namespace FatPanda\Illuminate\WordPress\Models;

use Illuminate\Database\Eloquent\Builder;

class PostBuilder extends Builder {

	/**
	 * Add one or more meta data conditions to the query.
	 */
	function with($meta, $operator = null, $value = null)
	{
		
		list($value, $operator) = $this->prepareValueAndOperator(
			$value, $operator, func_num_args() == 2
		);

		static $count;
		if (empty($count)) {
			$count = 0;
		}
		
		if (!is_array($meta)) {
			$meta = [ $meta => $value ];
		}

		foreach($meta as $key => $value) {
			$alias = '_with_condition_'.(++$count);
			$this->query->join("postmeta as {$alias}", "{$alias}.post_id", '=', 'ID');
			$this->query->where("{$alias}.meta_key", '_' . $key);
			$this->query->where("{$alias}.meta_value", $operator, $value);
		}

		return $this;
	}

	/**
   * Prepare the value and operator for a where clause.
   *
   * @param  string  $value
   * @param  string  $operator
   * @param  bool  $useDefault
   * @return array
   *
   * @throws \InvalidArgumentException
   */
  protected function prepareValueAndOperator($value, $operator, $useDefault = false)
  {
    if ($useDefault) {
      return [$operator, '='];
    } elseif ($this->invalidOperatorAndValue($operator, $value)) {
      throw new InvalidArgumentException('Illegal operator and value combination.');
    }

    return [$value, $operator];
  }

}