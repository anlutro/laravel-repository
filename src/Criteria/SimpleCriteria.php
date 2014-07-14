<?php
/**
 * Laravel 4 Repository classes
 *
 * @author   Andreas Lutro <anlutro@gmail.com>
 * @license  http://opensource.org/licenses/MIT
 * @package  l4-repository
 */

namespace anlutro\LaravelRepository\Criteria;

use anlutro\LaravelRepository\QueryJoinStack;
use anlutro\LaravelRepository\CriteriaInterface;

class SimpleCriteria implements CriteriaInterface
{
	protected $wheres;
	protected $orWheres;
	protected $whereIns;
	protected $orWhereIns;

	public function where($column, $value, $operator = '=')
	{
		$this->wheres[] = [$column, $operator, $value];
	}

	public function orWhere($column, $value, $operator = '=')
	{
		$this->orWheres[] = [$column, $operator, $value];
	}

	public function whereIn($column, array $values)
	{
		$this->whereIns[] = [$column, $values];
	}

	public function orWhereIn($column, array $values)
	{
		$this->orWhereIns[] = [$column, $values];
	}

	public function apply($query, QueryJoinStack $joins)
	{
		foreach (['where', 'orWhere', 'whereIn', 'orWhereIn'] as $method) {
			foreach ($this->{$method.'s'} as $params) {
				call_user_func_array($method, $params);
			}
		}
	}
}
