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
abstract class EloquentRepository
{
	/**
	 * @var Illuminate\Database\Eloquent\Model
	 */
	protected $model;

	/**
	 * @var c\Validator
	 */
	protected $validator;

	/**
	 * @var Illuminate\Support\MessageBag
	 */
	protected $errors;

	/**
	 * How the repository should paginate.
	 *
	 * @var false|int
	 */
	protected $paginate = false;

	/**
	 * Whether to throw exceptions or return null on single row queries.
	 *
	 * @var boolean
	 */
	protected $throwExceptions = false;

	/**
	 * @param Illuminate\Database\Eloquent\Model $model
	 * @param c\Validator $validator
	 */
	public function __construct(Model $model, Validator $validator = null)
	{
		$this->model = $model;

		if ($validator) {
			$this->validator = $validator;
			$this->validator->setTable($this->model->getTable());
		}

		$this->errors = new MessageBag;
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
	 * Get the repository's validator.
	 *
	 * @return c\Validator
	 */
	public function getValidator()
	{
		return $this->validator;
	}

	/**
	 * @see getErrors()
	 */
	public function errors()
	{
		return $this->errors;
	}

	/**
	 * Get the repository's error messagess.
	 * 
	 * @return Illuminate\Support\MessageBag
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Toggle pagination. False or no arguments to disable pagination, otherwise
	 * provide a number of items to show per page.
	 *
	 * @param  false|int $paginate
	 *
	 * @return void
	 */
	public function paginate($paginate = false)
	{
		if ($paginate === false) {
			$this->paginate = false;
		} else {
			$this->paginate = (int) $paginate;
		}

		return $this;
	}

	/**
	 * Toggle whether or not to throw exceptions on single row queries.
	 *
	 * @param  boolean $toggle
	 *
	 * @return void
	 */
	public function toggleExceptions($toggle = true)
	{
		$this->throwExceptions = (bool) $toggle;

		return $this;
	}

	/**
	 * Get a new query builder instance.
	 *
	 * @return Illuminate\Database\Eloquent\Builder
	 */
	public function newQuery()
	{
		return $this->model->newQuery();
	}

	/**
	 * Get a new model instance.
	 *
	 * @param  array  $attributes
	 *
	 * @return Illuminate\Database\Eloquent\Model
	 */
	public function getNew(array $attributes = array())
	{
		return $this->model->newInstance($attributes);
	}

	/**
	 * Create and validate a new model instance without saving it.
	 *
	 * @param  array  $attributes
	 *
	 * @return Illuminate\Database\Eloquent\Model
	 */
	public function makeNew(array $attributes = array())
	{
		if (!$this->valid('create', $attributes)) {
			return false;
		}

		$model = $this->getNew($attributes);
		$this->prepareCreate($model, $attributes);

		if (!$this->readyForSave($model) || !$this->readyForCreate($model)) {
			return false;
		}

		return $model;
	}

	/**
	 * Create a new model instance and save it to the database.
	 *
	 * @param  array $attributes
	 *
	 * @return Illuminate\Database\Eloquent\Model|false
	 */
	public function create(array $attributes = array())
	{
		if (!$model = $this->makeNew($attributes)) return false;

		return $model->save() ? $model : false;
	}

	/**
	 * Get all the rows from the database.
	 *
	 * @return Illuminate\Database\Eloquent\Collection
	 */
	public function getAll()
	{
		return $this->fetchMany($this->newQuery());
	}

	/**
	 * Get a single row by its primary key.
	 *
	 * @param  mixed $key
	 *
	 * @return Illuminate\Database\Eloquent\Model|null
	 */
	public function getByKey($key)
	{
		$query = $this->newQuery()
			->where($this->model->getQualifiedKeyName(), $key);

		return $this->fetchSingle($query);
	}

	/**
	 * Update a model without saving it.
	 *
	 * @param  Illuminate\Database\Eloquent\Model  $model
	 * @param  array  $attributes
	 * @param  string $action     The name of the action to be executed on the validator. Defaults to 'update'
	 *
	 * @return Illuminate\Database\Eloquent\Model|false
	 *
	 * @throws RuntimeException if trying to update non-existing model
	 */
	public function dryUpdate($model, array $attributes, $action = 'update')
	{
		if (!$model->exists) {
			throw new \RuntimeException('Cannot update non-existing model');
		}

		if (!$this->canBeUpdated($model, $attributes)) {
			return false;
		}

		$this->validator->setKey($model->getKey());
		if (!$this->valid($action, $attributes)) {
			return false;
		}

		$model->fill($attributes);
		$this->prepareUpdate($model, $attributes);

		if (!$this->readyForSave($model) || !$this->readyForUpdate($model)) {
			return false;
		}

		return true;
	}

	/**
	 * Update and save changes an existing model instance.
	 *
	 * @param  mixed $model      A model instance or primary key.
	 * @param  array $attributes
	 *
	 * @return boolean
	 */
	public function update($model, array $attributes)
	{
		$model = $this->verifyModel($model);

		return $this->dryUpdate($model, $attributes) ? $model->save() : false;
	}

	/**
	 * Delete an existing model instance.
	 *
	 * @param  mixed  $model
	 *
	 * @return boolean
	 */
	public function delete($model)
	{
		$model = $this->verifyModel($model);

		return (bool) $model->delete();
	}

	/**
	 * Ensure that a model given is an actual instance of a model. If it isn't,
	 * try to fetch it by primary key or throw an exception.
	 *
	 * @param  mixed  $model
	 *
	 * @return Illuminate\Database\Eloquent\Model
	 *
	 * @throws Illuminate\Database\Eloquent\ModelNotFoundException
	 */
	protected function verifyModel($model)
	{
		if (!$model instanceof Model && !$model = $this->getByKey($model)) {
			throw new ModelNotFoundException("Could not find model with key [$model]");
		}

		return $model;
	}

	/**
	 * Run a query builder and return a collection of rows.
	 *
	 * @param  Illuminate\Database\Eloquent\Builder  $query
	 *
	 * @return mixed
	 */
	protected function fetchMany($query)
	{
		$this->prepareQuery($query, true);

		if ($this->paginate === false) {
			$results = $query->get();
			$this->prepareCollection($results);
		} else {
			$results = $query->paginate($this->paginate);
			$this->preparePaginator($results);
		}

		return $results;
	}

	/**
	 * Run a query builder and return a single row.
	 *
	 * @param  Illuminate\Database\Eloquent\Builder  $query
	 *
	 * @return Illuminate\Database\Eloquent\Model
	 */
	protected function fetchSingle($query)
	{
		$this->prepareQuery($query, false);

		if ($this->throwExceptions === true) {
			$result = $query->firstOrFail();
		} else {
			$result = $query->first();
		}

		if ($result) {
			$this->prepareModel($result);
		}
		
		return $result;
	}

	/**
	 * Check if a set of input attributes are valid for a certain action.
	 * Merges any validation errors into the repository's messages.
	 *
	 * @param  string $action
	 * @param  array  $attributes
	 *
	 * @return bool
	 */
	protected function valid($action, array $attributes)
	{
		// if no validator is set, no validation should be done, return true
		if ($this->validator === null) {
			return true;
		}

		$method = 'valid' . ucfirst($action);
		$passes = $this->validator->$method($attributes);

		if (!$passes) {
			$errors = $this->validator->errors()->all();
			$this->errors->merge($errors);
		};

		return $passes;
	}

	/**
	 * Determine if a model is ready to be saved to the database, regardless of
	 * create or update.
	 *
	 * @param  Illuminate\Database\Eloquent\Model $model
	 *
	 * @return boolean
	 */
	protected function readyForSave($model)
	{
		return true;
	}

	/**
	 * Determine if a model is ready to be created in the database.
	 *
	 * @param  Illuminate\Database\Eloquent\Model $model
	 *
	 * @return boolean
	 */
	protected function readyForCreate($model)
	{
		return true;
	}

	/**
	 * Determine if a model can be updated.
	 *
	 * @param  Illuminate\Database\Eloquent\Model  $model
	 * @param  array  $attributes
	 *
	 * @return boolean
	 */
	protected function canBeUpdated($model, $attributes)
	{
		return true;
	}

	/**
	 * Determine if a model is ready to be saved to the database after an update.
	 *
	 * @param  Illuminate\Database\Eloquent\Model  $model
	 *
	 * @return boolean
	 */
	protected function readyForUpdate($model)
	{
		return true;
	}

	/**
	 * This method is called before a model is saved in the create() method.
	 *
	 * @param  Illuminate\Database\Eloquent\Model $model
	 * @param  array  $attributes
	 *
	 * @return void
	 */
	protected function prepareCreate($model, $attributes) {}

	/**
	 * This method is called before a model is saved in the update() method.
	 *
	 * @param  Illuminate\Database\Eloquent\Model $model
	 * @param  array  $attributes
	 *
	 * @return void
	 */
	protected function prepareUpdate($model, $attributes) {}

	/**
	 * This method is called before fetchMany and fetchSingle. Use it to add
	 * functionality that should be present on every query.
	 *
	 * @param  Illuminate\Database\Eloquent\Builder $query
	 * @param  boolean $many  Whether the query is fetching one or many rows
	 *
	 * @return void
	 */
	protected function prepareQuery($query, $many) {}

	/**
	 * This method is called after fetchMany when pagination === false. Use it
	 * to perform operations on a collection of models before it is returned
	 * from the repository.
	 *
	 * @param  Illuminate\Database\Eloquent\Collection $collection
	 *
	 * @return void
	 */
	protected function prepareCollection($collection) {}

	/**
	 * This method is called after fetchMany when pagination is enabled. Use it
	 * to perform operations on a paginator object before it is returned from
	 * the repository.
	 *
	 * @param  Illuminate\Pagination\Paginator $paginator
	 *
	 * @return void
	 */
	protected function preparePaginator($paginator) {}

	/**
	 * This method is called after fetchSingle. Use it to prepare a model before
	 * it is returned by the repository.
	 *
	 * @param  Illuminate\Database\Eloquent\Model $model
	 *
	 * @return void
	 */
	protected function prepareModel($model) {}
}
