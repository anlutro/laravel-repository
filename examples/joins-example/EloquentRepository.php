<?php

use anlutro\LaravelRepository\EloquentRepository;

class EloquentRepository extends EloquentRepository {

	/**
	 * @param \ProductModel $model
	 * @param \ProductValidator $validator
	 */
	public function __construct(ProductModel $model, ProductValidator $validator)
	{
		parent::__construct($model, $validator);
	}

}