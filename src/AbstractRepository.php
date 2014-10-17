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
use anlutro\LaravelValidation\ValidatorInterface;

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
	 * @var \anlutro\LaravelValidation\ValidatorInterface
	 */
	protected $validator;

	/**
	 * Whether to validate the model or the input attributes.
	 *
	 * @var boolean
	 */
	protected $validateEntity = false;

	/**
	 * The currently active criteria.
	 *
	 * @var \anlutro\LaravelRepository\CriteriaInterface[]
	 */
	protected $criteria = [];

	/**
	 * Criteria classes that should be applied to every query.
	 *
	 * This array will be converted to an array of Criteria objects when the
	 * repository is instantiated.
	 *
	 * @var string[]|\anlutro\LaravelRepository\CriteriaInterface[]
	 */
	protected $defaultCriteria = [];

	/**
	 * Whether or not criteria should be reset on each query.
	 *
	 * @var boolean
	 */
	protected $resetCriteria = true;

	/**
	 * @var \Illuminate\Support\MessageBag
	 */
	protected $errors;

	/**
	 * @param \anlutro\LaravelValidation\ValidatorInterface $validator Optional
	 */
	public function __construct(ValidatorInterface $validator = null)
	{
		$this->resetErrors();

		$this->setupDefaultCriteria();

		if ($validator) {
			$this->setValidator($validator);
		}
	}

	/**
	 * Set up the repository's default criteria.
	 *
	 * @return void
	 */
	protected function setupDefaultCriteria()
	{
		$defaultCriteria = $this->defaultCriteria;
		$this->defaultCriteria = [];

		foreach ($defaultCriteria as $criteria) {
			$this->addDefaultCriteria(new $criteria);
		}
	}

	/**
	 * Add a default criteria to the repository. Default criteria are applied to
	 * every query.
	 *
	 * @param \anlutro\LaravelRepository\CriteriaInterface $criteria
	 *
	 * @return $this
	 */
	protected function addDefaultCriteria(CriteriaInterface $criteria)
	{
		$this->defaultCriteria[] = $criteria;

		return $this;
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
	 * @param  mixed   $attributes
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
			if ($this->validateEntity) {
				if (!$this->valid($action, $this->getEntityAttributes($object))) return false;
			} else {
				if (!$this->valid($action, $attributes)) return false;
			}
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

		return null;
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

		$result = $this->validator->valid($action, $attributes);

		if ($result === false) {
			$this->errors->merge($this->validator->getErrors());
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
		$this->applyCriteria($query);

		if ($many === false) {
			$result = $this->getRegularQueryResults($query, false);

			if (!$result && $this->throwExceptions === true) {
				throw $this->getNotFoundException($query);
			}

			return $result;
		}

		return $this->paginate === false ?
			$this->getRegularQueryResults($query, true) :
			$this->getPaginatedQueryResults($query);
	}

	/**
	 * Get a new "not found" exception.
	 *
	 * @param  mixed $query
	 *
	 * @return \Exception
	 */
	protected function getNotFoundException($query)
	{
		return new NotFoundException();
	}

	/**
	 * Get regular results from a query builder.
	 *
	 * @param  mixed   $query
	 * @param  boolean $many
	 *
	 * @return mixed
	 */
	protected function getRegularQueryResults($query, $many)
	{
		return $many ? $query->get() : $query->first();
	}

	/**
	 * Get paginated results from a query.
	 *
	 * @param  mixed $query
	 *
	 * @return mixed
	 */
	protected function getPaginatedQueryResults($query)
	{
		return $query->paginate($this->paginate);
	}

	/**
	 * Reset the repository's errors.
	 *
	 * @return $this
	 */
	protected function resetErrors()
	{
		$this->errors = new MessageBag;

		return $this;
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
	 * @param \anlutro\LaravelValidation\ValidatorInterface $validator
	 *
	 * @return $this
	 */
	public function setValidator(ValidatorInterface $validator)
	{
		$this->validator = $validator;

		return $this;
	}

	/**
	 * Get the repository's validator.
	 *
	 * @return \anlutro\LaravelValidation\ValidatorInterface
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
	 * @return $this
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
	 * @return $this
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
	 * @see   doBeforeOrAfter
	 *
	 * @param  string $action
	 * @param  object $object
	 * @param  mixed  $attributes
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
	 * @see   doBeforeOrAfter
	 *
	 * @param  string $action
	 * @param  mixed  $result
	 * @param  mixed  $attributes
	 *
	 * @return mixed
	 */
	protected function doAfter($action, $result, $attributes)
	{
		return $this->doBeforeOrAfter('after', $action, [$result, $attributes]);
	}

	/**
	 * Add a criteria to the current stack.
	 *
	 * @param  \anlutro\LaravelRepository\CriteriaInterface $criteria
	 *
	 * @return $this
	 */
	public function pushCriteria(CriteriaInterface $criteria)
	{
		$this->criteria[] = $criteria;

		return $this;
	}

	/**
	 * Reset the criteria stack.
	 *
	 * @return $this
	 */
	public function resetCriteria()
	{
		$this->criteria = [];

		return $this;
	}

	/**
	 * Apply the repository's criteria onto a query builder.
	 *
	 * @param  mixed $query
	 *
	 * @return void
	 */
	public function applyCriteria($query)
	{
		foreach ($this->defaultCriteria as $criteria) {
			$criteria->apply($query);
		}

		if (empty($this->criteria)) return;

		foreach ($this->criteria as $criteria) {
			$criteria->apply($query);
		}

		if ($this->resetCriteria) {
			$this->resetCriteria();
		}
	}

	/**
	 * Create and persist a new entity with the given attributes.
	 *
	 * @param  array  $attributes
	 *
	 * @return object|false
	 */
	public function create(array $attributes)
	{
		return $this->perform('create', $this->getNew(), $attributes, true);
	}

	/**
	 * Update an entity with the given attributes and persist it.
	 *
	 * @param  object  $entity
	 * @param  array   $attributes
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
	 * @param  object  $entity
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
	 * @param  \Illuminate\Database\Query\Builder  $query
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
	 * @param  \Illuminate\Database\Query\Builder  $query
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
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  string $column
	 * @param  string $key    Column to be used as the array keys
	 *
	 * @return array
	 */
	protected function fetchList($query, $column = 'id', $key = null)
	{
		$this->doBefore('query', $query, true);
		$this->applyCriteria($query);

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
		$query = $this->newAttributesQuery($attributes);

		if (empty($attributes)) {
			if ($this->throwExceptions) {
				throw $this->getNotFoundException($query);
			}

			return null;
		}

		return $this->fetchSingle($query);
	}

	/**
	 * Get a specific row by criteria.
	 *
	 * @param  \anlutro\LaravelRepository\CriteriaInterface $criteria
	 *
	 * @return mixed
	 */
	public function findByCriteria(CriteriaInterface $criteria)
	{
		$this->resetCriteria();
		$this->pushCriteria($criteria);

		return $this->fetchSingle($this->newQuery());
	}

	/**
	 * Get all rows.
	 *
	 * @return mixed
	 */
	public function getAll()
	{
		$query = $this->newQuery();

		return $this->fetchMany($query);
	}

	/**
	 * Get a specific row by attributes in the repository.
	 *
	 * @param  array $attributes
	 *
	 * @return mixed
	 *
	 * @throws \InvalidArgumentException if $attributes is empty
	 */
	public function getByAttributes(array $attributes)
	{
		if (count($attributes) < 1) {
			throw new \InvalidArgumentException('Cannot getByAttributes with an empty array');
		}

		return $this->fetchMany($this->newAttributesQuery($attributes));
	}

	/**
	 * Get a collection of rows by a criteria. This resets all previously pushed
	 * criteria.
	 *
	 * @param  \anlutro\LaravelRepository\CriteriaInterface $criteria
	 *
	 * @return mixed
	 */
	public function getByCriteria(CriteriaInterface $criteria)
	{
		$this->resetCriteria();
		$this->pushCriteria($criteria);

		return $this->fetchMany($this->newQuery());
	}

	/**
	 * Get a collection of rows by an array of primary keys.
	 *
	 * @param  array  $keys
	 *
	 * @return mixed
	 *
	 * @throws \InvalidArgumentException if $keys is empty
	 */
	public function getByKeys(array $keys)
	{
		if (count($keys) < 1) {
			throw new \InvalidArgumentException('Cannot getByKeys with an empty array');
		}

		$query = $this->newQuery()
			->whereIn($this->getKeyName(), $keys);

		return $this->fetchMany($query);
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
	protected function transaction(Closure $closure)
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
	 * @return \Illuminate\Database\Query\Builder
	 */
	protected abstract function newQuery();

	/**
	 * Get a new entity instance.
	 *
	 * @param  array  $attributes
	 *
	 * @return object
	 */
	public abstract function getNew(array $attributes = array());

	/**
	 * Perform a create action.
	 *
	 * @param  object  $entity
	 * @param  array   $attributes
	 *
	 * @return object|false
	 */
	protected abstract function performCreate($entity, array $attributes);

	/**
	 * Perform an update action.
	 *
	 * @param  object  $entity
	 * @param  array   $attributes
	 *
	 * @return boolean
	 */
	protected abstract function performUpdate($entity, array $attributes);

	/**
	 * Perform a delete action.
	 *
	 * @param  object  $entity
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
	 * @param  object  $entity
	 *
	 * @return mixed
	 */
	protected abstract function getEntityKey($entity);

	/**
	 * Get an entity's attributes.
	 *
	 * @param  object  $entity
	 *
	 * @return mixed
	 */
	protected abstract function getEntityAttributes($entity);
}
