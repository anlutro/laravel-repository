<?php
/**
 * Laravel 4 Repository classes
 *
 * @author   Andreas Lutro <anlutro@gmail.com>
 * @license  http://opensource.org/licenses/MIT
 * @package  l4-repository
 */

namespace anlutro\LaravelRepository;

use anlutro\LaravelValidation\ValidatorInterface;

/**
 * Repository interface.
 */
interface RepositoryInterface {

	/**
	 * Validate a set of attributes against a certain action.
	 *
	 * @param  string $action
	 * @param  array  $attributes
	 *
	 * @return boolean
	 */
	public function valid($action, array $attributes);

	/**
	 * Get the repository's error messages.
	 *
	 * @return \Illuminate\Support\MessageBag
	 */
	public function getErrors();

	/**
	 * Set the repository's validator.
	 *
	 * @param \anlutro\LaravelValidation\ValidatorInterface $validator
	 *
	 * @return static
	 */
	public function setValidator(ValidatorInterface $validator);

	/**
	 * Get the repository's validator.
	 *
	 * @return \anlutro\LaravelValidation\ValidatorInterface
	 */
	public function getValidator();

	/**
	 * Toggle pagination.
	 *
	 * @param  false|int $toggle
	 *
	 * @return static
	 */
	public function paginate($toggle);

	/**
	 * Toggle throwing of exceptions.
	 *
	 * @param  boolean $toggle
	 * @param  boolean $toggleValidator Whether or not to toggle exceptions on
	 * the validator as well as the repository. Defaults to true
	 *
	 * @return static
	 */
	public function toggleExceptions($toggle, $toggleValidator = true);

	/**
	 * Add a criteria to the current stack.
	 *
	 * @param  \anlutro\LaravelRepository\CriteriaInterface $criteria
	 *
	 * @return static
	 */
	public function pushCriteria(CriteriaInterface $criteria);

	/**
	 * Reset the criteria stack.
	 *
	 * @return static
	 */
	public function resetCriteria();

	/**
	 * Apply the repository's criteria onto a query builder.
	 *
	 * @param  mixed $query
	 *
	 * @return void
	 */
	public function applyCriteria($query);

	/**
	 * Create and persist a new entity with the given attributes.
	 *
	 * @param  array  $attributes
	 *
	 * @return object|false
	 */
	public function create(array $attributes);

	/**
	 * Update an entity with the given attributes and persist it.
	 *
	 * @param  object  $entity
	 * @param  array   $attributes
	 *
	 * @return boolean
	 */
	public function update($entity, array $attributes);

	/**
	 * Delete an entity.
	 *
	 * @param  object  $entity
	 *
	 * @return boolean
	 */
	public function delete($entity);
	/**
	 * Get a specific row by key in the repository.
	 *
	 * @param  mixed $key
	 *
	 * @return mixed
	 */
	public function findByKey($key);

	/**
	 * Get a specific row by attributes in the repository.
	 *
	 * @param  array $attributes
	 *
	 * @return mixed
	 */
	public function findByAttributes(array $attributes);

	/**
	 * Get a specific row by criteria.
	 *
	 * @param  \anlutro\LaravelRepository\CriteriaInterface $criteria
	 *
	 * @return mixed
	 */
	public function findByCriteria(CriteriaInterface $criteria);

	/**
	 * Get all the entities for the repository.
	 *
	 * @return object[]
	 */
	public function getAll();

	/**
	 * Get a specific row by attributes in the repository.
	 *
	 * @param  array $attributes
	 *
	 * @return mixed
	 *
	 * @throws \InvalidArgumentException if $attributes is empty
	 */
	public function getByAttributes(array $attributes);

	/**
	 * Get a collection of rows by criteria.
	 *
	 * @param  \anlutro\LaravelRepository\CriteriaInterface $criteria
	 *
	 * @return mixed
	 */
	public function getByCriteria(CriteriaInterface $criteria);

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
	public function getList($column = 'id', $key = null);

}
