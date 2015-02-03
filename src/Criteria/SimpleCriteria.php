<?php
/**
 * Laravel 4 Repository classes
 *
 * @author   Andreas Lutro <anlutro@gmail.com>
 * @license  http://opensource.org/licenses/MIT
 * @package  l4-repository
 */

namespace anlutro\LaravelRepository\Criteria;

use anlutro\LaravelRepository\CriteriaInterface;

class SimpleCriteria implements CriteriaInterface
{
	/**
	 * @var array
	 */
	protected $wheres = [];
	
	/**
	 * @var array
	 */
	protected $orWheres = [];
	
	/**
	 * @var array
	 */
	protected $whereIns = [];
	
	/**
	 * @var array
	 */
	protected $orWhereIns = [];

	/**
	 * @param  string $column
	 * @param  string $value
	 * @param  string $operator
	 *
	 * @return void
	 */
	public function where($column, $value, $operator = '=')
	{
		$this->wheres[] = [$column, $operator, $value];
	}

	/**
	 * @param  string $column
	 * @param  string $value
	 * @param  string $operator
	 *
	 * @return void
	 */
	public function orWhere($column, $value, $operator = '=')
	{
		$this->orWheres[] = [$column, $operator, $value];
	}

	/**
	 * @param  string $column
	 * @param  array $values
	 *
	 * @return void
	 */
	public function whereIn($column, array $values)
	{
		$this->whereIns[] = [$column, $values];
	}

	/**
	 * @param string $column
	 * @param array  $values
	 *
	 * @return void
	 */
	public function orWhereIn($column, array $values)
	{
		$this->orWhereIns[] = [$column, $values];
	}

	/**
	 * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
	 *
	 * @return void
	 */
	public function apply($query)
	{
		$query->where(function($query) {
			foreach (['where', 'orWhere', 'whereIn', 'orWhereIn'] as $method) {
				foreach ($this->{$method.'s'} as $params) {
					call_user_func_array([$query, $method], $params);
				}
			}
		});
	}
}
