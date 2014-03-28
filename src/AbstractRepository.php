<?php
/**
 * Laravel 4 Repository classes
 *
 * @author   Andreas Lutro <anlutro@gmail.com>
 * @license  http://opensource.org/licenses/MIT
 * @package  l4-repository
 */

namespace anlutro\LaravelRepository;

use Closure;
use Illuminate\Support\MessageBag;
use anlutro\LaravelValidation\Validator;

/**
 * Abstract repository class.
 */
abstract class AbstractRepository
{
	/**
	 * Whether or not to throw exceptions or return null when "find" methods do
	 * not yield any results.
	 *
	 * @var boolean
	 */
	protected $throwExceptions = false;

	/**
	 * Whether or not to paginate results.
	 *
	 * @var boolean
	 */
	protected $paginate = false;

	/**
	 * @var \anlutro\LaravelValidation\Validator
	 */
	protected $validator;

	/**
	 * @var \Illuminate\Support\MessageBag
	 */
	protected $errors;

	/**
	 * @param \anlutro\LaravelValidation\Validator $validator Optional
	 */
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

		if ($validate === true) {
			if (!$this->valid($action, $attributes)) return false;
		}

		$beforeResult = $this->doBefore($action, $object, $attributes);
		if ($beforeResult === false) return $beforeResult;

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
				throw new NotFoundException;
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
	 * @param \anlutro\LaravelValidation\Validator $validator
	 */
	public function setValidator(Validator $validator)
	{
		$this->validator = $validator;
	}

	/**
	 * Get the repository's validator.
	 *
	 * @return \anlutro\LaravelValidation\Validator
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
	 * Toggle throwing of exceptions.
	 *
	 * @param  boolean $toggle
	 * @param  boolean $toggleValidator Whether or not to toggle exceptions on
	 * the validator as well as the repository. Defaults to true
	 *
	 * @return static
	 */
	public function toggleExceptions($toggle, $toggleValidator = true)
	{
		$this->throwExceptions = (bool) $toggle;

		if ($this->validator && $toggleValidator) {
			$this->validator->toggleExceptions((bool) $toggle);
		}

		return $this;
	}

	/**
	 * Do a before action.
	 *
	 * @see  doBeforeOrAfter
	 *
	 * @return mixed
	 */
	protected function doBefore($action, $object, $attributes)
	{
		return $this->doBeforeOrAfter('before', $action, [$object, $attributes]);
	}

	/**
	 * Do an after action.
	 *
	 * @see  doBeforeOrAfter
	 *
	 * @return mixed
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
		return $this->perform('create', $this->getNew(), $attributes, true);
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
	 * Perform a query, fetching an array of columns.
	 *
	 * @param  mixed  $query
	 * @param  string $column
	 * @param  string $key    Column to be used as the array keys
	 *
	 * @return array
	 */
	protected function fetchList($query, $column = 'id', $key = null)
	{
		$this->doBefore('query', $query, true);
		return $query->lists($column, $key);
	}

	/**
	 * Get a specific row by key in the repository.
	 *
	 * @param  mixed $key
	 *
	 * @return mixed
	 */
	public function findByKey($key)
	{
		$query = $this->newQuery()
			->where($this->getKeyName(), '=', $key);
		return $this->fetchSingle($query);
	}

	/**
	 * Get a specific row by attributes in the repository.
	 *
	 * @param  array $attributes
	 *
	 * @return mixed
	 */
	public function findByAttributes(array $attributes)
	{
		return $this->fetchSingle($this->newAttributesQuery($attributes));
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
	 * @deprecated  Use findByKey()
	 */
	public function getByKey($key)
	{
		return $this->findByKey($key);
	}

	/**
	 * Get a specific row by attributes in the repository.
	 *
	 * @param  array $attributes
	 *
	 * @return mixed
	 */
	public function getByAttributes(array $attributes)
	{
		return $this->fetchMany($this->newAttributesQuery($attributes));
	}

	/**
	 * Get a list of columns from the repository.
	 *
	 * @see    \Illuminate\Database\Query::lists()
	 *
	 * @param  string $column
	 * @param  string $key    Column to be used as the array keys
	 *
	 * @return array
	 */
	public function getList($column = 'id', $key = null)
	{
		return $this->fetchList($this->newQuery(), $column, $key);
	}

	/**
	 * Perform a database transaction.
	 *
	 * The Connection object will be passed as a first argument to the closure.
	 *
	 * @param  \Closure $closure
	 *
	 * @return mixed
	 */
	public function transaction(Closure $closure)
	{
		return $this->getConnection()->transaction($closure);
	}

	/**
	 * Get a new query that searches by attributes.
	 *
	 * @param  array  $attributes
	 * @param  string $operator   Default: '='
	 *
	 * @return mixed
	 */
	protected function newAttributesQuery(array $attributes, $operator = '=')
	{
		$query = $this->newQuery();

		foreach ($attributes as $key => $value) {
			$query->where($key, $operator, $value);
		}

		return $query;
	}

	/**
	 * Get the connection the repository uses.
	 *
	 * @return \Illuminate\Database\Connection
	 */
	protected abstract function getConnection();

	/**
	 * Get a new query builder instance.
	 *
	 * @return mixed
	 */
	protected abstract function newQuery();

	/**
	 * Get a new entity instance.
	 *
	 * @param  array  $attributes
	 *
	 * @return mixed
	 */
	protected abstract function getNew(array $attributes = array());

	/**
	 * Perform a create action.
	 *
	 * @param  mixed  $entity
	 * @param  array  $attributes
	 *
	 * @return mixed
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
