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

/**
 * Class that stores and applies a callback as a criteria.
 */
class CallbackCriteria implements CriteriaInterface
{
	protected $callback;

	public function __construct(callable $callback)
	{
		$this->callback = $callback;
	}

	public function apply($query, QueryJoinStack $joins)
	{
		call_user_func($this->callback, $query);
	}
}
