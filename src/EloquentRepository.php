<?php
/**
 * Laravel 4 Repository classes
 *
 * @author   Andreas Lutro <anlutro@gmail.com>
 * @license  http://opensource.org/licenses/MIT
 * @package  l4-repository
 */

namespace c;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\MessageBag;

/**
 * Abstract Eloquent repository that provides some basic functionality.
 */
abstract class EloquentRepository extends AbstractRepository
{
	/**
	 * @var Illuminate\Database\Eloquent\Model
	 */
	protected $model;

	/**
	 * Whether to call push() or just save() when creating/updating a model.
	 *
	 * @var boolean
	 */
	protected $push = false;

	/**
	 * @param Illuminate\Database\Eloquent\Model $model
	 * @param c\Validator $validator
	 */
	public function __construct(Model $model, Validator $validator = null)
	{
		$this->model = $model;
		$this->setPrimaryKey($model->getQualifiedKeyName());

		if ($validator) {
			$this->validator = $validator;
			$this->validator->replace('table', $this->model->getTable());
		}

		$this->resetErrors();
	}

	/**
	 * Get the repository's model.
	 *
	 * @return Illuminate\Database\Eloquent\Model
	 */
	public function getModel()
	{
		return $this->model;
	}

	/**
	 * {@inheritdoc}
	 */
	public function newQuery()
	{
		return $this->model->newQuery();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getNew(array $attributes = array())
	{
		return $this->model->newInstance($attributes);
	}

	/**
	 * {@inheritdoc}
	 */
	public function create(array $attributes = array())
	{
		if (!$model = $this->makeNew($attributes)) return false;

		$method = $this->push ? 'push' : 'save';

		return $model->$method() ? $model : false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function updateModel($model, array $attributes)
	{
		$model->fill($attributes);
	}

	/**
	 * {@inheritdoc}
	 * 
	 * @throws RuntimeException if trying to update non-existing model
	 */
	public function dryUpdate($model, array $attributes, $action = 'update')
	{
		if (!$model->exists) {
			throw new \RuntimeException('Cannot update non-existing model');
		}

		return parent::dryUpdate($model, $attributes, $action);
	}

	/**
	 * {@inheritdoc}
	 */
	public function update($model, array $attributes)
	{
		$method = $this->push ? 'push' : 'save';

		return $this->dryUpdate($model, $attributes) ? $model->$method() : false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete($model)
	{
		return (bool) $model->delete();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getModelKey($model)
	{
		return $model->getKey();
	}
}
