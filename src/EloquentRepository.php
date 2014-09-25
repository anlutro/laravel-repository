<?php
/**
 * Laravel 4 Repository classes
 *
 * @author   Andreas Lutro <anlutro@gmail.com>
 * @license  http://opensource.org/licenses/MIT
 * @package  l4-repository
 */

namespace anlutro\LaravelRepository;

use Illuminate\Database\Eloquent\Model;
use anlutro\LaravelValidation\ValidatorInterface;

/**
 * Abstract Eloquent repository that provides some basic functionality.
 */
abstract class EloquentRepository extends AbstractRepository
{
	/**
	 * @var \Illuminate\Database\Eloquent\Model
	 */
	protected $model;

	/**
	 * Whether to call push() or just save() when creating/updating a model.
	 *
	 * @var boolean
	 */
	protected $push = false;

	/**
	 * @param \Illuminate\Database\Eloquent\Model $model
	 * @param \anlutro\LaravelValidation\ValidatorInterface $validator
	 */
	public function __construct(Model $model, ValidatorInterface $validator = null)
	{
		parent::__construct($validator);

		$this->model = $model;

		if ($validator) {
			$validator->replace('table', $this->model->getTable());
		}
	}

	/**
	 * Set the repository's model.
	 *
	 * @param  $model  \Illuminate\Database\Eloquent\Model
	 *
	 * @return $this
	 */
	public function setModel($model)
	{
		$this->model = $model;

		return $this;
	}

	/**
	 * Get the repository's model.
	 *
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function getModel()
	{
		return $this->model;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getConnection()
	{
		return $this->model->getConnection();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return  \Illuminate\Database\Eloquent\Model
	 */
	public function getNew(array $attributes = array())
	{
		return $this->model->newInstance($attributes);
	}

	/**
	 * {@inheritdoc}
	 */
	public function update($model, array $attributes)
	{
		if (!$model->exists) {
			throw new \RuntimeException('Cannot update non-existant model');
		}

		return parent::update($model, $attributes);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return  \Illuminate\Database\Eloquent\Model
	 */
	protected function performCreate($model, array $attributes)
	{
		$model->fill($attributes);

		return $this->perform('save', $model, $attributes, false);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function performUpdate($model, array $attributes)
	{
		$model->fill($attributes);

		return $this->perform('save', $model, $attributes, false);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function performSave($model, array $attributes)
	{
		$method = $this->push ? 'push' : 'save';

		return $model->$method() ? $model : false;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function performDelete($model)
	{
		return $model->delete();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	protected function newQuery()
	{
		return $this->model->newQuery();
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getKeyName()
	{
		return $this->model->getQualifiedKeyName();
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getEntityKey($model)
	{
		return $model->getKey();
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getEntityAttributes($model)
	{
		return $model->getAttributes();
	}
}
