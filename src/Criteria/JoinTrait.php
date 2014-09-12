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

/**
 * Use this trait in criteria that contains joins, makes sure joins
 * aren't added more then once.
 */
trait JoinTrait {

	/**
	 * Apply a join to a query.
	 *
	 * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
	 * @param string $table
	 * @param \Callable|array $conditions
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	protected function join($query, $table, $conditions)
	{
		if ($this->hasJoin($query, $table))
		{
			return;
		}

		if (is_callable($conditions))
		{
			$query->join($table, $conditions);
		}
		elseif (is_array($conditions))
		{
			$one = $operator = $two = $type = $where = null;
			@list($one, $operator, $two, $type, $where) = $conditions;
			$query->join($table, $one, $operator, $two, $type, $where);
		}
		else
		{
			throw new \InvalidArgumentException('Unknown join conditions.');
		}
	}

	/**
	 * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
	 * @param string $table
	 * @return bool
	 */
	protected function hasJoin($query, $table)
	{
		foreach ($query->joins as $joinClause)
		{
			if ($table === $joinClause->table)
			{
				return true;
			}
		}

		return false;
	}

}