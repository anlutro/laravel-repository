<?php
namespace c;

use Illuminate\Support\MessageBag;

abstract class AbstractRepository
{
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
	 * The name of the primary key column.
	 *
	 * @var string
	 */
	protected $primaryKey = 'id';

	/**
	 * Construct a new repository instance.
	 */
	public function __construct()
	{
		$this->resetErrors();
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
	 * Set the repository's validator.
	 *
	 * @param $validator c\Validator
	 */
	public function setValidator(Validator $validator)
	{
		$this->validator = $validator;
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
			$errors = $this->validator->errors()->getMessages();
			$this->errors->merge($errors);
		}

		return $passes;
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
	 * Reset the repository's error messages.
	 *
	 * @return void
	 */
	protected function resetErrors()
	{
		$this->errors = new MessageBag;
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
	 * Get the primary key column name.
	 *
	 * @return string
	 */
	public function getPrimaryKey()
	{
		return $this->primaryKey;
	}

	/**
	 * Set the primary key column name.
	 *
	 * @param string $primaryKey
	 */
	public function setPrimaryKey($primaryKey)
	{
		$this->primaryKey = (string) $primaryKey;
	}

	/**
	 * Create and validate a new model instance without saving it.
	 *
	 * @param  array  $attributes
	 * @param  string $action     The name of the action to be executed on the validator. Defaults to 'create'
	 *
	 * @return mixed
	 */
	public function makeNew(array $attributes = array(), $action = 'create')
	{
		$this->resetErrors();

		if (!$this->valid($action, $attributes)) {
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
	 * Update a model without saving it.
	 *
	 * @param  mixed  $model
	 * @param  array  $attributes
	 * @param  string $action     The name of the action to be executed on the validator. Defaults to 'update'
	 *
	 * @return mixed
	 */
	public function dryUpdate($model, array $attributes, $action = 'update')
	{
		$this->resetErrors();

		if (!$this->canBeUpdated($model, $attributes)) {
			return false;
		}

		if ($this->validator) {
			$this->validator->replace('key', $this->getModelKey($model));
			if (!$this->valid($action, $attributes)) {
				return false;
			}
		}

		$this->updateModel($model, $attributes);
		$this->prepareUpdate($model, $attributes);

		if (!$this->readyForSave($model) || !$this->readyForUpdate($model)) {
			return false;
		}

		return true;
	}

	/**
	 * Get all the rows from the database.
	 *
	 * @return mixed
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
	 * @return mixed
	 */
	public function getByKey($key)
	{
		$query = $this->newQuery()
			->where($this->getPrimaryKey(), '=', $key);

		return $this->fetchSingle($query);
	}

	/**
	 * Run a query builder and return a collection of rows.
	 *
	 * @param  mixed $query
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
	 * @param  mixed $query
	 *
	 * @return mixed
	 */
	protected function fetchSingle($query)
	{
		$this->prepareQuery($query, false);

		$result = $query->first();

		if ($result === null && $this->throwExceptions) {
			throw new \Illuminate\Database\Eloquent\ModelNotFoundException;
		}

		if ($result) {
			$this->prepareModel($result);
		}
		
		return $result;
	}

	/**
	 * Determine if a model is ready to be saved to the database, regardless of
	 * create or update.
	 *
	 * @param  mixed $model
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
	 * @param  mixed $model
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
	 * @param  mixed  $model
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
	 * @param  mixed  $model
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
	 * @param  mixed $model
	 * @param  array $attributes
	 *
	 * @return void
	 */
	protected function prepareCreate($model, $attributes) {}

	/**
	 * This method is called before a model is saved in the update() method.
	 *
	 * @param  mixed $model
	 * @param  array $attributes
	 *
	 * @return void
	 */
	protected function prepareUpdate($model, $attributes) {}

	/**
	 * This method is called before fetchMany and fetchSingle. Use it to add
	 * functionality that should be present on every query.
	 *
	 * @param  mixed   $query
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
	 * @param  mixed  $collection
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
	 * @param  mixed $model
	 *
	 * @return void
	 */
	protected function prepareModel($model) {}

	/**
	 * Get a new model instance.
	 *
	 * @param  array  $attributes
	 *
	 * @return mixed
	 */
	abstract public function getNew(array $attributes = array());

	/**
	 * Create a new model instance and save it to the database.
	 *
	 * @param  array  $attributes
	 *
	 * @return mixed
	 */
	abstract public function create(array $attributes = array());

	/**
	 * Update and save changes an existing model instance.
	 *
	 * @param  mixed  $model
	 * @param  array  $attributes
	 *
	 * @return boolean
	 */
	abstract public function update($model, array $attributes);

	/**
	 * Delete an existing model instance.
	 *
	 * @param  mixed  $model
	 *
	 * @return boolean
	 */
	abstract public function delete($model);

	/**
	 * Get a new query builder instance.
	 *
	 * @return mixed
	 */
	abstract protected function newQuery();

	/**
	 * Update an existing model instance with new attributes.
	 *
	 * @param  mixed  $model
	 * @param  array  $attributes
	 *
	 * @return void
	 */
	abstract protected function updateModel($model, array $attributes);

	/**
	 * Get the primary key from a model.
	 *
	 * @param  mixed  $model
	 *
	 * @return mixed
	 */
	abstract protected function getModelKey($model);
}
