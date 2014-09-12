<?php
namespace anlutro\LaravelRepository\Mappers;

use anlutro\LaravelRepository\DataMapperInterface;

class ArrayAccessMapper implements DataMapperInterface
{
	public function map($entity)
	{
		return iterator_to_array($entity);
	}

	public function fill($entity, array $attributes)
	{
		foreach ($attributes as $key => $value) {
			$entity[$key] = $value;
		}
	}
}
