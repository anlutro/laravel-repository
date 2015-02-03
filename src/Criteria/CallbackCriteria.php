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
 * Class that stores and applies a callback as a criteria.
 */
class CallbackCriteria implements CriteriaInterface
{
	/**
	 * @var callable
	 */
	protected $callback;

	/**
	 * @param callable $callback
	 */
	public function __construct(callable $callback)
	{
		$this->callback = $callback;
	}

	/**
	 * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
	 *
	 * @return void
	 */
	public function apply($query)
	{
		$query->where(function($query) {
			call_user_func($this->callback, $query);
		});
	}
}
