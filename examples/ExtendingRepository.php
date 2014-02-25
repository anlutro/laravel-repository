<?php

/**
 * A little more complex example showing how you can extend the repository to
 * add your own custom actions as well as before/after behaviour.
 */
class ExtendedRepository extends \c\EloquentRepository
{
	public function __construct(MyModel $model, MyValidator $validator)
	{
		parent::__construct($model, $validator);
	}

	/**
	 * The easiest way to add a new repository action with some custom behaviour.
	 */
	public function createAsAdmin($model, array $attributes)
	{
		// set some unfillable attributes that can't be set via the regular
		// create() method for security reasons.
		$model->is_active = array_get($attributes, 'is_active');
		$model->user_level = array_get($attributes, 'user_level');

		// ... then perform the regular create action.
		return $this->perform('create', $model, $attributes);
	}

	/**
	 * You can also use a custom $this->perform call to call before, perform and
	 * after methods automatically, as well as validation.
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
		$this->perform('update', $model, $attributes, false);
	}

	public function afterUpdateAsAdmin($model)
	{
		// update relations here maybe?
	}
}
