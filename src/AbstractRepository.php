<?php
/**
 * Laravel 4 Repository classes
 *
 * @author   Andreas Lutro <anlutro@gmail.com>
 * @license  http://opensource.org/licenses/MIT
 * @package  l4-repository
 */

namespace c;

use Illuminate\Support\MessageBag;

/**
 * Abstract repository class.
 */
abstract class AbstractRepository
{
	protected $throwExceptions = false;
	protected $paginate = false;
	protected $validator;
	protected $errors;

	public function __construct(Validator $validator = null)
	{
		$this->resetErrors();

		if ($validator) $this->validator = $validator;
	}

	/**
	 * Perform an action.
	 *
	 * Calls $this->before{$action} and $this->after{$action} before or after
	 * $this->perform{action} has been called. Also calls $this->valid($action,
	 * $attributes) for validation.
	 *
	 * @param  string  $action
	 * @param  mixed   $object
	 * @param  array   $attributes
	 * @param  boolean $validate
	 *
	 * @return mixed
	 */
	protected function perform($action, $object, $attributes = array(), $validate = true)
	{
		$perform = 'perform' . ucfirst($action);
		if (!method_exists($this, $perform)) {
			throw new \BadMethodCallException("Method $perform does not exist on this class");
		}

		$beforeResult = $this->doBefore($action, $object, $attributes);
		if ($beforeResult === false) return $beforeResult;

		if ($validate === true) {
			if (!$this->valid($action, $attributes)) return false;
		}

		$result = call_user_func_array([$this, $perform], [$object, $attributes]);
		if ($result === false) return $result;

		$this->doAfter($action, $result, $attributes);

		return $result;
	}

	/**
	 * Perform a before or after action.
	 *
	 * @param  string $which  before or after
	 * @param  string $action
	 * @param  array  $args
	 *
	 * @return false|null
	 */
	protected function doBeforeOrAfter($which, $action, array $args)
	{
		$method = $which.ucfirst($action);
		if (method_exists($this, $method)) {
			$result = call_user_func_array([$this, $method], $args);
			if ($result === false) return $result;
		}
	}

	/**
	 * Validate a set of attributes against a certain action.
	 *
	 * @param  string $action
	 * @param  array  $attributes
	 *
	 * @return boolean
	 */
	public function valid($action, array $attributes)
	{
		if ($this->validator === null) {
			return true;
		}

		$method = 'valid' . ucfirst($action);
		$result = $this->validator->$method($attributes);

		if ($result === false) {
			$this->errors->merge($this->validator->errors()->getMessages());
		}

		return $result;
	}

	/**
	 * Perform a query.
	 *
	 * @param  mixed   $query
	 * @param  boolean $many
	 *
	 * @return mixed
	 */
	protected function performQuery($query, $many)
	{
		if ($many === false) {
			$result = $query->first();

			if (!$result && $this->throwExceptions === true) {
				throw new \c\NotFoundException;
			}

			return $result;
		}

		return $this->paginate === false ? $query->get()
			: $query->paginate($this->paginate);
	}

	/**
	 * Reset the repository's errors.
	 *
	 * @return void
	 */
	protected function resetErrors()
	{
		$this->errors = new MessageBag;
	}

	/**
	 * Get the repository's error messages.
	 *
	 * @return \Illuminate\Support\MessageBag
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Set the repository's validator.
	 *
	 * @param \c\Validator $validator
	 */
	public function setValidator(\c\Validator $validator)
	{
		$this->validator = $validator;
	}

	/**
	 * Get the repository's validator.
	 *
	 * @return \c\Validator
	 */
	public function getValidator()
	{
		return $this->validator;
	}

	/**
	 * Toggle pagination.
	 *
	 * @param  false|int $toggle
	 *
	 * @return static
	 */
	public function paginate($toggle)
	{
		$this->paginate = $toggle === false ? false : (int) $toggle;
		return $this;
	}

	/**
	 * Do a before action.
	 *
	 * @see  doBeforeOrAfter
	 */
	protected function doBefore($action, $object, $attributes)
	{
		return $this->doBeforeOrAfter('before', $action, [$object, $attributes]);
	}

	/**
	 * Do an after action.
	 *
	 * @see  doBeforeOrAfter
	 */
	protected function doAfter($action, $result, $attributes)
	{
		return $this->doBeforeOrAfter('after', $action, [$result, $attributes]);
	}

	/**
	 * Create and persist a new entity with the given attributes.
	 *
	 * @param  array  $attributes
	 *
	 * @return mixed
	 */
	public function create(array $attributes)
	{
		return $this->perform('create', $this->getNew($attributes), $attributes, true);
	}

	/**
	 * Update an entity with the given attributes and persist it.
	 *
	 * @param  mixed  $entity
	 * @param  array  $attributes
	 *
	 * @return boolean
	 */
	public function update($entity, array $attributes)
	{
		if ($this->validator) {
			$this->validator->replace('key', $this->getEntityKey($entity));
		}

		return $this->perform('update', $entity, $attributes, true) ? true : false;
	}

	/**
	 * Delete an entity.
	 *
	 * @param  mixed $entity
	 *
	 * @return boolean
	 */
	public function delete($entity)
	{
		return $this->perform('delete', $entity, [], false);
	}

	/**
	 * Perform a query, fetching multiple rows.
	 *
	 * @param  mixed  $query
	 *
	 * @return mixed
	 */
	protected function fetchMany($query)
	{
		return $this->perform('query', $query, true, false);
	}

	/**
	 * Perform a query, fetching a single row.
	 *
	 * @param  mixed  $query
	 *
	 * @return mixed
	 */
	protected function fetchSingle($query)
	{
		return $this->perform('query', $query, false, false);
	}

	/**
	 * Get all the entities for the repository.
	 *
	 * @return mixed
	 */
	public function getAll()
	{
		$query = $this->newQuery();
		return $this->fetchMany($query);
	}

	/**
	 * Get a specific row by key in the repository.
	 *
	 * @param  mixed $key
	 *
	 * @return mixed
	 */
	public function getByKey($key)
	{
		$query = $this->newQuery()
			->where($this->getKeyName(), '=', $key);
		return $this->fetchSingle($query);
	}

	/**
	 * Get a new query builder instance.
	 */
	protected abstract function newQuery();

	/**
	 * Get a new entity instance.
	 *
	 * @param  array  $attributes
	 */
	protected abstract function getNew(array $attributes = array());

	/**
	 * Perform a create action.
	 *
	 * @param  mixed  $entity
	 * @param  array  $attributes
	 */
	protected abstract function performCreate($entity, array $attributes);

	/**
	 * Perform an update action.
	 *
	 * @param  mixed  $entity
	 * @param  array  $attributes
	 *
	 * @return boolean
	 */
	protected abstract function performUpdate($entity, array $attributes);

	/**
	 * Perform a delete action.
	 *
	 * @param  mixed $entity
	 *
	 * @return boolean
	 */
	protected abstract function performDelete($entity);

	/**
	 * Get the name of the primary key to query for.
	 *
	 * @return string
	 */
	protected abstract function getKeyName();

	/**
	 * Get the primary key of an entity.
	 *
	 * @param  mixed $entity
	 *
	 * @return mixed
	 */
	protected abstract function getEntityKey($entity);
}
