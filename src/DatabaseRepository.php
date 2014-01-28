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
	 * @var Illuminate\Database\Connection
	 */
	protected $db;

	/**
	 * The table to run queries from.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * @param Illuminate\Database\Connection $db
	 * @param string $table
	 */
	public function __construct(Connection $db)
	{
		parent::__construct();

		if ($this->table === null) {
			$class = get_class($this);
			throw new \RuntimeException("Property {$class}::\$table must be defined.");
		}

		$this->setConnection($db);
	}

	/**
	 * Set the connection to run queries on.
	 *
	 * @param Illuminate\Database\Connection $db
	 */
	public function setConnection(Connection $db)
	{
		$this->db = $db;
	}

	/**
	 * Get the connection instance.
	 *
	 * @return Illuminate\Database\Connection
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
	public function getPrimaryKey()
	{
		return "{$this->table}.{$this->primaryKey}";
	}

	/**
	 * {@inheritdoc}
	 */
	public function getNew(array $attributes = array())
	{
		$obj = new \StdClass;
		foreach ($attributes as $key => $value) {
			$obj->$key = $value;
		}
		return $obj;
	}

	/**
	 * {@inheritdoc}
	 */
	public function create(array $attributes = array())
	{
		if (!$model = $this->makeNew($attributes)) return false;
		
		$result = $this->newQuery()
			->insert((array) $model);

		return $result ? $model : false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function update($model, array $attributes)
	{
		if (!$this->dryUpdate($model, $attributes)) return false;

		$result = $this->newQuery()
			->where($this->getPrimaryKey(), '=', $this->getModelKey($model))
			->update($model->toArray());

		return (bool) $result;
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete($model)
	{
		$result = $this->newQuery()
			->where($this->getPrimaryKey(), '=', $this->getModelKey($model))
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
	protected function updateModel($model, array $attributes)
	{
		foreach ($attributes as $key => $value) {
			$model->$key = $value;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getModelKey($model)
	{
		return $model->{$this->primaryKey};
	}
}
