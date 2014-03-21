<?php

/**
 * A little more complex example showing how you can extend the repository to
 * add your own custom actions as well as before/after behaviour.
 */
class ExtendedRepository extends \anlutro\LaravelRepository\EloquentRepository
{
	public function __construct(MyModel $model, MyValidator $validator)
	{
		parent::__construct($model, $validator);
	}

	/**
	 * Add custom behaviour like this
	 */
	public function updateAsAdmin($model, array $attributes)
	{
		// performs before/perform/after actions automatically
		// validator->validUpdateAsAdmin is also called.
		$this->perform('updateAsAdmin', $model, $attributes);
	}

	public function beforeUpdateAsAdmin($model, array $attributes)
	{
		// return false or throw an exception there to prevent the update.
	}

	public function performUpdateAsAdmin($model, array $attributes)
	{
		$model->unfillable_attribute = array_get($attributes, 'unfillable_attribute');

		// 4th argument is whether or not to validate. in this case we've already
		// validated via $this->perform('updateAsAdmin') so we specify false.
		// by doing this we also call beforeUpdate, performUpdate, afterUpdate
		$this->perform('update', $model, $attributes, false);
	}

	public function afterUpdateAsAdmin($model)
	{
		// update relations here maybe?
	}
}
