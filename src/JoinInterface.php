<?php
/**
 * Laravel 4 Repository classes
 *
 * @author   Bradley Weston <b.weston@outlook.com>
 * @license  http://opensource.org/licenses/MIT
 * @package  l4-repository
 */

namespace anlutro\LaravelRepository;

interface JoinInterface {

	/**
	 * Apply the join to the query builder instance.
	 *
	 * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
	 *
	 * @return void
	 */
	public function apply($query);
}
