<?php
namespace anlutro\LaravelRepository;

interface DataMapperInterface
{
	/**
	 * Map an entity to an array of persistable data.
	 *
	 * @param  object $entity
	 *
	 * @return array
	 */
	public function map($entity);

	/**
	 * Fill an entity with persisted attributes.
	 *
	 * @param  object $entity
	 * @param  array  $attributes
	 *
	 * @return void
	 */
	public function fill($entity, array $attributes);
}
