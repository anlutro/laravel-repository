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
	const LOGIC_AND = 1;
	const LOGIC_OR = 2;

	/**
	 * @var array
	 */
	protected $columns = [];

	/**
	 * @var string
	 */
	protected $search;

	/**
	 * Whether to use AND or OR.
	 *
	 * @var int
	 */
	protected $logic;

	/**
	 * @param array  $searchableColumns
	 * @param string $searchFor
	 * @param int    $logic
	 */
	public function __construct(array $searchableColumns, $searchFor, $logic = self::LOGIC_AND)
	{
		$this->columns = $searchableColumns;
		$this->search = $searchFor;

		if ($logic !== static::LOGIC_AND && $logic !== static::LOGIC_OR) {
			throw new \InvalidArgumentException('Invalid value for $logic, use one of the SearchCriteria::LOGIC constants');
		}

		$this->logic = $logic;
	}

	/**
	 * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
	 *
	 * @return void
	 */
	public function apply($query)
	{
		$method = $this->logic === static::LOGIC_AND ? 'where' : 'orWhere';

		$query->where(function($query) use($method) {
			$value = '%'.str_replace(' ', '%', $this->search).'%';
			foreach ($this->columns as $column) {
				$query->$method($column, 'like', $value);
			}
		});
	}
}
