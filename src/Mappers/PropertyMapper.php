<?php
namespace anlutro\LaravelRepository\Mappers;

use anlutro\LaravelRepository\DataMapperInterface;

class PropertyMapper implements DataMapperInterface
{
	public function map($entity)
	{
		$data = [];
		foreach (get_object_vars($entity) as $key => $value) {
			$key = ctype_lower($key) ? $key : strtolower(preg_replace('/(.)([A-Z])/', '$1_$2', $key));
			$data[$key] = $value;
		}
		return $data;
	}

	public function fill($entity, array $attributes)
	{
		foreach ($attributes as $key => $value) {
			$property = lcfirst(str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $key))));
			$entity->$property = $value;
		}
	}
}
