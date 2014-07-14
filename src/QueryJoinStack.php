<?php
/**
 * Laravel 4 Repository classes
 *
 * @author   Bradley Weston <b.weston@outlook.com>
 * @license  http://opensource.org/licenses/MIT
 * @package  l4-repository
 */

namespace anlutro\LaravelRepository;

class QueryJoinStack {

	/**
	 * @var array
	 */
	protected $joins;

	/**
	 * @var \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
	 */
	protected $query;

	/**
	 * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
	 */
	public function __construct($query)
	{
		$this->query = $query;
	}

	/**
	 * Push a join on to the query.
	 * 
	 * We only allow the class to be inserted once as you
	 * can't join twice on the same table.
	 * 
	 * @param \anlutro\LaravelRepository\JoinInterface
	 * @return static
	 */
	public function push(JoinInterface $join)
	{
		$key = get_class($join);

		$this->joins[$key] = $join;

		return $this;
	}

	/**
	 * Run through the joins and apply them.
	 */
	public function apply()
	{
		foreach ($this->joins as $join)
		{
			$join->apply($query);
		}
	}

}