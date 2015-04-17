<?php
namespace anlutro\LaravelRepository\Mappers;

use anlutro\LaravelRepository\DataMapperInterface;

class GetterSetterMapper implements DataMapperInterface
{
	public function map($entity)
	{
		$data = [];
		foreach (get_class_methods($entity) as $method) {
			if (strpos($method, 'get') !== 0) continue;
			$key = lcfirst(substr($method, 3));
			$key = ctype_lower($key) ? $key : strtolower(preg_replace('/(.)([A-Z])/', '$1_$2', $key));
			$data[$key] = $entity->$method();
		}
		return $data;
	}

	public function fill($entity, array $attributes)
	{
		foreach ($attributes as $key => $value) {
			$method = 'set'.str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $key)));
			$entity->$method($value);
		}
	}
}
