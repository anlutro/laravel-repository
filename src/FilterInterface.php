<?php
namespace anlutro\LaravelRepository;

interface FilterInterface
{
	/**
	 * Apply the filter to a query builder.
	 *
	 * @param  mixed $query Query builder reference
	 *
	 * @return void
	 */
	public function apply($query);
}
