<?php
/**
 * Laravel 4 Repository classes
 *
 * @author   Andreas Lutro <anlutro@gmail.com>
 * @license  http://opensource.org/licenses/MIT
 * @package  l4-repository
 */

namespace anlutro\LaravelRepository;

interface CriteriaInterface
{
	/**
	 * Apply the criteria to the query builder instance.
	 *
	 * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
	 *
	 * @return void
	 */
	public function apply($query);
}
