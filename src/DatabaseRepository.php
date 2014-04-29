<?php
/**
 * Laravel 4 Repository classes
 *
 * @author   Andreas Lutro <anlutro@gmail.com>
 * @license  http://opensource.org/licenses/MIT
 * @package  l4-repository
 */

namespace anlutro\LaravelRepository;

use Illuminate\Database\Connection;
use Illuminate\Support\Fluent;

/**
 * Abstract database repository pattern. Use it to build repositories that don't
 * utilize Eloquent for simplicity or performance reasons.
 */
abstract class DatabaseRepository extends AbstractRepository
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
	 * The primary key of the table.
	 *
	 * @var string
	 */
	protected $primaryKey = 'id';

	/**
	 * @param \Illuminate\Database\Connection $db
	 * @param \anlutro\LaravelValidation\Validator $validator
	 */
	public function __construct(Connection $db, Validator $validator = null)
	{
		parent::__construct($validator);

		if ($this->table === null) {
			$class = get_class($this);
			throw new \RuntimeException("Property {$class}::\$table must be defined.");
		}

		$this->setConnection($db);

		if ($validator) {
			$this->validator->replace('table', $this->table);
		}
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
	 * {@inheritdoc}
	 */
	public function getConnection()
	{
		return $this->db;
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
	 * Set the table to query from.
	 *
	 * @param string $table
	 */
	public function setTable($table)
	{
		$this->table = (string) $table;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getKeyName()
	{
		return "{$this->table}.{$this->primaryKey}";
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return  \Illuminate\Support\Fluent
	 */
	public function getNew(array $attributes = array())
	{
		return new Fluent($attributes);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return  \Illuminate\Support\Fluent|false
	 */
	protected function performCreate($entity, array $attributes = array())
	{
		foreach ($attributes as $key => $value) {
			$entity->$key = $value;
		}

		$result = $this->newQuery()
			->insert($entity->toArray());

		return $result ? $entity : false;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function performUpdate($entity, array $attributes)
	{
		foreach ($attributes as $key => $value) {
			$entity->$key = $value;
		}

		return $this->newQuery()
			->where($this->getKeyName(), '=', $entity->{$this->primaryKey})
			->update($entity->toArray());

		return $result;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function performDelete($entity)
	{
		return $this->newQuery()
			->where($this->getKeyName(), '=', $entity->{$this->primaryKey})
			->delete();

		return (bool) $result;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function newQuery()
	{
		return $this->db->table($this->table);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getEntityKey($entity)
	{
		return $entity->{$this->primaryKey};
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getEntityAttributes($entity)
	{
		return $entity->getAttributes();
	}
}
