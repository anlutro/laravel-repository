<?php
/**
 * Laravel 4 Repository classes
 *
 * @author   Andreas Lutro <anlutro@gmail.com>
 * @license  http://opensource.org/licenses/MIT
 * @package  l4-repository
 */

namespace c;

use Illuminate\Database\Connection;

/**
 * Abstract database repository pattern. Use it to build repositories that don't
 * utilize Eloquent for simplicity or performance reasons.
 */
abstract class DatabaseRepository
{
	/**
	 * The database connection to use.
	 *
	 * @var \Illuminate\Database\Connection
	 */
	protected $db;

	/**
	 * The table to run queries from.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * Whether or not to paginate, and how many items to show per page.
	 *
	 * @var false|int
	 */
	protected $paginate = false;

	/**
	 * @param \Illuminate\Database\connection $db
	 * @param string $table (optional)
	 */
	public function __construct(Connection $db, $table = null)
	{
		$this->setConnection($db);

		if ($table !== null) {
			$this->table = (string) $table;
		}
	}

	/**
	 * Get the table that's being queried from.
	 *
	 * @return string
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * Set the connection to run queries on.
	 *
	 * @param \Illuminate\Database\Connection $db
	 */
	public function setConnection(Connection $db)
	{
		$this->db = $db;
	}

	/**
	 * Get the connection instance.
	 *
	 * @return \Illuminate\Database\Connection
	 */
	public function getConnection()
	{
		return $this->db;
	}

	/**
	 * Get all the rows from the table.
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function getAll()
	{
		$query = $this->newQuery();

		return $this->runQuery($query);
	}

	/**
	 * Get a single row from the database by key.
	 *
	 * @param  mixed  $key
	 * @param  string $keyName defaults to 'id'
	 *
	 * @return StdClass|array
	 */
	public function getByKey($key, $keyName = 'id')
	{
		$query = $this->newQuery();

		return $query->where($keyName, '=', $key)
			->first();
	}

	/**
	 * Update an existing row in the database.
	 *
	 * @param  mixed  $key
	 * @param  array  $attributes
	 * @param  string $keyName    defaults to 'id'
	 *
	 * @return int  number of rows updated
	 */
	public function update($key, array $attributes, $keyName = 'id')
	{
		$query = $this->newQuery();

		return $query->where($keyName, '=', $key)
			->update($attributes);
	}

	/**
	 * Update an existing entitiy.
	 *
	 * @param  StdClass|array $entity
	 * @param  string         $keyName  defaults to 'id'
	 *
	 * @return int            number of rows updated
	 */
	public function updateEntity($entity, $keyName = 'id')
	{
		$attributes = (array) $entity;

		return $this->update($entity->$keyName, $attributes, $keyName);
	}

	/**
	 * Delete a row from the database by key.
	 *
	 * @param  mixed  $key
	 * @param  string $keyName defaults to 'id'
	 *
	 * @return int    number of rows updated
	 */
	public function delete($key, $keyName = 'id')
	{
		$query = $this->newQuery();

		return $query->where($keyName, '=', $key)
			->delete();
	}

	/**
	 * Delete an entity.
	 *
	 * @param  StdClass|array $entity
	 * @param  string         $keyName
	 *
	 * @return int            number of rows updated
	 */
	public function deleteEntity($entity, $keyName = 'id')
	{
		return $this->delete($entity->$keyName, $keyName);
	}

	/**
	 * Insert a row into the database.
	 *
	 * @param  array  $attributes
	 *
	 * @return mixed
	 */
	public function create(array $attributes)
	{
		$query = $this->newQuery();

		return $query->insert($attributes);
	}

	/**
	 * Insert an entity into the database.
	 *
	 * @param  StdClass $entity
	 *
	 * @return mixed
	 */
	public function createEntity($entity)
	{
		return $this->create((array) $entity);
	}

	/**
	 * Get a new entity from the table.
	 *
	 * @return StdClass
	 */
	public function getNew()
	{
		static $entity;

		if ($entity === null) {
			$schema = $this->db->getDoctrineSchemaManager();
			$columns = array_keys($schema->listTableColumns($table));
			$entity = (object) array_fill_keys($columns, null);
		}

		return $entity;
	}

	/**
	 * Get a new query builder instance.
	 *
	 * @return \Illuminate\Database\Query\Builder
	 */
	protected function newQuery()
	{
		return $this->db->table($this->table);
	}

	/**
	 * Toggle pagination. False or no arguments to disable pagination, otherwise
	 * provide a number of items to show per page.
	 *
	 * @param  mixed $paginate
	 *
	 * @return void
	 */
	public function togglePagination($paginate = false)
	{
		if ($paginate === false) {
			$this->paginate = false;
		} else {
			$this->paginate = (int) $paginate;
		}
	}

	/**
	 * Run a query builder and return its results.
	 *
	 * @param  $query  query builder instance/reference
	 *
	 * @return mixed
	 */
	protected function runQuery($query)
	{
		$this->prepareQuery($query);

		if ($this->paginate === false) {
			return $query->get();
		} else {
			return $query->paginate($this->paginate);
		}
	}

	/**
	 * This function is ran by runQuery before fetching the results. Put default
	 * behaviours in this function.
	 *
	 * @param  $query  query builder instance/reference
	 *
	 * @return void
	 */
	protected function prepareQuery($query) {}
}
