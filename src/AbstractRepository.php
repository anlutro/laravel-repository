<?php
namespace c;

use Illuminate\Support\MessageBag;

abstract class AbstractRepository
{
	protected $throwExceptions = false;
	protected $paginate = false;
	protected $before = [];
	protected $after = [];
	protected $validator;
	protected $errors;

	public function __construct()
	{
		$this->resetErrors();
	}

	protected function perform($action, $object, $attributes = array(), $validate = true)
	{
		$perform = 'perform' . ucfirst($action);
		if (!method_exists($this, $perform)) {
			throw new \BadMethodCallException("Method $perform does not exist on this class");
		}

		if ($validate === true) {
			if (!$this->valid($action, $attributes)) return false;
		}

		$beforeResult = $this->doBefore($action, $object, $attributes);
		if ($beforeResult === false) return $beforeResult;

		$result = call_user_func_array([$this, $perform], [$object, $attributes]);
		if ($result === false) return $result;

		$this->doAfter($action, $result, $attributes);

		return $result;
	}

	protected function doBeforeOrAfter($which, $action, array $args)
	{
		$method = $which.ucfirst($action);
		if (method_exists($this, $method)) {
			$result = call_user_func_array([$this, $method], $args);
			if ($result === false) return $result;
		}

		if (array_key_exists($action, $this->$which)) {
			foreach ((array) $this->$which[$action] as $callback) {
				if (is_string($callback) && method_exists($this, $callback)) {
					$callback = [$this, $callback];
				}
				$result = call_user_func_array($callback, $args);
			}
		}
	}

	public function valid($action, array $attributes)
	{
		if ($this->validator === null) {
			return true;
		}

		$method = 'valid' . ucfirst($action);
		$result = $this->validator->$method($attributes);

		if ($result === false) {
			$this->errors->merge($this->validator->errors()->getMessages());
		}

		return $result;
	}

	protected function performQuery($query, $many)
	{
		if ($many === false) {
			$result = $query->first();

			if (!$result && $this->throwExceptions === true) {
				throw new \c\NotFoundException;
			}

			return $result;
		}

		return $this->paginate === false ? $query->get()
			: $query->paginate($this->paginate);
	}

	protected function resetErrors()
	{
		$this->errors = new MessageBag;
	}

	public function paginate($toggle)
	{
		$this->paginate = $toggle === false ? false : (int) $toggle;
		return $this;
	}

	protected function doBefore($action, $object, $attributes)
	{
		return $this->doBeforeOrAfter('before', $action, [$object, $attributes]);
	}

	protected function doAfter($action, $result, $attributes)
	{
		return $this->doBeforeOrAfter('after', $action, [$result, $attributes]);
	}

	public function create(array $attributes)
	{
		return $this->perform('create', $this->getNew($attributes), $attributes, true);
	}

	public function update($object, $attributes)
	{
		return $this->perform('update', $object, $attributes, true) ? true : false;
	}

	public function delete($object)
	{
		return $this->perform('delete', $object, [], false);
	}

	protected function fetchMany($query)
	{
		return $this->perform('query', $query, true, false);
	}

	protected function fetchSingle($query)
	{
		return $this->perform('query', $query, false, false);
	}

	public function getAll()
	{
		$query = $this->newQuery();
		return $this->fetchMany($query);
	}

	public function getByKey($key)
	{
		$query = $this->newQuery()
			->where($this->getKeyName(), '=', $key);
		return $this->fetchSingle($query);
	}

	protected abstract function newQuery();
	protected abstract function getNew(array $attributes = array());
	protected abstract function performCreate($object, array $attributes);
	protected abstract function performUpdate($object, array $attributes);
	protected abstract function performDelete($object);
	protected abstract function getKeyName();
}
