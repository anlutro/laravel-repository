<?php

use anlutro\LaravelRepository\QueryJoinStack;
use anlutro\LaravelRepository\CriteriaInterface;

class ProductCategoryCriteria extends CriteriaInterface {

	/**
	 * @var \CategoryModel
	 */
	protected $category;

	/**
	 * @param \CategoryModel $category
	 */
	public function __construct($category)
	{
		$this->category = $category;
	}

	/**
	 * Apply the criteria to the query builder instance.
	 *
	 * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
	 * @param \anlutro\LaravelRepository\QueryJoinStack $joins
	 *
	 * @return void
	 */
	public function apply($query, QueryJoinStack $joins)
	{
		$joins->push(new ProductCategoryJoin);

		$query->where('categories.id', '=', $this->category->getKey());
	}

}