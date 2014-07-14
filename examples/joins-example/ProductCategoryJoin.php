<?php

use anlutro\LaravelRepository\JoinInterface;

class ProductCategoryJoin extends JoinInterface {

	/**
	 * Apply the join to the query builder instance.
	 *
	 * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
	 *
	 * @return void
	 */
	public function apply($query)
	{
		$query->join('categories', 'categories.id', '=', 'products.category_id');
	}

}