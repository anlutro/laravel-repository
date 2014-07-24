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

class SearchCriteria implements CriteriaInterface
{

	/**
	 * @var array
	 */
	protected $columns = [];

	/**
	 * @var string
	 */
	protected $search = null;

	/**
	 * @param array $searchableColumns
	 * @param string $searchFor
	 */
	public function __construct(array $searchableColumns, $searchFor)
	{
		$this->columns = $searchableColumns;
		$this->search = $searchFor;
	}

	/**
	 * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
	 * @return void
	 */
	public function apply($query)
	{
		$query->where(function($query) {
			$value = '%'.str_replace(' ', '%', $this->search).'%';
			foreach ($this->columns as $column) {
				$query->where($column, 'like', $value);
			}
		});
	}
}
