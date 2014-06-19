<?php
namespace anlutro\LaravelRepository;

class SimpleCriteria implements CriteriaInterface
{
	protected $wheres;
	protected $orWheres;
	protected $whereIns;
	protected $orWhereIns;

	public function where($column, $value, $operator = '=')
	{
		$this->wheres[] = [$column, $operator, $value];
	}

	public function orWhere($column, $value, $operator = '=')
	{
		$this->orWheres[] = [$column, $operator, $value];
	}

	public function whereIn($column, array $values)
	{
		$this->whereIns[] = [$column, $values];
	}

	public function orWhereIn($column, array $values)
	{
		$this->orWhereIns[] = [$column, $values];
	}

	public function apply($query)
	{
		foreach (['where', 'orWhere', 'whereIn', 'orWhereIn'] as $method) {
			foreach ($this->{$method.'s'} as $params) {
				call_user_func_array($method, $params);
			}
		}
	}
}
