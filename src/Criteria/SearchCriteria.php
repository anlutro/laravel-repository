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
	public function __construct(array $searchableColumns)
	{
		$this->columns = $searchableColumns;
	}

	public function apply($query)
	{
		foreach ($this->columns as $key => $value) {
			$value = '%'.str_replace(' ', '%', $value).'%';
			$query->where($key, 'like', $value);
		}
	}
}
