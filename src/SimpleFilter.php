<?php
namespace anlutro\LaravelRepository;

class SimpleFilter implements FilterInterface
{
	protected $whereFilters = [];
	protected $orWhereFilters = [];
	protected $whereInFilters = [];
	protected $orWhereInFilters = [];

	public function filterWhere($column, $value, $operator = '=')
	{
		$this->whereFilters[] = [$column, $operator, $value];
	}

	public function filterOrWhere($column, $value, $operator = '=')
	{
		$this->orWhereFilters[] = [$column, $operator, $value];
	}

	public function filterWhereIn($column, array $values)
	{
		if (empty($values)) return;
		$this->whereInFilters[] = [$column, $values];
	}

	public function filterOrWhereIn($column, array $values)
	{
		if (empty($values)) return;
		$this->orWhereInFilters[] = [$column, $values];
	}

	public function apply($query)
	{
		foreach (['where', 'orWhere', 'whereIn', 'orWhereIn'] as $method) {
			$this->applyWhereFilters($query, $method);
		}
	}

	protected function applyWhereFilters($query, $method)
	{
		$filters = $method . 'Filters';
		if (empty($this->$filters)) return;

		$query->where(function($query) use($method, $filters) {
			foreach ($this->$filters as $clause) {
				call_user_func_array([$query, $method], $clause);
			}
		});
	}
}
