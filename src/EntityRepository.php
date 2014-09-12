<?php
/**
 * Laravel 4 Repository classes
 *
 * @author   Andreas Lutro <anlutro@gmail.com>
 * @license  http://opensource.org/licenses/MIT
 * @package  l4-repository
 */

namespace anlutro\LaravelRepository;

class EntityRepository extends DatabaseRepository
{
	protected $entityClass;
	protected $dataMapper;
	protected $dataMapperClass;

	public function getNew(array $attributes = array())
	{
		$entity = new $this->entityClass();
		$this->fillEntityAttributes($entity, $attributes);
		return $entity;
	}

	protected function getEntityAttributes($entity)
	{
		return $this->getDataMapper()->map($entity);
	}

	protected function fillEntityAttributes($entity, array $attributes)
	{
		$this->getDataMapper()->fill($entity, $attributes);
	}

	protected function getDataMapper()
	{
		if ($this->dataMapper === null) {
			$this->dataMapper = new $this->dataMapperClass;
		}

		return $this->dataMapper;
	}
}
